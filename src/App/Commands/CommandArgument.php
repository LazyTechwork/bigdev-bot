<?php


namespace App\Commands;


class CommandArgument
{
    public static $TYPE_STRING = "string";
    public static $TYPE_INTEGER = "integer";
    public static $TYPE_MENTION = "mention";


    public const MENTION_REGEX = "/\[(id|club)(\d+)\|(.+?)]/";


    /**
     * Type of argument
     *
     * @var string
     */
    private $type;
    /**
     * Argument data
     *
     * @var string
     */
    private $data;

    /**
     * Get argument type
     *
     * @return string
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get argument data
     *
     * @return string
     */
    public function getData(): string
    {
        return $this->data;
    }

    /**
     * CommandArgument constructor.
     * @param $type
     * @param $data
     */
    public function __construct($type, $data)
    {
        $this->type = $type;
        $this->data = $data;
    }

    public function __toString()
    {
        if ($this->type === self::$TYPE_MENTION)
            return "@" . $this->data;
        else
            return $this->data;
    }
}