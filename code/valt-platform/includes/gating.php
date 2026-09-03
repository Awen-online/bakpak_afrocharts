<?php
defined( 'ABSPATH' ) || exit;

/**
 * How long the CardanoPress asset cache is trusted before a refresh is queued.
 *
 * The cache (cardanopress_stored_assets) is only rebuilt on login/explicit sync,
 * so without this it can outlive real ownership: a holder who sells or transfers
 * their NFT keeps access until they next log in, and a secondary-market buyer
 * gains it late. A short TTL makes gating track on-chain ownership in both
 * directions — which matters precisely because holdings move on other markets.
 */
const VALT_ASSET_CACHE_TTL = 15 * MINUTE_IN_SECONDS;

/**
 * Check whether the current logged-in user holds at least one asset
 * from the given CardanoPress NFT policy ID.
 *
 * The decision is server-side: it reads the CardanoPress-maintained asset cache,
 * never a client-supplied value, and non-holders are never sent the gated markup.
 * The cache's integrity depends on the wallet layer binding each stored asset set
 * to a cryptographically verified wallet. That binding is provided by CardanoPress
 * itself and MUST be kept at >= v1.36.1, which derives the stake address
 * server-side from the signed wallet address and ignores any client-supplied
 * stake_address (the fix for the auth-bypass 0-day Valt disclosed). The interim
 * stake-binding guard mu-plugin was removed once that upstream fix shipped.
 * Freshness is kept in check by valt_maybe_refresh_stale_assets().
 */
function valt_user_holds_policy( string $policy_id ): bool {
	if ( ! function_exists( 'cardanoPress' ) ) {
		return false;
	}

	$profile = cardanoPress()->userProfile();

	if ( ! $profile->isConnected() ) {
		return false;
	}

	valt_maybe_refresh_stale_assets();

	$assets = $profile->storedAssets();

	if ( empty( $assets ) || ! is_array( $assets ) ) {
		return false;
	}

	foreach ( $assets as $asset ) {
		if ( ! empty( $asset['policy_id'] ) && $asset['policy_id'] === $policy_id ) {
			return true;
		}
	}

	return false;
}

/**
 * Queue a background refresh of the current user's on-chain asset cache when it
 * has gone stale, without blocking the request.
 *
 * The gate serves the currently-cached result on this request and the refreshed
 * set applies to the next one. Refreshing reuses CardanoPress's own sanctioned
 * path — the wp_login status check — which rebuilds the cache from Blockfrost for
 * the given user and fails safe if the wallet layer or Blockfrost is unconfigured.
 */
function valt_maybe_refresh_stale_assets(): void {
	$user_id = get_current_user_id();

	if ( ! $user_id ) {
		return;
	}

	$synced_at = (int) get_user_meta( $user_id, 'valt_assets_synced_at', true );

	if ( ( time() - $synced_at ) < VALT_ASSET_CACHE_TTL ) {
		return;
	}

	// Avoid piling up duplicate jobs while one is already queued.
	if ( wp_next_scheduled( 'valt_refresh_user_assets', [ $user_id ] ) ) {
		return;
	}

	// Stamp now, so a burst of gated views in the same window queues only once.
	update_user_meta( $user_id, 'valt_assets_synced_at', time() );
	wp_schedule_single_event( time() + 1, 'valt_refresh_user_assets', [ $user_id ] );
}

/**
 * Cron handler: rebuild a user's asset cache from chain via CardanoPress.
 *
 * @param int $user_id WP user whose cache to refresh.
 */
function valt_refresh_user_assets( int $user_id ): void {
	if ( ! function_exists( 'cardanoPress' ) ) {
		return;
	}

	$user = get_user_by( 'id', $user_id );

	if ( ! $user ) {
		return;
	}

	// CardanoPress's doWalletStatusChecks() is bound to wp_login and rebuilds the
	// stored-asset cache for the passed user from Blockfrost. It reads the user's
	// stored network/stake and returns early if either is missing or Blockfrost is
	// not configured, so this is safe to fire from cron.
	do_action( 'wp_login', $user->user_login, $user );
}
add_action( 'valt_refresh_user_assets', 'valt_refresh_user_assets' );

/**
 * Resolve the artist NAME a held NFT belongs to.
 *
 * All Valt songs are minted under one shared policy, so policy alone can't tell
 * which artist an NFT belongs to. We resolve via the on-chain CIP-25 metadata
 * 'artist' field, falling back to mapping the song title (metadata 'name') through
 * the local NFT registry. Returns '' when the artist can't be determined.
 *
 * @param array $asset A CardanoPress stored asset.
 * @return string Artist name, or '' if unknown.
 */
