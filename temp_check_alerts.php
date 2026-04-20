<?php
require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;
use App\Kernel;

if (class_exists(Dotenv::class)) {
    (new Dotenv())->loadEnv(__DIR__ . '/.env');
}

$kernel = new Kernel('dev', true);
$container = $kernel->getContainer();
$service = $container->get('App\\Service\\StockAlertService');
$result = $service->checkAndSendAlerts();
echo json_encode($result, JSON_PRETTY_PRINT) . PHP_EOL;
