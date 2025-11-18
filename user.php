<?php
// Important! This file must be run only in an isolated container

date_default_timezone_set('UTC');

function ip_in_cidr(string $ip, string $cidr): bool {
    if (strpos($cidr, '/') === false) return false;
    [$subnet, $prefix] = explode('/', $cidr, 2);
    if (!filter_var($ip, FILTER_VALIDATE_IP)) return false;
    if (!filter_var($subnet, FILTER_VALIDATE_IP)) return false;
    $prefix = (int)$prefix;
    $ipBin = @inet_pton($ip);
    $subnetBin = @inet_pton($subnet);
    if ($ipBin === false || $subnetBin === false) return false;
    if (strlen($ipBin) !== strlen($subnetBin)) return false;
    $maxBits = strlen($ipBin) * 8;
    if ($prefix < 0 || $prefix > $maxBits) return false;
    $fullBytes = intdiv($prefix, 8);
    $remainingBits = $prefix % 8;
    if ($fullBytes > 0 && substr($ipBin, 0, $fullBytes) !== substr($subnetBin, 0, $fullBytes)) return false;
    if ($remainingBits === 0) return true;
    $mask = (0xFF << (8 - $remainingBits)) & 0xFF;
    $ipByte = ord($ipBin[$fullBytes]);
    $subnetByte = ord($subnetBin[$fullBytes]);
    return ($ipByte & $mask) === ($subnetByte & $mask);
}

function ip_in_any(string $ip, array $cidrs): bool {
    foreach ($cidrs as $cidr) {
        if (ip_in_cidr($ip, $cidr)) return true;
    }
    return false;
}

// Get client IP safely. Trust XFF only if REMOTE_ADDR is a trusted proxy. Returns IP or null.
function get_client_ip(array $trustedProxyCidrs = []): ?string {
    $remote = $_SERVER['REMOTE_ADDR'] ?? null;
    if ($remote && filter_var($remote, FILTER_VALIDATE_IP)) {
        // If REMOTE_ADDR is a trusted proxy, try XFF/X-Real-IP
        if (!empty($trustedProxyCidrs) && ip_in_any($remote, $trustedProxyCidrs)) {
            if (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
                $parts = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
                $candidate = $parts[0] ?? null;
                if ($candidate && filter_var($candidate, FILTER_VALIDATE_IP)) return $candidate;
            }
            if (!empty($_SERVER['HTTP_X_REAL_IP'])) {
                $xr = trim($_SERVER['HTTP_X_REAL_IP']);
                if ($xr && filter_var($xr, FILTER_VALIDATE_IP)) return $xr;
            }
        }
        // Otherwise return validated REMOTE_ADDR
        return $remote;
    }
    return null;
}

// CONFIG: add trusted proxy/load-balancer CIDRs
$trustedProxyCidrs = ['127.0.0.1/32', '::1/128']; // add nginx/load-balancer CIDRs here

$fb_log = '/var/log/honeypot.log'; // bind-mount this on host: /var/log/honeypot.log

$ip = get_client_ip($trustedProxyCidrs);
$ts = date('Y-m-d H:i:s');
$uri = $_SERVER['REQUEST_URI'] ?? '/user.php';

// Log only if IP is valid
if ($ip !== null) {
    $line = sprintf("%s honeypot: %s uri=\"%s\"\n", $ts, $ip, addslashes($uri));
    @file_put_contents($fb_log, $line, FILE_APPEND | LOCK_EX);
}

// Return a simple fake login form (no auth)
http_response_code(200);
$showError = (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST');
?>
<!doctype html>
<html lang="sv">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login</title>
  <style>body{font-family:Arial,Helvetica,sans-serif;max-width:640px;margin:40px auto;padding:10px}label{display:block;margin:8px 0}</style>
</head>
<body>
  <h1>Admin login</h1>
  <form method="post" action="/user.php">
    <label>Username: <input name="username" type="text" autocomplete="off"></label>
    <label>Password: <input name="password" type="password" autocomplete="off"></label>
    <?php if ($showError): ?>
    <p style="color:gray">Invalid email or password.</p>
    <?php endif; ?>
    <button type="submit">Login</button>
  </form>
</body>
</html>