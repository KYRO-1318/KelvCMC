# KelvCMC API documentation

KelvCMC exposes a public REST API protected by [Laravel Sanctum](https://laravel.com/docs/12.x/sanctum) personal access tokens. The full machine-readable specification is available as **OpenAPI 3.0** at `docs/openapi.yaml` and served by the app at `GET /api/docs` (load it in any Swagger UI / Redoc viewer).

## Base URL

```
https://panel.yourcompany.com/api/v1
```

## Authentication

Create a personal access token:

```bash
php artisan sanctum:create-token
```

Or programmatically:

```php
$token = $user->createToken('api-client');
echo $token->plainTextToken;
```

Send it on every request:

```
Authorization: Bearer <token>
```

## Rate limiting

API requests are throttled to `API_RATE_LIMIT_PER_MINUTE` (default **60/min**) per IP.

## Endpoints

### Users

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/users` | admin `api.users.read` | List users (search + pagination) |
| GET | `/users/me` | any | Current user with service/invoice/ticket counts |
| GET | `/users/{id}` | admin `api.users.read` | Show a user |

### Products

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/products` | any | List active products with plans |
| GET | `/products/{id}` | any | Product detail |

### Orders

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/orders` | any | Current user's orders |
| POST | `/orders` | any | Place an order |
| GET | `/orders/{id}` | owner | Order detail |

#### `POST /orders`

```json
{
    "product_id": 1,
    "plan_id": 2,
    "cycle": "monthly",
    "coupon": "WELCOME20",
    "config": {
        "domain": "example.com",
        "node_id": 3
    }
}
```

Behaviour: creates the order + invoice; if the user's internal credit covers the total, the order is paid and provisioning is queued automatically. Otherwise the invoice is left open and the client pays through the web portal.

### Services

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/services` | owner | List services (filter by `status`) |
| GET | `/services/{id}` | owner | Service detail |

### Invoices

| Method | Path | Auth | Description |
| --- | --- | --- | --- |
| GET | `/invoices` | owner | List invoices (filter by `status`) |
| GET | `/invoices/{id}` | owner | Invoice with items and payments |

## Errors

| Code | Meaning |
| --- | --- |
| 401 | Missing/invalid token |
| 403 | Insufficient permissions |
| 404 | Resource not found |
| 422 | Validation error (see `errors` array) |
| 429 | Rate limit exceeded |

## Example

```bash
# list active products
curl -H "Authorization: Bearer $TOKEN" \
     https://panel.yourcompany.com/api/v1/products

# place an order
curl -X POST https://panel.yourcompany.com/api/v1/orders \
     -H "Authorization: Bearer $TOKEN" \
     -H "Content-Type: application/json" \
     -d '{"product_id": 1, "cycle": "monthly", "coupon": "WELCOME20"}'
```

## Webhooks

Payment gateways post to `POST /api/webhooks/{gateway}` — configure this URL in your Stripe/PayPal/Mollie/Coinbase dashboards:

```
https://panel.yourcompany.com/api/webhooks/stripe
https://panel.yourcompany.com/api/webhooks/paypal
https://panel.yourcompany.com/api/webhooks/mollie
https://panel.yourcompany.com/api/webhooks/coinbase
```

> **Note:** `docs/openapi.yaml` uses the versioned prefix `/api/v1`; webhook endpoints are unversioned by design.

Next: [Themes](themes.md) · [Plugins](plugins.md)
