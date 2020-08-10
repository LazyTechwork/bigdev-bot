<?php


namespace App\Commands;


use App\Models\Member;
use DigitalStar\vk_api\LongPoll;
use DigitalStar\vk_api\vk_api;

class CommandProcessor
{
    public const COMMANDS_HOME = "\\App\\Commands\\";
    public const COMMANDS_PREFIX = "/";
    private $commands = [];
    private $namespace = [];
    private $client;

    /**
     * CommandProcessor constructor.
     */
    public function __construct(vk_api $client)
    {
        $this->client = $client;
    }


    /**
     * Create command
     *
     * @param $name
     * @param $handler
     * @param boolean $admin
     */
    public function createCommand($name, $handler, $admin = false)
    {
        $this->commands[$name] = [
            "handler" => $handler,
            "admin"   => $admin,
        ];

        $this->namespace[$name] = $name;
    }

    /**
     * Create alias for command
     *
     * @param $name
     * @param $alias
     */
    public function createAlias($name, $alias)
    {
        $this->namespace[$alias] = $name;
    }

    /**
     * Call command by name or alias
     *
     * @param string $cmd
     * @param bool $admin
     * @param Member $sender
     * @param array $args
     * @param int $peer_id
     * @param $attached_message
     * @return mixed
     */
    public function callCommand(string $cmd, $admin, Member $sender, array $args, int $peer_id, $attached_message)
    {
        $command = $this->commands[$this->namespace[$cmd]];
        if ($command["admin"] && !$admin)
            return false;
        [$class, $method] = explode('::', $command["handler"]);
        return call_user_func([self::COMMANDS_HOME . $class, $method], $this->client, $sender, $peer_id, $attached_message, $args);
    }
}