# eSIM Go API Notes

Postman source analyzed: `esim-go-24-postman.json`.

The collection uses `X-API-Key` authentication and had `https://api.esim-go.com/v2.4` as its variable value. BlipBlap's Laravel config defaults to the newer base URL supplied by the user:

```text
https://api.esim-go.com/v2.5
```

## Core Endpoints Needed First

- `GET /catalogue`
- `GET /catalogue/bundle/{name}`
- `GET /inventory`
- `GET /organisation`
- `GET /organisation/balance`
- `GET /organisation/groups`
- `GET /networks`
- `POST /orders`
- `GET /orders`
- `GET /orders/{orderReference}`
- `POST /esims/apply`
- `GET /esims/assignments`
- `GET /esims`
- `PUT /esims`
- `GET /esims/{iccid}`
- `GET /esims/{iccid}/refresh`
- `GET /esims/{iccid}/bundles`
- `GET /esims/{iccid}/bundles/{name}`
- `GET /esims/{iccid}/compatible/{bundle}`
- `GET /esims/{iccid}/history`
- `GET /esims/{iccid}/location`
- `POST /esims/{iccid}/sms`
- `POST /inventory/refund`
- usage callback URL configured in the eSIM Go portal

## Laravel Mapping

- `App\Services\EsimGo\EsimGoClient` wraps the API.
- `config/esim-go.php` reads base URL and API key from env.
- `CatalogueSyncController` pulls catalogue data into local `esim_plans`.

Never commit a real API key. Put it only in local `.env`:

```text
ESIM_GO_API_KEY=...
```
