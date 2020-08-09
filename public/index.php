<?php

declare(strict_types=1);

use App\Bot;
use Dotenv\Dotenv;

require __DIR__ . '/../vendor/autoload.php';

$config = Dotenv::createImmutable(__DIR__ . '/../');
$config->load();
$config->required('BOT_TOKEN')->notEmpty();
$config->required('DB_DRIVER')->notEmpty();
$config->required('DB_HOST')->notEmpty();
$config->required('DB_NAME')->notEmpty();
$config->required('DB_USER')->notEmpty();
$config->required('DB_PASS');

$bot = new Bot();
$bot->run();
