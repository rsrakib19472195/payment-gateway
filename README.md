# TWIXO API Support Server

This project is a small PHP + SQLite API server intended to receive transaction data from the TWIXO Android app's external API feature.

## What was verified from the APK

The APK contains:

- External API URL configuration
- External API authentication key configuration
- Auto-send to API option
- `X-Access-Key` header string
- `Authorization: Bearer ...` support string
- transaction fields such as:
  - `transaction_id`
  - `service`
  - `sender`
  - `amount`
  - `sms_balance`
  - `raw_sms`
  - `verification_reason`
  - `deviceId`
  - `sms_timestamp`

The APK also contains a placeholder webhook URL (`your-api-endpoint.com/webhook`), so the shipped APK does not expose a guaranteed production endpoint. The API URL is configurable inside the app.

## Requirements

- PHP 8.0+
- PDO SQLite extension
- Apache/Nginx
- HTTPS strongly recommended

## Setup

1. Upload the complete folder to your server.
2. Open `config.php`.
3. Change:

```php
const API_KEY = 'CHANGE_THIS_TO_A_LONG_RANDOM_KEY';
```

Use a long random key, for example 32+ characters.
4. Make sure `data/` is writable by PHP.
5. Test:

`GET /health`

6. In TWIXO app's External API Configuration:
   - API URL: `https://YOUR-DOMAIN.example/`
   - API Key: the same value from `config.php`
   - Enable Auto-send to API.

The server accepts the key through either:

`X-Access-Key: YOUR_KEY`

or:

`Authorization: Bearer YOUR_KEY`

## Endpoints

### Health

`GET /health`

### Receive transaction

`POST /api/transaction`

Example JSON:

```json
{
  "transaction_id": "TX123456",
  "service": "bkash",
  "sender": "01700000000",
  "amount": 100,
  "sms_balance": 2500,
  "raw_sms": "You have received Tk 100...",
  "verification_reason": "verified",
  "deviceId": "android-device-id",
  "sms_timestamp": 1760000000
}
```

### List transactions

`GET /api/transactions?limit=50`

Optional:

`?service=bkash`

`?status=received`

### Get one transaction

`GET /api/transaction/TX123456`

### Statistics

`GET /api/stats`

### Latest reported balance

`GET /api/balance`

Optional:

`GET /api/balance?service=bkash`

## Security

Do not publish your API key in a frontend website.

Use HTTPS.

For production, replace the simple single-key authentication with per-device keys, key rotation, rate limiting, IP restrictions, and request signatures if needed.

## Important

This server is designed to be compatible with the fields and authentication strings visible in the supplied APK. Because the APK is a compiled Flutter application and its production API URL is configurable, the exact production request body cannot be guaranteed from the APK alone. The receiver intentionally accepts common aliases for the observed transaction fields.
