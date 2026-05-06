# Laravel + Inertia Migration Start

## Visual Thesis

BlipBlap should feel light, fast, travel-friendly, and playful: soft off-white pages, crisp deep-blue typography, rounded sky-blue actions, scattered geometric accents, and destination-led imagery.

## Content Plan

- Hero: BlipBlap brand promise, search, primary CTA, travel image.
- Trust strip: global coverage, instant activation, transparent pricing, support.
- Destinations: local/regional/global tabs and popular location tiles.
- How it works: choose plan, scan QR, connect.
- FAQ: compact accordion list.
- App/account CTA: manage eSIMs easily.
- Footer: site map, legal, social.

## Interaction Thesis

- Search expands into destination suggestions.
- Destination tabs swap the package group without leaving the page.
- Package cards and FAQ rows use small hover/reveal transitions.

## Build Order

1. Install Laravel dependencies once Composer is available.
2. Configure `.env` with `ESIM_GO_BASE_URL` and `ESIM_GO_API_KEY`.
3. Run migrations and seed demo plans.
4. Wire catalogue sync to admin-only route.
5. Build destination detail page based on the Airalo workflow context.
6. Add checkout/payment provider.
7. Add account eSIM management, install details, status refresh, and top-up.

## eSIM Go Catalogue Sync

- Live API access was confirmed against `https://api.esim-go.com/v2.5`.
- Catalogue response uses `bundles`, `pageCount`, `rows`, and `pageSize`.
- With `perPage=100`, the API returned `pageCount=54`.
- Full sync completed successfully with `0` failed bundles.
- Local synced plan count after the full pull: `5344`.
- Sync command: `php artisan blipblap:sync-catalogue --per-page=100`.
- Resume from a later page when needed: `php artisan blipblap:sync-catalogue --per-page=100 --start-page=3`.
