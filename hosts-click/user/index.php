<?php

require dirname(__DIR__) . '/common.php';

$config = hc_da_load_config();
$message = '';
$error = '';
$linkResult = null;

$activeTab = 'preview';
if (isset($_POST['tab']) && is_string($_POST['tab'])) {
    $activeTab = $_POST['tab'] === 'settings' ? 'settings' : 'preview';
} elseif (isset($_GET['tab']) && is_string($_GET['tab'])) {
    $activeTab = $_GET['tab'] === 'settings' ? 'settings' : 'preview';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_settings') {
        $config['api_key'] = trim($_POST['api_key'] ?? '');
        $config['license_status'] = $config['api_key'] ? 'unknown' : 'guest';
        $config['last_checked'] = null;

        if (hc_da_save_config($config)) {
            $message = 'Settings saved.';
        } else {
            $error = 'Failed to save settings.';
        }
    }

    if ($action === 'test_key') {
        $result = hc_da_test_api_key($config);
        $config['last_checked'] = date('c');
        if (! empty($result['error'])) {
            $config['license_status'] = 'invalid';
            $error = $result['error'];
        } else {
            $config['license_status'] = 'valid';
            $message = 'API key is valid.';
        }
        hc_da_save_config($config);
    }

    if ($action === 'clear_api_key') {
        if (hc_da_clear_api_key()) {
            $config = hc_da_load_config();
            $message = 'API key removed.';
        } else {
            $error = 'Failed to remove API key.';
        }
    }

    if ($action === 'uninstall_plugin') {
        $result = hc_da_attempt_uninstall();
        if (! empty($result['error'])) {
            $error = $result['error'];
        } else {
            $message = 'Plugin uninstalled.';
        }
    }

    if ($action === 'create_link') {
        $domain = trim($_POST['domain'] ?? '');
        $ip = trim($_POST['ip'] ?? '');
        $expiresOption = trim($_POST['expires_option'] ?? '');

        $expiresMinutes = 10;
        if (hc_da_has_license($config) && $expiresOption !== '') {
            $expiresMinutes = hc_da_minutes_from_option($expiresOption);
        }

        if ($domain && $ip === '' && ($defaultIp ?? '') !== '') {
            $ip = $defaultIp;
        }

        if ($domain && $ip) {
            $linkResult = hc_da_create_preview_link($config, $domain, $ip, $expiresMinutes);
            if (! empty($linkResult['error'])) {
                $error = $linkResult['error'];
            } else {
                $message = 'Preview link created.';
            }
        } else {
            $error = 'Domain or IP missing.';
        }
    }
}

$user = hc_da_get_user();
$domains = $user ? hc_da_user_domains($user) : [];
$defaultIp = hc_da_default_ip();
$licenseStatus = $config['license_status'] ?? 'guest';
$lastChecked = $config['last_checked'] ?? 'Never';

$previewLinks = [];
$previewLinksError = '';
if (! empty($config['api_key'])) {
    $linksResponse = hc_da_list_preview_links($config);
    if (! empty($linksResponse['error'])) {
        $previewLinksError = $linksResponse['error'];
    } else {
        $previewLinks = $linksResponse['links'] ?? [];
    }
}

$domainMap = [];
foreach ($domains as $row) {
    $domainMap[$row['domain']] = $row['ip'] ?? '';
}

