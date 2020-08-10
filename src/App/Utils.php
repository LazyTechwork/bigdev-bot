<?php


namespace App;


class Utils
{
    public static $LOG_INFO = "96m";
    public static $LOG_WARN = "93m";
    public static $LOG_ERROR = "91m";
    public static $LOG_DEBUG = "37m";

    public static function identifySex($vk_sex)
    {
        switch ($vk_sex) {
            case 1:
                return "female";
            case 2:
                return "male";
            default:
                return "undefined";
        }
    }

    public static function log($msg, $lvl = "96m")
    {
        echo "\033[$lvl$msg\033[0m\n";
    }

    public static function debug($var)
    {
        self::log(print_r($var, true), self::$LOG_DEBUG);
    }

    public static function generateMention($id, $text)
    {
        return "[" . ($id > 0 ? "id" : "club") . (abs($id)) . "|$text]";
    }
}