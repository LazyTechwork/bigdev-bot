<?php


namespace App;


class Utils
{
    public static string $LOG_INFO = "96m";
    public static string $LOG_WARN = "93m";
    public static string $LOG_ERROR = "91m";
    public static string $LOG_DEBUG = "37m";

    /**
     * Converts VK sex to DB sex
     *
     * @param $vk_sex
     * @return string
     */
    public static function identifySex($vk_sex): string
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

    /**
     * Logging messages in console
     *
     * @param $msg
     * @param string $lvl
     */
    public static function log($msg, $lvl = "96m")
    {
        echo "\033[$lvl$msg\033[0m\n";
    }

    /**
     * Logging variables in console
     *
     * @param $var
     */
    public static function debug($var)
    {
        self::log(print_r($var, true), self::$LOG_DEBUG);
    }

    /**
     * Generating mentions for messages
     *
     * @param $id
     * @param $text
     * @return string
     */
    public static function generateMention($id, $text): string
    {
        return "[" . ($id > 0 ? "id" : "club") . (abs($id)) . "|$text]";
    }
}