?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Preview link - hostsclick.com</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        :root {
            color-scheme: light;
        }
        body {
            font-family: "Open Sans", "Segoe UI", Arial, sans-serif;
            margin: 0;
            background: #f3f5fb;
            color: #111827;
        }
        .hc-wrap {
            max-width: 1100px;
            margin: 24px auto;
            padding: 24px;
            background: #fdfdff;
            border-radius: 16px;
            box-shadow: 0 20px 40px rgba(15, 23, 42, 0.08);
            border: 1px solid #e5e7eb;
        }
        .hc-title {
            text-align: center;
            font-size: 22px;
            font-weight: 700;
            margin-bottom: 16px;
        }
        .hc-subtitle {
            text-align: center;
            color: #6b7280;
            margin-top: -8px;
            margin-bottom: 20px;
        }
        .hc-tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 16px;
            padding-left: 8px;
        }
        .hc-tab-btn {
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            background: #e0e7ff;
            color: #1d4ed8;
            cursor: pointer;
        }
        .hc-tab-btn.active {
            background: #2563eb;
            color: #fff;
        }
        .hc-pane {
            display: none;
        }
        .hc-pane.active {
            display: block;
        }
        .hc-section {
            background: #fff;
            border: 1px solid #e5e7eb;
            border-radius: 12px;
            padding: 16px;
            margin-bottom: 16px;
        }
        .hc-section h2 {
            margin: 0 0 8px;
            font-size: 18px;
        }
        .hc-muted {
            color: #6b7280;
            font-size: 13px;
        }
        .hc-form-row {
            display: flex;
            flex-wrap: wrap;
            gap: 12px;
            align-items: center;
        }
        .hc-form-row input,
        .hc-form-row select {
            padding: 8px 10px;
            border-radius: 8px;
            border: 1px solid #d1d5db;
            background: #fff;
        }
        .hc-form-row button {
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            background: #2563eb;
            color: #fff;
            font-weight: 600;
            cursor: pointer;
        }
        .hc-actions {
            display: flex;
            flex-wrap: wrap;
            gap: 10px;
            margin-top: 12px;
        }
        .hc-actions button {
            border: none;
            border-radius: 8px;
            padding: 8px 14px;
            font-weight: 600;
            cursor: pointer;
        }
        .hc-btn-secondary {
            background: #2563eb;
            color: #fff;
        }
        .hc-btn-danger {
            background: #ef4444;
            color: #fff;
        }
        .hc-btn-danger-dark {
            background: #b91c1c;
            color: #fff;
        }
        .hc-message {
            margin-bottom: 12px;
            padding: 10px 12px;
            border-radius: 10px;
            font-weight: 600;
        }
        .hc-message.success {
            background: #ecfdf5;
            color: #065f46;
        }
        .hc-message.error {
            background: #fef2f2;
            color: #b91c1c;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 14px;
        }
        th, td {
            padding: 8px;
            border-bottom: 1px solid #e5e7eb;
            text-align: left;
        }
        .hc-preview-box {
            margin-top: 12px;
            padding: 12px;
            background: #f1f5ff;
            border-radius: 10px;
            border: 1px solid #dbeafe;
        }
        .hc-preview-box a {
            color: #1d4ed8;
            text-decoration: none;
            font-weight: 600;
        }
        .hc-preview-box .hc-actions {
            margin-top: 8px;
        }
    </style>
