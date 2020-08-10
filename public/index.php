<?php

declare(strict_types=1);

use App\CLI;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

// Creating dotenv
$config = Dotenv::createImmutable(__DIR__ . '/../');
$config->load();
$config->required('BOT_TOKEN')->notEmpty();
$config->required('USER_TOKEN')->notEmpty();
$config->required('VKAPI_VERSION')->notEmpty();
$config->required('BOT_GROUPID')->isInteger();
$config->required('DB_DRIVER')->notEmpty();
$config->required('DB_HOST')->notEmpty();
$config->required('DB_NAME')->notEmpty();
$config->required('DB_USER')->notEmpty();
$config->required('DB_PASS');

$cli = new CLI();
$cli->process();
$cli->execute();
