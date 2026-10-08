# Twixo — Free Payment Automation

Public source for [Twixo](https://twixo.sweez.xyz) — Bangladeshi personal payment automation (bKash, Nagad, Rocket, Upay).

This package is the **payment page + SMS ingest + verify callback** you integrate into your own site or app.

## Requirements

- PHP 7.4+ with PDO MySQL and cURL
- MySQL / MariaDB
- Twixo mobile app (SMS reader) from [twixo.sweez.xyz](https://twixo.sweez.xyz)

## Quick setup

1. Upload this folder to your server (e.g. `https://yourdomain.com/pay/`).
2. Import the database schema:

   ```bash
   mysql -u root -p < schema.sql
   ```

3. Copy local config and edit secrets:

   ```bash
   cp config.local.example.php config.local.php
   ```

   Set wallet numbers, DB credentials, SMS API keys, and `CALLBACK_API_URL`.

4. In the Twixo app, point the SMS webhook to:

   ```
   https://yourdomain.com/pay/read_sms.php
   ```

   Use the same `SMS_ACCESS_KEY` and `SMS_API_KEY` as in `config.local.php`.

5. Start a payment session with a POST JSON body to `index.php`:

   ```json
   {
     "uid": "1001",
     "name": "Customer Name",
     "number": "017XXXXXXXX",
     "amount": "500"
   }
   ```

   Response includes `payment_url` — open that URL for the checkout UI.

## Flow

1. Your backend creates a session via `index.php` (POST JSON).
2. Customer picks a wallet and sends money to your number.
3. Twixo app reads the SMS and posts to `read_sms.php` → stored in `payment_sms`.
4. Customer enters the Transaction ID on `verify.php`.
5. `verify.php` calls `callback.php`, which matches the SMS row, marks it used, and logs success in `payment_logs`.
6. Hook your own balance/order logic inside `callback.php` (marked with comments).

## Branding (required)

Free use from [Twixo](https://twixo.sweez.xyz) requires the Twixo credit to stay visible:

- Do **not** remove `includes/twixo_brand.php`
- Do **not** blank out `TWIXO_BRAND` / `TWIXO_URL` in config
- Every public page calls `twixo_brand_render()` (watermark + anti-removal guard)

See `LICENSE.txt`.

## Files

| File | Purpose |
|------|---------|
| `index.php` | Create session + payment method UI |
| `verify.php` | Instructions + Transaction ID verify |
| `callback.php` | Match SMS / mark paid / merchant hook |
| `read_sms.php` | Twixo app SMS ingest API |
| `success.php` / `cancel.php` | Result pages |
| `config.php` | Core config + branding enforcement |
| `config.local.php` | Your secrets (gitignored) |
| `schema.sql` | Database tables |
| `includes/twixo_brand.php` | Locked Twixo watermark |

## Security checklist

- [ ] Change `SMS_ACCESS_KEY` / `SMS_API_KEY` / `ENCRYPTION_KEY`
- [ ] Use strong DB passwords; never commit `config.local.php`
- [ ] Serve over HTTPS
- [ ] Set `DEBUG_MODE` to `false` in production
- [ ] Keep wallet numbers only in `config.local.php`

---

© Twixo · Developed by [Sweez](https://twixo.sweez.xyz)
