<?php

namespace App;


use App\Commands\CommandArgument;
use App\Commands\CommandProcessor;
use App\Models\Member;
use DigitalStar\vk_api\Execute;
use DigitalStar\vk_api\LongPoll;
use DigitalStar\vk_api\vk_api;
use DigitalStar\vk_api\VkApiException;
use Illuminate\Database\Capsule\Manager as Capsule;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;

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
    private vk_api $client;

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
    private LongPoll $lp;

    /**
     * @var CommandProcessor
     */
    private static CommandProcessor $cmdprocessor;

    /**
     * Members cache
     *
     * @var Member[]
     */
    private array $members = [];

    /**
     * @var string[]
     */
    private array $commands;

    /**
     * Bot constructor.
     * @throws VkApiException
     */
    public function __construct()
    {
        $this->token = $_ENV['BOT_TOKEN'];
        $this->groupid = $_ENV['BOT_GROUPID'];

        $this->commands = [
            ["cmd" => ["name" => "ban", "admin" => true, "handler" => "AdminCommands::ban"], "aliases" => ["бан", "мьёльнир", "тор", "thor"]],
            ["cmd" => ["name" => "strike", "admin" => true, "handler" => "AdminCommands::strike"], "aliases" => ["страйк", "молния"]],
        ];

        $this->client = vk_api::create($this->token, $_ENV['VKAPI_VERSION']);
        $this->client = new Execute($this->client);
        $this->lp = new LongPoll($this->client);

        self::$cmdprocessor = new CommandProcessor($this->client);
        $this->setupDatabaseConnection();
    }

    /**
     * Setups Eloquent database connection
     */
    public function setupDatabaseConnection()
    {
        $capsule = new Capsule();
        $capsule->addConnection([
            'driver'    => $_ENV['DB_DRIVER'],
            'host'      => $_ENV['DB_HOST'],
            'database'  => $_ENV['DB_NAME'],
            'username'  => $_ENV['DB_USER'],
            'password'  => $_ENV['DB_PASS'],
            'charset'   => 'utf8',
            'collation' => 'utf8_unicode_ci',
            'prefix'    => '',
        ]);
        $capsule->bootEloquent();
        $capsule->setAsGlobal();
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
        else Utils::log("Members table already created");

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
        else Utils::log("Strikes table already created");
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
     * @throws VkApiException
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

    public function initializeCommands()
    {
        foreach ($this->commands as $command) {
            $cmd = $command["cmd"];
            Utils::log("Creating command {$cmd["name"]} {{$cmd["handler"]}}", Utils::$LOG_DEBUG);
            self::$cmdprocessor->createCommand($cmd["name"], $cmd["handler"], $cmd["admin"]);
            foreach ($command["aliases"] as $alias) {
                Utils::log("Creating alias for {$cmd["name"]} ($alias)", Utils::$LOG_DEBUG);
                self::$cmdprocessor->createAlias($cmd["name"], $alias);
            }
        }
    }

    /**
     * Method to run bot
     * @throws VkApiException
     */
    public function run()
    {
        Utils::log("Generating databases..");
        $this->generateTables();
        $this->collectAllMembers();

        Utils::log("Registering commands..");
        $this->initializeCommands();
        Utils::log("Initialized " . sizeof($this->commands) . " commands");

        Utils::log("@BigDev bot running..");
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
            Utils::log($member->full_name . " " . $message, Utils::$LOG_DEBUG);

            $this->processMessage($message, $peer_id, $user_id, $data->object->message->reply_message);
        });
    }

    /**
     * Processing received message
     *
     * @param $message
     * @param $peer_id
     * @param $user_id
     * @param $attached_message
     * @return bool
     */
    public function processMessage(string $message, int $peer_id, int $user_id, $attached_message)
    {
        $isUser = $peer_id < 2000000000; // Checking is conversation or user

        if ($isUser)
            return false;

        $member = $this->members[$user_id];
        $member->messages++; // Increasing messages count for member

        if (Str::start($message, CommandProcessor::COMMANDS_PREFIX)) {
            [$command, $args] = explode(' ', $message, 2);
            $args = explode(' ', $args);
            $cmdargs = [];
            foreach ($args as $arg) if (is_numeric($arg))
                $cmdargs[] = new CommandArgument(CommandArgument::$TYPE_INTEGER, $arg);
            elseif (preg_match(CommandArgument::MENTION_REGEX, $arg)) {
                $id = explode('|', $arg, 2)[0];
                $cmdargs[] = new CommandArgument(CommandArgument::$TYPE_MENTION, Str::substr($id, 1));
            } else
                $cmdargs[] = new CommandArgument(CommandArgument::$TYPE_STRING, $arg);

            self::$cmdprocessor->callCommand($command, $member->admin, $member, $cmdargs, $peer_id, $attached_message);
        }

        return true;
    }
}