<?php

namespace Lee1387\LobbyCore\forms;

use jojoe77777\FormAPI\SimpleForm;
use Lee1387\LobbyCore\Main;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class GameSelectorForm {

    public static function send(Player $player): void {
        $form = new SimpleForm(function(Player $player, ?int $data) {
            if ($data === null) return;

            $buttons = array_values(Main::getInstance()->getConfig()->get("menu")["buttons"]);
            $selectedButton = $buttons[$data];

            switch($selectedButton["type"]) {
                case "world":
                    $worldName = $selectedButton["target"];
                    $world = Main::getInstance()->getServer()->getWorldManager()->getWorldByName($worldName);

                    if ($world === null) {
                        $player->sendMessage(TextFormat::RED . "Error: World '" . $worldName . "' not found!");
                        return;
                    }

                    $player->teleport($world->getSafeSpawn());
                    $player->sendMessage(TextFormat::GREEN . "Teleported to " . $worldName);
                    break;

                case "server":
                    $serverInfo = explode(":", $selectedButton["target"]);
                    if (count($serverInfo) !== 2) {
                        $player->sendMessage(TextFormat::RED . "Error: Invalid server address configuration!");
                        return;
                    }

                    $player->transfer($serverInfo[0], (int)$serverInfo[1]);
                    break;
            }
        });

        $form->setTitle(TextFormat::BOLD . TextFormat::AQUA . Main::getInstance()->getConfig()->get("compass-name"));

        foreach(Main::getInstance()->getConfig()->get("menu")["buttons"] as $button) {
            $form->addButton($button["text"]);
        }

        $form->sendToPlayer($player);
    }
}