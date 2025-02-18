<?php

namespace Lee1387\LobbyCore\world;

use Lee1387\LobbyCore\Main;
use pocketmine\player\Player;

class WorldManager {

    public static function isLobbyWorld(string $worldName): bool {
        return in_array($worldName, Main::getInstance()->getConfig()->get("lobby-worlds", ["world"]));
    }

    public static function isPlayerInLobby(Player $player): bool {
        return self::isLobbyWorld($player->getWorld()->getFolderName());
    }
}