<?php


namespace App\Commands;


use DigitalStar\vk_api\LongPoll;
use DigitalStar\vk_api\vk_api;

class CommandProcessor
{
    public const COMMANDS_HOME = "\\App\\Commands\\";
    public const COMMANDS_PREFIX = "/";
    private $commands = [];
    private $namespace = [];
    private $client;
    private $lp;

    /**
     * CommandProcessor constructor.
     */
    public function __construct(vk_api $client, LongPoll $lp)
    {
        $this->client = $client;
        $this->lp = $lp;
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
     * @param $cmd
     * @param $args
     * @return mixed
     */
    public function callCommand($cmd, $admin, $args)
    {
        $command = $this->commands[$this->namespace[$cmd]];
        if ($command["admin"] && !$admin)
            return false;
        [$class, $method] = explode('::', $command["handler"]);
        return call_user_func([self::COMMANDS_HOME . $class, $method], $this->client, $this->lp, $args);
    }
}