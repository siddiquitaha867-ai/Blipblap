# Migration Parity Backlog

This tracks what is already applied from the saved project context and what remains for production parity.

## Done

- Laravel/Inertia/Vue app scaffold.
- Storefront home page.
- Airalo-style destination pages.
- Package detail, checkout review, success/install preview.
- Auth/register/login/logout/email verification.
- Admin dashboard and user detail pages.
- eSIM Go client/config/catalogue sync base.
- Core tables:
  - `users`
  - `esim_plans`
  - `esim_orders`
  - `customer_esims`
  - `api_logs`
- Legacy parity tables added:
  - `esim_events`
  - `loyalty_events`
  - `loyalty_balances`
  - `campaign_events`
  - `promotion_rules`

## Next Production Modules

- Payment gateway integration.
- Payment webhook handling.
- Paid-order provisioning job.
- eSIM Go install details persistence.
- My eSIMs account pages.
- Top-up eligibility and top-up checkout.
- Status refresh scheduler.
- Customer-visible event timeline.
- Admin catalogue/settings/logs/orders/promotions/loyalty screens.
- Branded email templates for ready, delayed, status updated, reward, top-up, reminder, and followup.
- API credential management in admin instead of only `.env`.

