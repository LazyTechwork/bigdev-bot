<?php


namespace App;


use GetOpt\ArgumentException;
use GetOpt\Command;
use GetOpt\GetOpt;

class CLI
{
    private $getopt;

    public function __construct()
    {
        $this->getopt = new GetOpt();
        $this->getopt->getHelp()->setTexts([
            'placeholder'     => '<>',
            'optional'        => '[]',
            'multiple'        => '...',
            'usage-title'     => 'Usage: ',
            'usage-command'   => 'command',
            'usage-options'   => 'options',
            'usage-operands'  => 'operands',
            'options-title'   => "Options:\n",
            'options-listing' => ', ',
            'commands-title'  => "Commands:\n",
        ]);
        $this->getopt->addCommands([
            Command::create("start", "\\App\\CLI::botStart")->setDescription("Start bot"),
            Command::create("db:fresh", "\\App\\CLI::dbFresh")->setDescription("Recreate database"),
        ]);
    }

    public function process()
    {
        try {
            $this->getopt->process();
        } catch (ArgumentException $exception) {
            throw $exception;
        }
    }

    public function execute()
    {
        if (!$command = $this->getopt->getCommand()) {
            echo $this->getopt->getHelp()->render($this->getopt);
        } else {
            [$class, $method] = explode('::', $command->getHandler());
            call_user_func([$class, $method], $this->getopt->getOptions(), $this->getopt->getOperands());
        }
    }

    public static function botStart()
    {
        $bot = new Bot();
        $bot->run();
    }

    public static function dbFresh()
    {
        $bot = new Bot();
        $bot->dropTables();
    }
}