</head>
<body>
    <div class="hc-wrap">
        <div class="hc-title">Preview link - hostsclick.com</div>
        <div class="hc-subtitle">DirectAdmin Integration</div>

        <?php if ($message): ?>
            <div class="hc-message success"><?php echo hc_da_escape($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="hc-message error"><?php echo hc_da_escape($error); ?></div>
        <?php endif; ?>

        <div class="hc-tabs">
            <button class="hc-tab-btn <?php echo $activeTab === 'preview' ? 'active' : ''; ?>" data-tab="preview">Preview link</button>
            <button class="hc-tab-btn <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" data-tab="settings">Settings</button>
        </div>

        <div class="hc-pane <?php echo $activeTab === 'preview' ? 'active' : ''; ?>" id="hc-pane-preview">
            <section class="hc-section">
                <h2>Domains</h2>
                <p class="hc-muted">Create a preview link for a domain on this server.</p>
                <form method="post" class="hc-form-row">
                    <input type="hidden" name="action" value="create_link">
                    <input type="hidden" name="tab" value="preview">

                    <label class="hc-muted" style="font-weight:600;">Domain</label>
                    <input id="hc-domain-input" type="text" name="domain" list="hc-domain-list" placeholder="Select or type domain" style="min-width: 240px;">
                    <datalist id="hc-domain-list">
                        <?php foreach ($domains as $row): ?>
                            <option value="<?php echo hc_da_escape($row['domain']); ?>"></option>
                        <?php endforeach; ?>
                    </datalist>

                    <label class="hc-muted" style="font-weight:600;">Target IP</label>
                    <input id="hc-ip-input" type="text" name="ip" value="<?php echo hc_da_escape($defaultIp); ?>" style="width: 180px;">

                    <?php if (hc_da_has_license($config)): ?>
                        <label class="hc-muted" style="font-weight:600;">Expiry</label>
                        <select name="expires_option">
                            <option value="1h">1 hour</option>
                            <option value="6h">6 hours</option>
                            <option value="12h">12 hours</option>
                            <option value="24h">24 hours</option>
                            <option value="7d">7 days</option>
                            <option value="30d">30 days</option>
                        </select>
                    <?php endif; ?>

                    <div style="margin-left:auto;">
                        <button type="submit">Generate Preview Link</button>
                    </div>
                </form>

                <?php if (empty($domains)): ?>
                    <div class="hc-muted" style="margin-top: 8px;">No domains detected for this user. Type a domain manually.</div>
                <?php endif; ?>

                <?php if ($linkResult && empty($linkResult['error'])): ?>
                    <div class="hc-preview-box">
                        <div><strong>Preview URL:</strong> <a href="<?php echo hc_da_escape($linkResult['preview_url'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"><?php echo hc_da_escape($linkResult['preview_url'] ?? ''); ?></a></div>
                        <div class="hc-muted">Expires: <?php echo hc_da_escape($linkResult['expires_at'] ?? ''); ?></div>
                    </div>
                <?php endif; ?>
            </section>

            <section class="hc-section">
                <h2>All Preview Links</h2>
                <p class="hc-muted">All preview links created with this API key.</p>

                <?php if (empty($config['api_key'])): ?>
                    <div class="hc-muted">Set an API key to view preview links.</div>
                <?php elseif ($previewLinksError): ?>
                    <div class="hc-message error"><?php echo hc_da_escape($previewLinksError); ?></div>
                <?php elseif (empty($previewLinks)): ?>
                    <div class="hc-muted">No preview links found.</div>
                <?php else: ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Preview URL</th>
                                <th>Domain</th>
                                <th>IP</th>
                                <th>Expires</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($previewLinks as $link): ?>
                                <tr>
                                    <td><a href="<?php echo hc_da_escape($link['preview_url'] ?? '#'); ?>" target="_blank" rel="noopener noreferrer"><?php echo hc_da_escape($link['preview_url'] ?? ''); ?></a></td>
                                    <td><?php echo hc_da_escape($link['target_host'] ?? ''); ?></td>
                                    <td><?php echo hc_da_escape($link['target_ip'] ?? ''); ?></td>
                                    <td><?php echo hc_da_escape($link['expires_at'] ?? ''); ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </section>

        </div>

        <div class="hc-pane <?php echo $activeTab === 'settings' ? 'active' : ''; ?>" id="hc-pane-settings">
            <section class="hc-section">
                <h2>API License</h2>
                <form method="post" style="margin: 0;">
                    <input type="hidden" name="action" value="save_settings">
                    <input type="hidden" name="tab" value="settings">
                    <label for="api_key" style="font-weight: 600;">API Key</label><br>
                    <div class="hc-form-row" style="margin-top: 8px;">
                        <input id="api_key" name="api_key" type="password" value="<?php echo hc_da_escape($config['api_key']); ?>" placeholder="Enter API key" style="width: 420px; max-width: 100%;">
                        <button type="submit">Save</button>
                    </div>
                </form>

                <div class="hc-actions">
                    <form method="post" style="margin: 0;">
                        <input type="hidden" name="action" value="test_key">
                        <input type="hidden" name="tab" value="settings">
                        <button type="submit" class="hc-btn-secondary">Test API Key</button>
                    </form>
                    <form method="post" style="margin: 0;" onsubmit="return confirm('Remove the saved API key?');">
                        <input type="hidden" name="action" value="clear_api_key">
                        <input type="hidden" name="tab" value="settings">
                        <button type="submit" class="hc-btn-danger">Remove API Key</button>
                    </form>
                    <form method="post" style="margin: 0;" onsubmit="return confirm('Uninstall the hosts.click plugin from this server?');">
                        <input type="hidden" name="action" value="uninstall_plugin">
                        <input type="hidden" name="tab" value="settings">
                        <button type="submit" class="hc-btn-danger-dark">Uninstall Plugin</button>
                    </form>
                </div>

                <div style="margin-top: 10px;">
                    <strong>Status:</strong> <?php echo hc_da_escape($licenseStatus); ?><br>
                    <strong>Last checked:</strong> <?php echo hc_da_escape($lastChecked); ?>
                </div>
            </section>
        </div>
    </div>

    <script>
        (function () {
            const buttons = document.querySelectorAll('.hc-tab-btn');
            const panes = document.querySelectorAll('.hc-pane');

            buttons.forEach((button) => {
                button.addEventListener('click', () => {
                    const tab = button.dataset.tab;
                    buttons.forEach((btn) => btn.classList.toggle('active', btn === button));
                    panes.forEach((pane) => pane.classList.toggle('active', pane.id === `hc-pane-${tab}`));
                });
            });

            const domainMap = <?php echo json_encode($domainMap, JSON_UNESCAPED_SLASHES); ?>;
            const domainInput = document.getElementById('hc-domain-input');
            const ipInput = document.getElementById('hc-ip-input');
            if (domainInput && ipInput) {
                const updateIp = () => {
                    const value = domainInput.value.trim();
                    if (domainMap[value]) {
                        ipInput.value = domainMap[value];
                    }
                };
                domainInput.addEventListener('change', updateIp);
                domainInput.addEventListener('input', updateIp);
            }
        })();
    </script>
</body>
</html>
