<?php

define('HC_DA_CONFIG_PATH', '/usr/local/directadmin/plugins/hosts-click/config.json');

define('HC_DA_DEFAULT_CONFIG', [
    'api_base_url' => 'https://hostsclick.com',
    'api_key' => '',
    'license_status' => 'guest',
    'last_checked' => null,
]);

function hc_da_init_request(): void
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;

    $queryString = getenv('QUERY_STRING');
    if ($queryString !== false && $queryString !== '' && empty($_GET)) {
        parse_str($queryString, $_GET);
    }

    $postPayload = getenv('POST');
    if ($postPayload !== false && $postPayload !== '' && empty($_POST)) {
        parse_str($postPayload, $_POST);
    }

    $method = getenv('REQUEST_METHOD');
    if ($method && ! isset($_SERVER['REQUEST_METHOD'])) {
        $_SERVER['REQUEST_METHOD'] = $method;
    }
}

function hc_da_load_config(): array
{
    if (! file_exists(HC_DA_CONFIG_PATH)) {
        return HC_DA_DEFAULT_CONFIG;
    }

    $raw = file_get_contents(HC_DA_CONFIG_PATH);
    if ($raw === false) {
        return HC_DA_DEFAULT_CONFIG;
    }

    $decoded = json_decode($raw, true);
    if (! is_array($decoded)) {
        return HC_DA_DEFAULT_CONFIG;
    }

    return array_merge(HC_DA_DEFAULT_CONFIG, $decoded);
}

function hc_da_save_config(array $config): bool
{
    $dir = dirname(HC_DA_CONFIG_PATH);
    if (! is_dir($dir)) {
        mkdir($dir, 0755, true);
    }

    $payload = json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    if ($payload === false) {
        return false;
    }

    return file_put_contents(HC_DA_CONFIG_PATH, $payload, LOCK_EX) !== false;
}

function hc_da_clear_api_key(): bool
{
    $config = hc_da_load_config();
    $config['api_key'] = '';
    $config['license_status'] = 'guest';
    $config['last_checked'] = null;

    return hc_da_save_config($config);
}

function hc_da_escape(string $value): string
{
    return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
}

hc_da_init_request();

function hc_da_http_request(string $method, string $url, ?array $payload = null, array $headers = [], bool $json = true): array
{
    $ch = curl_init($url);
    if ($ch === false) {
        return ['error' => 'Unable to initialize HTTP client.'];
    }

    $headerLines = array_merge([
        'Accept: application/json',
    ], $headers);

    $options = [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_HTTPHEADER => $headerLines,
        CURLOPT_TIMEOUT => 15,
    ];

    if ($payload !== null) {
        if ($json) {
            $body = json_encode($payload);
            $headerLines[] = 'Content-Type: application/json';
        } else {
            $body = http_build_query($payload);
            $headerLines[] = 'Content-Type: application/x-www-form-urlencoded';
        }
        $options[CURLOPT_HTTPHEADER] = $headerLines;
        $options[CURLOPT_POSTFIELDS] = $body;
    }

    curl_setopt_array($ch, $options);

    $response = curl_exec($ch);
    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);

    if ($response === false) {
        return ['error' => $error ?: 'Request failed.'];
    }

    $decoded = json_decode($response, true);
    if (! is_array($decoded)) {
        return ['error' => 'Invalid response from API.', 'status' => $status, 'raw' => $response];
    }

    $decoded['status'] = $status;
    return $decoded;
}

function hc_da_post_json(string $url, array $payload, array $headers = []): array
{
    return hc_da_http_request('POST', $url, $payload, $headers, true);
}

function hc_da_post_form(string $url, array $payload, array $headers = []): array
{
    return hc_da_http_request('POST', $url, $payload, $headers, false);
}

function hc_da_get_json(string $url, array $headers = []): array
{
    return hc_da_http_request('GET', $url, null, $headers);
}

function hc_da_delete_request(string $url, array $headers = []): array
{
    return hc_da_http_request('DELETE', $url, null, $headers);
}

function hc_da_get_user(): string
{
    $candidates = [
        getenv('USERNAME'),
        getenv('USER'),
        getenv('REMOTE_USER'),
        $_SERVER['DA_USER'] ?? null,
        $_SERVER['USERNAME'] ?? null,
    ];

    foreach ($candidates as $candidate) {
        if (is_string($candidate) && $candidate !== '') {
            return $candidate;
        }
    }

    return '';
}

