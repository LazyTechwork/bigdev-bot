<?php

namespace App;


use Illuminate\Database\Capsule\Manager as Capsule;

class Bot
{
    private $token;
    private $client;
    private $capsule;

    public function __construct()
    {
        $this->token = $_ENV['BOT_TOKEN'];
        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver'    => $_ENV['DB_DRIVER'],
            'host'      => $_ENV['DB_HOST'],
            'database'  => $_ENV['DB_NAME'],
            'username'  => $_ENV['DB_USER'],
            'password'  => $_ENV['DB_PASS'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $this->capsule->setAsGlobal();
    }

    public function run()
    {
        echo "@BigDev bot running..";
    }
}