# Hosts.click DirectAdmin Plugin (Evolution)

Hosts.click adds **temporary HTTPS preview links** for your hosted domains. This plugin integrates Hosts.click into
DirectAdmin **user** accounts running the **Evolution** skin.

It also injects a “Temporary URL” action in the Domains UI (reversible, without modifying core DirectAdmin files).

## Requirements

- DirectAdmin with the **Evolution** skin
- Root shell access
- Hosts.click API base URL: `https://hostsclick.com` (HTTPS only)

## Quick Install (Recommended)

Run as `root`:

```bash
curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-installer.sh | bash
```

## Quick Uninstall (Recommended)

Run as `root`:

```bash
curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-installer.sh | bash -s -- --uninstall
```

## Download Installer

Install:

```bash
curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-installer.sh -o hosts-click-directadmin-installer.sh
bash hosts-click-directadmin-installer.sh
```

Uninstall:

```bash
bash hosts-click-directadmin-installer.sh --uninstall
```

## Download Plugin (tar.gz)

```bash
curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-plugin.tar.gz -o hosts-click-directadmin-plugin.tar.gz
```

## Features

- “Preview Links” UI:
  - Create preview links from domain + IP
  - List preview links
- “Settings” UI:
  - Save API key
  - Test API key / validate license
  - Clear API key
- Domains page injection:
  - Adds “Temporary URL” beside existing domain actions
- Guest mode (no key) and Licensed mode (key) flows.

## Install

1. Clone this repo or upload it to your server.
2. Run the installer as root:

```bash
cd /path/to/hosts.click.directadmin
sudo bash install.sh
```

The installer will:

- Copy plugin files into:
  - `/usr/local/directadmin/plugins/hosts-click`
- Create (or preserve) the config file:
  - `/usr/local/directadmin/plugins/hosts-click/config.json`
- Inject a safe script tag into:
  - `/usr/local/directadmin/data/skins/evolution/custom/footer.html`

That script powers the Domains page “Temporary URL” injection.

## Uninstall

```bash
cd /path/to/hosts.click.directadmin
sudo bash uninstall.sh
```

## Uninstall (From Downloaded tar.gz)

If the archive contains `directadmin-plugin/`:

```bash
tmp=$(mktemp -d) && curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-plugin.tar.gz -o "$tmp/plugin.tar.gz" && tar -xzf "$tmp/plugin.tar.gz" -C "$tmp" && bash "$tmp/directadmin-plugin/uninstall.sh"
```

If the archive extracts files into the temp directory root (common):

```bash
tmp=$(mktemp -d) && curl -fsSL https://hostsclick.com/downloads/hosts-click-directadmin-plugin.tar.gz -o "$tmp/plugin.tar.gz" && tar -xzf "$tmp/plugin.tar.gz" -C "$tmp" && bash "$tmp/uninstall.sh"
```

## Configuration

Use the plugin **Settings** tab to set your API key, or edit:

```text
/usr/local/directadmin/plugins/hosts-click/config.json
```

Example:

```json
{
  "api_base_url": "https://hostsclick.com",
  "api_key": "YOUR_KEY",
  "license_status": "valid",
  "last_checked": "2026-01-27T00:00:00Z"
}
```

## Guest vs Licensed Mode

- Guest mode (no valid key): uses `POST /api/guest-preview-links` with a fixed **10-minute** expiry.
- Licensed mode (valid key): uses `POST /api/preview-links` (header `X-API-Key`) and supports expiry options.

### Required API Endpoints

- Licensed:
  - `POST /api/preview-links` (`X-API-Key`)
- Guest:
  - `POST /api/guest-preview-links`

If `guest-preview-links` is not available on your API base URL, guest mode will fail.

## How Domain/IP Detection Works

- Domains are read from:
  - `/usr/local/directadmin/data/users/<user>/domains.list`
- IPs are read from:
  - `/usr/local/directadmin/data/users/<user>/domains/<domain>.conf`
- Fallback:
  - admin IP list (when domain conf is missing)

## Updating

To update, pull new changes and re-run the installer:

```bash
cd /path/to/hosts.click.directadmin
git pull
sudo bash install.sh
```

## Troubleshooting

- Plugin menu missing:
  - Confirm the plugin directory exists:

```bash
ls -la /usr/local/directadmin/plugins/hosts-click
```

- Domain injection not showing:
  - Confirm the injection script is present:
    - `/usr/local/directadmin/plugins/hosts-click/assets/domains-inject.js`
  - Confirm it is referenced in:
    - `/usr/local/directadmin/data/skins/evolution/custom/footer.html`

## Security Notes

- Your API key is stored in a JSON config file on the server.
- The installer attempts to set ownership/perms to DirectAdmin’s `diradmin` user when available.

## License / Support

Issues and PRs are welcome. Include:

- DirectAdmin version
- Skin (Evolution)
- Any relevant logs/errors
- The exact install/uninstall commands you ran
