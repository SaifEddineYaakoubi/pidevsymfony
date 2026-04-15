<?php

declare(strict_types=1);

require dirname(__DIR__).'/vendor/autoload.php';

use Doctrine\DBAL\DriverManager;

$dotenvPath = dirname(__DIR__).'/.env';
$params = [
    'url' => getenv('DATABASE_URL') ?: null,
];

if (!$params['url'] && file_exists($dotenvPath)) {
    $env = file_get_contents($dotenvPath);
    if (preg_match('/^DATABASE_URL=(.*)$/m', $env, $m)) {
        $params['url'] = trim($m[1]);
        $params['url'] = trim($params['url'], "\"'");
    }
}

if (!$params['url']) {
    fwrite(STDERR, "DATABASE_URL not found in env or .env\n");
    exit(1);
}

$conn = DriverManager::getConnection($params);

$sql = <<<'SQL'
SHOW CREATE TABLE parcelle
SQL;

$row = $conn->fetchAssociative($sql);
if (!$row) {
    fwrite(STDERR, "No result (table parcelle missing?)\n");
    exit(2);
}

// MySQL returns columns: Table, Create Table
foreach ($row as $k => $v) {
    echo "== {$k} ==\n";
    echo $v."\n\n";
}

