<?php


namespace App\Commands;


use App\Models\Member;
use DigitalStar\vk_api\vk_api;
use Illuminate\Support\Str;

class AdminCommands
{
    /**
     * @param vk_api $client
     * @param Member $sender
     * @param int $peer_id
     * @param $attached_message
     * @param CommandArgument[] $args
     * @return false
     * @throws \DigitalStar\vk_api\VkApiException
     */
    public static function ban(vk_api $client, vk_api $user_client, Member $sender, int $peer_id, $attached_message, $args)
    {
        if ($attached_message == null && sizeof($args) == 0) {
            $client->sendMessage($peer_id, "Пользователь не найден");
            return false;
        }
        if ($args[0]->getType() == CommandArgument::$TYPE_MENTION)
            $banuser = Str::startsWith($args[0]->getData(), "id") ? Str::substr($args[0]->getData(), 2) : Str::substr($args[0]->getData(), 4);
        elseif ($attached_message != null)
            $banuser = $attached_message->from_id;
        else {
            $client->sendMessage($peer_id, "Пользователь не найден");
            return false;
        }
        $banned = null;
        if ($banuser > 0) {
            $banned = Member::query()->where("vk", $banuser)->first();
            $banned->status = "banned";
            $banned->save();
        }
        $client->request("messages.removeChatUser", [
            "chat_id"   => $peer_id - 2000000000,
            "member_id" => $banuser,
        ]);
        $user_client->request("groups.ban", [
            "group_id"        => $_ENV["BOT_GROUPID"],
            "owner_id"        => $banuser,
            "comment"         => "Вы были заблокированы в беседе пользователем {$sender->full_name}",
            "comment_visible" => 1,
        ]);
        $client->sendMessage($peer_id, "Пользователь [" . ($banuser > 0 ? "id" : "club") . (abs($banuser)) . sprintf("|%s] был заблокирован [id%s|%s]", $banned->full_name, $sender->vk, $sender->full_name));
        return true;
    }
}