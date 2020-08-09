<?php

namespace App;


use DigitalStar\vk_api\LongPoll;
use DigitalStar\vk_api\vk_api;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;

/**
 * Main class that initializes bot
 * @package App
 */
class Bot
{
    /**
     * Token for VK API
     *
     * @var string
     */
    private $token;

    /**
     * VK API client
     *
     * @var vk_api
     */
    private $client;

    /**
     * VK Long-pool client
     *
     * @var LongPoll
     */
    private $lp;

    /**
     * Eloquent database manager
     *
     * @var Capsule
     */
    private $capsule;

    /**
     * Bot constructor.
     * @throws \DigitalStar\vk_api\VkApiException
     */
    public function __construct()
    {
        $this->token = $_ENV['BOT_TOKEN'];
        $this->client = vk_api::create($this->token, $_ENV['VKAPI_TOKEN']);
//        $this->lp = new LongPoll($this->client);
        $this->setupDatabaseConnection();
    }

    /**
     * Setups Eloquent database connection
     */
    public function setupDatabaseConnection()
    {
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

    /**
     * Generates tables in database
     */
    public function generateTables()
    {
//        Creating Member schema
        if (!Capsule::schema()->hasTable("members"))
            Capsule::schema()->create("members", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger("vk")->unique();
                $table->string("first_name");
                $table->string("last_name");
                $table->enum("sex", ["male", "female", "undefined"])->default("undefined");
                $table->unsignedBigInteger("messages")->default(0);
                $table->enum("status", ["active", "banned", "striked"])->default("active");
                $table->timestamps();
            });

//        Creating Strike schema
        if (!Capsule::schema()->hasTable("strikes"))
            Capsule::schema()->create("strikes", function (Blueprint $table) {
                $table->id();
                $table->foreignId("member_id");
                $table->foreign('member_id')->references('id')->on('members');
                $table->string("comment")->nullable();
                $table->timestamps();
            });
    }

    /**
     * Method to run bot
     */
    public function run()
    {
        echo "@BigDev bot running..";
    }
}