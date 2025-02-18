<?php

namespace Lee1387\LobbyCore\items;

use Lee1387\LobbyCore\Main;
use pocketmine\item\Item;
use pocketmine\item\VanillaItems;
use pocketmine\utils\TextFormat;

class LobbyItems {

    public static function getGameSelector(): Item {
        $compass = VanillaItems::COMPASS();
        $compass->setCustomName(TextFormat::BOLD . TextFormat::AQUA . Main::getInstance()->getConfig()->get("compass-name", "Game Selector"));
        return $compass;
    }
}