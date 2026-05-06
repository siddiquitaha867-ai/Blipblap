# WordPress Plugin Context Applied To Laravel

Source context:

- `../../CODEX_CONTEXT.md`
- `../../AIRALO_WORKFLOW_CONTEXT.md`
- `../../LARAVEL_MIGRATION_CONTEXT.md`

## Original Plugin Identity

The original project was a WordPress/WooCommerce plugin named **BlipBlap eSIM Manager** using the `BlipBlap\ESIM` namespace. Its responsibility was not only storefront rendering, but also catalogue sync, checkout validation, paid-order provisioning, customer eSIM ownership, install details, admin support tools, loyalty, campaigns, promotions, email notifications, and API logs.

The Laravel/Inertia app should preserve that product scope while replacing WordPress/WooCommerce with Laravel-native modules.

## Core Parity Map

| WordPress plugin area | Laravel equivalent |
| --- | --- |
| WooCommerce catalogue products | `esim_plans` plus storefront destination/package pages |
| WooCommerce paid order hooks | `esim_orders` plus future payment webhook/provisioning jobs |
| Customer owned ICCIDs | `customer_esims` |
| Supplier API client | `App\Services\EsimGo\EsimGoClient` |
| Catalogue mapper/sync | `CatalogueMapper` and `CatalogueSyncController` |
| API logs | `api_logs` |
| Customer visible events | `esim_events` |
| Loyalty events/balances | `loyalty_events`, `loyalty_balances` |
| Cart recovery/followup/referral events | `campaign_events` |
| Promotion rules | `promotion_rules` |
| Admin users/customers/orders/logs | `/admin` pages and future admin modules |
| My Account eSIMs | future `/account/esims` pages |
| Install/QR/manual details | future checkout success + account eSIM detail |
| Top-up | future account top-up flow through eSIM Go apply/top-up endpoints |

## Preserved Flow

1. Sync catalogue from eSIM Go into local database.
2. Render destination and package pages from local database.
3. Let customer choose a package.
4. Require login/signup or customer details during checkout.
5. Validate before payment where provider endpoint supports it.
6. Provision only after payment success.
7. Store ICCID, QR/manual install data, status, and customer ownership.
8. Show install details on success and in account area.
9. Refresh delayed/pending eSIM status by scheduled job.
10. Support top-up only for owned eligible ICCIDs.

## Do Not Regress Current Additions

Recent Laravel additions should stay intact:

- BlipBlap/Airalo-style header and ESIM Plans dropdown.
- BlipBlap assets and destination flags.
- Airalo-style country eSIM pages.
- Auth modal pages.
- Admin dashboard and users pages.

