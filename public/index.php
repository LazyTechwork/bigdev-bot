<?php

declare(strict_types=1);

use App\Bot;

require __DIR__ . '/../vendor/autoload.php';

$bot = new Bot();
$bot->run();
