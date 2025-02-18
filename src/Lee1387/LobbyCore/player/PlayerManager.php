<?php

namespace Lee1387\LobbyCore\player;

use Lee1387\LobbyCore\items\LobbyItems;
use Lee1387\LobbyCore\Main;
use pocketmine\player\GameMode;
use pocketmine\player\Player;

class PlayerManager {

    public static function resetPlayer(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setGamemode(GameMode::SURVIVAL());
        $player->setAllowFlight(false);
        $player->setFlying(false);

        foreach($player->getEffects()->all() as $effect) {
            $player->getEffects()->remove($effect->getType());
        }
    }

    public static function setupLobbyPlayer(Player $player): void {
        self::resetPlayer($player);
        $player->setHealth(20);
        $player->getHungerManager()->setFood(20);
        $player->getHungerManager()->setSaturation(20);
        $player->getInventory()->setItem(0, LobbyItems::getGameSelector());
        $player->setGamemode(self::getDefaultGamemode());
    }

    public static function getDefaultGamemode(): GameMode {
        return match(strtolower(Main::getInstance()->getConfig()->get("default-gamemode", "adventure"))) {
            "creative" => GameMode::CREATIVE(),
            "survival" => GameMode::SURVIVAL(),
            "spectator" => GameMode::SPECTATOR(),
            default => GameMode::ADVENTURE()
        };
    }
}