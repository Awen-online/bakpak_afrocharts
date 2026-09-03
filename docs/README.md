<p align="center">
  <img src="assets/valt-banner.png" alt="Valt — Token-gated music platform on Cardano" width="100%">
</p>

# Afrocharts | Web3 Artist Portal (Awen)
## aka Valt
Visit our domain: https://valt.digital

## Documentation

Here you can find links to all neccessary documentation for each of the milestones throughout the progress of this project.
### Milestone 1 - Initialization
>**Documents**
>
>[Setup Report](M1_Initialization/Setup_Report_Web3_Artist_Portal_Afrocharts_x_Awen.pdf) - information about initial design, including Afrocharts.com platform analysis setup
>
>[Afrocharts Partner API Doc](M1_Initialization/AfroCharts_Partners_API_Documentation_V1.pdf) - documentation to integrate with Afrocharts.com platform via permissioned API
>
>[Project Status Report](M1_Initialization/Awen_x_AfroCharts_Project_Status_Report.pdf) - Current project status
>
>[Project Timeline](M1_Initialization/Awen_x_AfroCharts_Project_Timeline.pdf) - project roadmap, timeline, and responsibilities
>
>[Notion Kanban Board](https://awen-online.notion.site/3519bb544b96476ea2baef08991e7644?v=575936b18fe94d4dba30037ca464be9f&pvs=32) - board of all tasks, stories, and epics for this project


### Milestone 2 - Development
>**Documents**
>
>[Proof of Achievement](M2_Development/Valt_M2_Proof_of_Achievement.pdf) - consolidated M2 submission: every Output, Acceptance Criterion, and Evidence Requirement mapped to verifiable proof, with the evidence screenshots embedded
>
>[Development Report](M2_Development/M2_Development_Report.pdf) - the web3 portal as built: architecture, on-chain NFT proof, the minting flow, user flow & token-gating
>
>[Launch Partner Roster](M2_Development/M2_Launch_Partner_Roster.pdf) - the artist partners chosen and ready for the initial launch (Acceptance Criterion: 5-10 artists)
>
>[Project Status Report (M2)](M2_Development/Awen%20x%20AfroCharts%20Project%20Status%20Report%20M2.pdf) - current project status
>
>[Project Timeline (M2)](M2_Development/Awen%20x%20AfroCharts%20Project%20Timeline%20M2.pdf) - updated roadmap, timeline, and responsibilities
>
>[Evidence Screenshots](M2_Development/screenshots) - the E1-E10 evidence set (NMKR Studio + inventory, Cardanoscan, live portal, wallet connection, the full minting sequence, The Valt unlocked, NFT Monitor backend)
>
>**Verify**
>
>[Live portal](https://www.valt.digital) - the web3 artist portal, running on the Cardano pre-production testnet
>
>[On-chain proof (Cardanoscan preprod)](https://preprod.cardanoscan.io/tokenPolicy/bf5a88ac0a236c22c2772a51ff2fa33301e17c42aa8f95fcd585b86a) - the 8 song NFTs minted under policy `bf5a88ac…`
>
>[Source code](../code) - the curated open-source platform (plugin + theme)

### Milestone 3 - Implementation & Prelaunch
>**Documents**
>
>[Proof of Achievement](M3_Implementation/M3_Proof_of_Achievement.pdf) - the milestone summary: outputs, acceptance criteria, evidence, and how a reviewer can verify every claim independently
>
>[Test & Bug-Fix Report](M3_Implementation/M3_Test_and_Bugfix_Report.pdf) - the test suite and its results, the six defects found and resolved, repository reconciliation, known limitations
>
>[Security Audit Report](M3_Implementation/M3_Security_Audit_Report.pdf) - automated scanning (semgrep) plus an autonomous-style assessment, the manual and on-chain security reviews, findings with dispositions, and mainnet prerequisites
>
>[User Feedback & Roadmap](M3_Implementation/M3_User_Feedback_and_Roadmap.pdf) - feedback channels, the in-product survey, the analysis (n=5: artists and collectors), improvements made, and the updated roadmap through M4/M5
>
>[Project Status Report (M3)](M3_Implementation/M3_Project_Status.pdf) - current project status
>
>[Project Timeline (M3)](M3_Implementation/M3_Project_Timeline.pdf) - updated roadmap, timeline, and responsibilities
>
>**Verify**
>
>[Test suite](../tests) - 15 tests / 25 assertions: `composer install && vendor/bin/phpunit`
>
>[CI workflow](../.github/workflows/ci.yml) - lint + suite on PHP 8.0-8.3 plus a semgrep security scan, on every push
>
>[Load check](../tests/load-plugin.php) - `php tests/load-plugin.php code/valt-platform` boots the published build against a stubbed WordPress API and reports `VERDICT=LOADED`

### Milestone 4 - Launch and Rollout
### Milestone 5 - Closeout & Evaluation
