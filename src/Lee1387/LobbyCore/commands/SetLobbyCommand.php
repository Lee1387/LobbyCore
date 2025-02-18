<?php

namespace Lee1387\LobbyCore\commands;

use Lee1387\LobbyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat;

class SetLobbyCommand extends Command {
    
    public function __construct() {
        parent::__construct("setlobby", "Set the lobby spawn position", "/setlobby", ["setspawn"]);
        $this->setPermission("lobbycore.command.setlobby");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
            return false;
        }

        if (!Main::getInstance()->isLobbyServer()) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used on the lobby server!");
            return false;
        }

        $config = Main::getInstance()->getConfig();
        $lobbyWorlds = $config->get("lobby-worlds", ["world"]);
        $currentWorld = $sender->getWorld()->getFolderName();

        if (!in_array($currentWorld, $lobbyWorlds)) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in lobby worlds!");
            $sender->sendMessage(TextFormat::GRAY . "Configured lobby worlds: " . implode(", ", $lobbyWorlds));
            return false;
        }

        $pos = $sender->getPosition();
        $location = $sender->getLocation();
        
        $config->setNested("lobby.world", $currentWorld);
        $config->setNested("lobby.x", $pos->getX());
        $config->setNested("lobby.y", $pos->getY());
        $config->setNested("lobby.z", $pos->getZ());
        $config->setNested("lobby.yaw", $location->getYaw());
        $config->setNested("lobby.pitch", $location->getPitch());
        $config->save();

        $posFormat = sprintf("%.2f, %.2f, %.2f", $pos->getX(), $pos->getY(), $pos->getZ());
        $sender->sendMessage(TextFormat::GREEN . "Lobby position set to: " . TextFormat::WHITE . $posFormat);
        
        return true;
    }
}