<?php

namespace App;


use App\Commands\CommandProcessor;
use App\Models\Member;
use DigitalStar\vk_api\Execute;
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
     * @var Execute|vk_api
     */
    private $client;

    /**
     * Bot groupid
     *
     * @var integer
     */
    private $groupid;

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
     * @var CommandProcessor
     */
    private static $cmdprocessor;

    /**
     * Members cache
     *
     * @var array
     */
    private $members = [];

    /**
     * Bot constructor.
     * @throws \DigitalStar\vk_api\VkApiException
     */
    public function __construct()
    {
        $this->token = $_ENV['BOT_TOKEN'];
        $this->groupid = $_ENV['BOT_GROUPID'];
        $this->client = vk_api::create($this->token, $_ENV['VKAPI_VERSION']);
        $this->client = new Execute($this->client);
        $this->lp = new LongPoll($this->client);
        self::$cmdprocessor = new CommandProcessor($this->client, $this->lp);
        $this->setupDatabaseConnection();
    }

    public static function GetCommandProcessor()
    {
        return self::$cmdprocessor;
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
        $this->capsule->bootEloquent();
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
                $table->boolean("admin")->default(false);
                $table->timestamps();
                echo "Members table created\n";
            });
        else echo "Members table already created\n";

//        Creating Strike schema
        if (!Capsule::schema()->hasTable("strikes"))
            Capsule::schema()->create("strikes", function (Blueprint $table) {
                $table->id();
                $table->foreignId("member_id");
                $table->foreign('member_id')->references('id')->on('members');
                $table->string("comment")->nullable();
                $table->timestamps();
                echo "Strikes table created\n";
            });
        else echo "Strikes table already created\n";
    }

    /**
     * Drop all tables created by bot
     */
    public function dropTables()
    {
        Capsule::schema()->dropIfExists("members");
        Capsule::schema()->dropIfExists("strikes");
    }

    /**
     * Collecting all group members and inserting them in database
     *
     * @throws \DigitalStar\vk_api\VkApiException
     */
    public function collectAllMembers()
    {
//        Requesting all members
        $members = $this->client->request("groups.getMembers", [
            "group_id" => $this->groupid,
            "sort"     => "time_asc",
            "fields"   => "sex",
        ])["items"];

//        Requesting all admins
        $admins = $this->client->request("groups.getMembers", [
            "group_id" => $this->groupid,
            "filter"   => "managers",
        ])["items"];

        $dbinsertion = [];
        $dbselection = [];

//        Quering members in database
        foreach ($members as $member) {
            $dbselection[] = $member["id"];
        }
        $alreadyDB = Member::query()->whereIn("vk", $dbselection)->get("vk");

//        Inserting new users
        foreach ($members as $member) {
            if ($alreadyDB->where("vk", $member["id"])->isNotEmpty())
                continue;
            $is_admin = false;
            foreach ($admins as $admin)
                if ($admin["id"] == $member["id"] && array_search($admin["role"], ["moderator", "creator", "administrator"])) {
                    $is_admin = true;
                    break;
                }

            $dbinsertion[] = [
                "vk"         => $member["id"],
                "first_name" => $member["first_name"],
                "last_name"  => $member["last_name"],
                "sex"        => Utils::identifySex($member["sex"]),
                "admin"      => $is_admin,
            ];
        }

        if (sizeof($dbinsertion) != 0)
            Member::insert($dbinsertion);

        $alreadyDB = Member::query()->where("status", "active")->get();

        foreach ($alreadyDB as $member) {
            $this->members[$member->vk] = $member;
        }
    }

    /**
     * Method to run bot
     */
    public function run()
    {
        echo "Generating databases..\n";
        $this->generateTables();
        $this->collectAllMembers();
        echo "@BigDev bot running..\n";
        $this->lp->listen(function () {
            $this->lp->initVars($peer_id, $message, $payload, $user_id, $type, $data);
//
//            В типе message_new если есть action то можно получить тип события в беседе
//            if ($data->object->action->type == 'chat_invite_user' || $data->object->action->type == 'chat_invite_user_by_link')
//
//            Также можно получить события из группы: https://vk.com/dev/groups_events
//            В $data->object находится объект события из группы
//
            $member = $this->members[$user_id];
            echo $member->full_name . " " . $message . "\n";
        });
    }
}