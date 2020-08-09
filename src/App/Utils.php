<?php


namespace App;


class Utils
{
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
}