function hc_da_user_domains(string $user): array
{
    $domainsFile = "/usr/local/directadmin/data/users/{$user}/domains.list";
    if (! file_exists($domainsFile)) {
        return [];
    }

    $lines = file($domainsFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if (! is_array($lines)) {
        return [];
    }

    $domains = [];
    foreach ($lines as $domain) {
        $domain = trim($domain);
        if ($domain === '') {
            continue;
        }

        $domains[] = [
            'domain' => $domain,
            'ip' => hc_da_domain_ip($user, $domain),
        ];
    }

    return $domains;
}

function hc_da_domain_ip(string $user, string $domain): string
{
    $confPath = "/usr/local/directadmin/data/users/{$user}/domains/{$domain}.conf";
    if (file_exists($confPath)) {
        $confLines = file($confPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($confLines)) {
            foreach ($confLines as $line) {
                if (strpos($line, 'ip=') === 0) {
                    return trim(substr($line, 3));
                }
            }
        }
    }

    $ipList = '/usr/local/directadmin/data/admin/ip.list';
    if (file_exists($ipList)) {
        $lines = file($ipList, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines) && count($lines) > 0) {
            return trim($lines[0]);
        }
    }

    return '';
}

function hc_da_default_ip(): string
{
    $ipList = '/usr/local/directadmin/data/admin/ip.list';
    if (file_exists($ipList)) {
        $lines = file($ipList, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if (is_array($lines) && count($lines) > 0) {
            return trim($lines[0]);
        }
    }

    return '';
}

function hc_da_minutes_from_option(string $option): int
{
    $map = [
        '1h' => 60,
        '6h' => 360,
        '12h' => 720,
        '24h' => 1440,
        '7d' => 10080,
        '30d' => 43200,
    ];

    return $map[$option] ?? 10;
}

function hc_da_has_license(array $config): bool
{
    return ! empty($config['api_key']) && ($config['license_status'] === 'valid');
}

function hc_da_create_preview_link(array $config, string $domain, string $ip, int $expiresMinutes): array
{
    $payload = [
        'hostname' => $domain,
        'ip' => $ip,
        'expires_in' => $expiresMinutes,
    ];

    $headers = [];
    $endpoint = rtrim($config['api_base_url'], '/') . '/api/preview-links';

    if (! empty($config['api_key'])) {
        $headers[] = 'X-API-Key: ' . $config['api_key'];
        return hc_da_post_json($endpoint, $payload, $headers);
    }

    $endpoint = rtrim($config['api_base_url'], '/') . '/api/guest-preview-links';
    return hc_da_post_form($endpoint, $payload, $headers);
}

function hc_da_list_preview_links(array $config): array
{
    if (empty($config['api_key'])) {
        return ['error' => 'API key required.'];
    }

    $endpoint = rtrim($config['api_base_url'], '/') . '/api/preview-links';
    return hc_da_get_json($endpoint, ['X-API-Key: ' . $config['api_key']]);
}

function hc_da_test_api_key(array $config): array
{
    if (empty($config['api_key'])) {
        return ['error' => 'API key is empty.'];
    }

    $response = hc_da_post_json(rtrim($config['api_base_url'], '/') . '/api/preview-links', [
        'hostname' => 'example.com',
        'ip' => '1.1.1.1',
        'expires_in' => 1,
    ], ['X-API-Key: ' . $config['api_key']]);

    if (! empty($response['error'])) {
        return $response;
    }

    $previewUrl = $response['preview_url'] ?? null;
    if ($previewUrl) {
        $host = parse_url($previewUrl, PHP_URL_HOST);
        if ($host) {
            $token = explode('.', $host)[0];
            if ($token) {
                hc_da_delete_request(rtrim($config['api_base_url'], '/') . '/api/preview-links/' . $token, [
                    'X-API-Key: ' . $config['api_key'],
                ]);
            }
        }
    }

    return ['status' => 200];
}

function hc_da_attempt_uninstall(): array
{
    $script = '/usr/local/directadmin/plugins/hosts-click/uninstall.sh';
    if (! file_exists($script)) {
        return ['error' => 'Uninstall script not found.'];
    }

    if (! is_executable($script)) {
        return ['error' => 'Uninstall script is not executable.'];
    }

    if (function_exists('posix_geteuid') && posix_geteuid() !== 0) {
        return ['error' => 'Uninstall requires root. Run uninstall.sh on the server.'];
    }

    $output = [];
    $exitCode = 0;
    exec(escapeshellcmd($script) . ' 2>&1', $output, $exitCode);

    if ($exitCode !== 0) {
        return ['error' => 'Uninstall failed: ' . implode("\n", $output)];
    }

    return ['status' => 200];
}