function valt_asset_artist_name( array $asset ): string {
	global $wpdb;
	$meta  = $asset['onchain_metadata'] ?? [];
	$table = "{$wpdb->prefix}valt_nft_registry";

	// 1) CIP-25 artist field set at mint time (only some NFTs carry it).
	if ( ! empty( $meta['artist'] ) && is_string( $meta['artist'] ) ) {
		return trim( $meta['artist'] );
	}

	// 2) Song title (metadata 'name') -> registry -> artist_name.
	if ( ! empty( $meta['name'] ) && is_string( $meta['name'] ) ) {
		$artist = $wpdb->get_var( $wpdb->prepare(
			"SELECT artist_name FROM {$table} WHERE display_name = %s AND artist_name <> '' LIMIT 1",
			$meta['name']
		) );
		if ( $artist ) {
			return trim( (string) $artist );
		}
	}

	// 3) Fallback for NFTs minted WITHOUT on-chain metadata: resolve from the deterministic
	//    on-chain token name. Normalize (drop the "valt" prefix(es), digits, punctuation) and
	//    match it against each registry song title, longest first to avoid short false hits.
	$hex     = $asset['asset_name'] ?? '';
	$decoded = $hex ? ( @hex2bin( $hex ) ?: '' ) : '';
	if ( $decoded ) {
		$norm = strtolower( preg_replace( '/[^a-z0-9]/i', '', $decoded ) ); // e.g. "valtvaltdeadend261"
		$rows = $wpdb->get_results( "SELECT display_name, artist_name FROM {$table} WHERE artist_name <> ''", ARRAY_A );
		if ( $rows ) {
			usort( $rows, function ( $a, $b ) {
				return strlen( $b['display_name'] ) <=> strlen( $a['display_name'] );
			} );
			foreach ( $rows as $r ) {
				$song_norm = strtolower( preg_replace( '/[^a-z0-9]/i', '', $r['display_name'] ) );
				if ( strlen( $song_norm ) >= 4 && strpos( $norm, $song_norm ) !== false ) {
					return trim( $r['artist_name'] );
				}
			}
		}
	}

	return '';
}

/**
 * Filter a list of stored assets to those belonging to a given artist (by policy + artist name).
 * Assets whose artist can't be resolved are kept (conservative — never hide an unknown holding);
 * assets resolved to a DIFFERENT artist are excluded.
 *
 * @param array  $assets       CardanoPress stored assets.
 * @param string $policy_id    The artist's policy id.
 * @param string $artist_name  The artist's display name.
 * @return array Filtered assets.
 */
function valt_filter_assets_for_artist( array $assets, string $policy_id, string $artist_name ): array {
	$out = [];
	foreach ( $assets as $asset ) {
		if ( ( $asset['policy_id'] ?? '' ) !== $policy_id ) {
			continue;
		}
		$resolved = valt_asset_artist_name( $asset );
		if ( $resolved !== '' && strcasecmp( $resolved, $artist_name ) !== 0 ) {
			continue; // Belongs to a different artist.
		}
		$out[] = $asset;
	}
	return $out;
}

/**
 * Return the Artist CPT post whose post_author matches the currently logged-in user.
 * Returns null if not logged in or no linked artist found.
 */
function valt_get_current_artist(): ?WP_Post {
	if ( ! is_user_logged_in() ) {
		return null;
	}

	$posts = get_posts( [
		'post_type'      => 'artist',
		'author'         => get_current_user_id(),
		'posts_per_page' => 1,
		'post_status'    => [ 'publish', 'draft' ],
	] );

	return $posts[0] ?? null;
}

/**
 * Testnet-only wallet enforcement.
 *
 * Valt runs on the Cardano pre-production testnet, so a MAINNET wallet can't
 * hold the testnet song NFTs that drive gating — connecting one is invalid.
 * On every load, disconnect any connected mainnet wallet. Only 'mainnet' is
 * rejected; testnet/preprod (and any other/unknown value) is left untouched,
 * so legitimate testnet users are never disconnected. Self-disables if the
 * platform is ever switched to mainnet.
 */
function valt_enforce_testnet_wallet(): void {
	if ( ! function_exists( 'cardanoPress' ) || ! is_user_logged_in() ) {
		return;
	}
	$cfg = function_exists( 'valt_nmkr_config' ) ? valt_nmkr_config() : [];
	if ( ! empty( $cfg['mode'] ) && 'mainnet' === $cfg['mode'] ) {
		return; // Platform is on mainnet — no restriction.
	}
	$profile = cardanoPress()->userProfile();
	if ( ! $profile || ! method_exists( $profile, 'connectedNetwork' ) || ! $profile->isConnected() ) {
		return;
	}
	if ( 'mainnet' === $profile->connectedNetwork() ) {
		$profile->saveWallet( '' );
		$profile->saveStake( '' );
		set_transient( 'valt_wallet_wrong_network_' . get_current_user_id(), 1, 120 );
	}
}
add_action( 'init', 'valt_enforce_testnet_wallet', 20 );
