<?php
defined( 'ABSPATH' ) || exit;

/**
 * Register post meta for Artist, Song, and Album CPTs with REST API visibility.
 */
add_action( 'init', function () {

	// Object-scoped: authorise the write against the SPECIFIC post, not just the
	// contributor-level 'edit_posts' capability. See M3 security assessment FIND-03.
	$auth_edit = function ( $allowed, $meta_key, $object_id ) {
		return current_user_can( 'edit_post', $object_id );
	};

	// ── Artist meta (existing + new) ─────────────────────────────────
	register_post_meta( 'artist', 'valt_policy_id', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'artist', 'valt_social_x', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'artist', 'valt_social_instagram', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'artist', 'valt_social_spotify', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'esc_url_raw',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'artist', 'valt_fan_count', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'artist', 'valt_featured', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	// ── Song meta (existing + new) ───────────────────────────────────
	register_post_meta( 'song', 'valt_release_status', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 1,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'song', 'valt_mint_count', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	// NFT lifecycle fields.
	foreach ( [
		'valt_nft_status',
		'valt_nft_transaction_id',
		'valt_nft_ipfs_hash',
		'valt_nft_uid',
		'valt_nft_asset_id',
		'valt_nft_price_ada',
	] as $key ) {
		register_post_meta( 'song', $key, [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'string',
			'default'           => '',
			'sanitize_callback' => 'sanitize_text_field',
			'auth_callback'     => $auth_edit,
		] );
	}

	// The mint recipient's wallet address is used server-side by the mint flow
	// only — it must NOT be readable via the public REST API, where anyone could
	// harvest the pseudonymous address off a published song. See M3 security
	// assessment FIND-02.
	register_post_meta( 'song', 'valt_nft_wallet_address', [
		'show_in_rest'      => false,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth_edit,
	] );

	// NFT integer fields.
	foreach ( [
		'valt_nft_price_usd'  => 0,
		'valt_nft_max_supply' => 0,
		'valt_nft_image_id'   => 0,
	] as $key => $default ) {
		register_post_meta( 'song', $key, [
			'show_in_rest'      => true,
			'single'            => true,
			'type'              => 'integer',
			'default'           => $default,
			'sanitize_callback' => 'absint',
			'auth_callback'     => $auth_edit,
		] );
	}

	// ── Album meta (campaign fields) ─────────────────────────────────
	register_post_meta( 'album', 'valt_campaign_active', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'album', 'valt_campaign_goal', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'integer',
		'default'           => 0,
		'sanitize_callback' => 'absint',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'album', 'valt_campaign_deadline', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'sanitize_text_field',
		'auth_callback'     => $auth_edit,
	] );

	register_post_meta( 'album', 'valt_campaign_description', [
		'show_in_rest'      => true,
		'single'            => true,
		'type'              => 'string',
		'default'           => '',
		'sanitize_callback' => 'wp_kses_post',
		'auth_callback'     => $auth_edit,
	] );
} );
