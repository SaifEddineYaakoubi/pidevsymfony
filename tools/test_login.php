<?php
// tools/test_login.php
$base = 'http://127.0.0.1:8000';
$cookie = sys_get_temp_dir() . '/sf_cookie.txt';

$ch = curl_init($base . '/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
$html = curl_exec($ch);
if ($html === false) { echo "Error fetching login page: " . curl_error($ch) . PHP_EOL; exit(1); }

// Extract CSRF token
if (!preg_match('/name="_csrf_token" value="([^"]+)"/', $html, $m)) {
    echo "CSRF token not found in login page" . PHP_EOL;
    exit(1);
}
$token = $m[1];

// Post login
$post = [
    'email' => 'admin@smartfarm.tn',
    'password' => 'adminpass',
    '_csrf_token' => $token
];

$ch = curl_init($base . '/admin/login');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($post));
$resp = curl_exec($ch);
if ($resp === false) { echo "Login POST failed: " . curl_error($ch) . PHP_EOL; exit(1); }

// Request debug route
$ch = curl_init($base . '/debug/me');
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie);
curl_setopt($ch, CURLOPT_COOKIEFILE, $cookie);
$debug = curl_exec($ch);
if ($debug === false) { echo "Debug request failed: " . curl_error($ch) . PHP_EOL; exit(1); }

echo $debug . PHP_EOL;

