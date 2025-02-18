<?php

namespace Lee1387\LobbyCore\commands;

use Lee1387\LobbyCore\Main;
use Lee1387\LobbyCore\world\WorldManager;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class FlyCommand extends Command {

    public function __construct() {
        parent::__construct("fly", "Toggle fly mode", "/fly");
        $this->setPermission("lobbycore.command.fly");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
            return false;
        }

        if (!Main::getInstance()->isLobbyServer()) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used on the lobby server.");
            return false;
        }

        if (!WorldManager::isPlayerInLobby($sender)) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in the lobby world!");
            return false;
        }

        $flying = $sender->getAllowFlight();
        $sender->setAllowFlight(!$flying);
        $sender->setFlying(!$flying);

        $sender->sendMessage(!$flying ?
            TextFormat::GREEN . "Fly Enabled" :
            TextFormat::RED . "Fly Disabled"
        );

        return true;
    }
}