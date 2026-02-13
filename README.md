# Hosts.click DirectAdmin Plugin (Evolution)

This plugin adds Hosts.click preview link generation inside DirectAdmin **user** accounts (Evolution skin only).

## Features

- **Preview links tab**: create preview links with domain + IP, plus list all preview links.
- **Settings tab**: save, test, and remove API key.
- **Domains page injection**: adds a “Temporary URL” button alongside existing actions.
- **Licensed servers**: expiry options (1h, 6h, 12h, 24h, 7d, 30d).
- **Guest mode**: default expiry of 10 minutes (uses guest API endpoint).

## Requirements

- DirectAdmin (Evolution skin)
- Access to `/usr/local/directadmin/plugins`
- Hosts.click API base URL: `https://hostsclick.com` (HTTPS only)

## Install

```bash
sudo /path/to/directadmin-plugin/install.sh
```

This will:
- Copy plugin files into `/usr/local/directadmin/plugins/hosts-click`
- Create `/usr/local/directadmin/plugins/hosts-click/config.json`
- Inject a safe script tag into `/usr/local/directadmin/data/skins/evolution/custom/footer.html`

## Uninstall

```bash
sudo /path/to/directadmin-plugin/uninstall.sh
```

## Configuration

Set your API key in the **Settings** tab, or edit:

```
/usr/local/directadmin/plugins/hosts-click/config.json
```

Example:
```
{
  "api_base_url": "https://hostsclick.com",
  "api_key": "YOUR_KEY",
  "license_status": "valid",
  "last_checked": "2026-01-27T00:00:00Z"
}
```

## Guest vs Licensed

- **Guest mode** (no valid API key): uses `POST /api/guest-preview-links` with **10‑minute** expiry.
- **Licensed mode** (valid API key): uses `POST /api/preview-links` and allows expiry options.

### Required API endpoints

- **Licensed**
  - `POST /api/preview-links` (header `X-API-Key`)
- **Guest**
  - `POST /api/guest-preview-links`

## Notes

- Domains are read from `/usr/local/directadmin/data/users/<user>/domains.list`.
- IPs are read from `/usr/local/directadmin/data/users/<user>/domains/<domain>.conf` (fallback: admin IP list).
- The Domains page injection is reversible and doesn’t touch core skin files.
