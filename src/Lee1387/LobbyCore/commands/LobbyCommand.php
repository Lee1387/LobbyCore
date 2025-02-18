<?php

namespace Lee1387\LobbyCore\commands;

use Lee1387\LobbyCore\Main;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\player\Player;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat;
use pocketmine\world\Position;
use Throwable;

class LobbyCommand extends Command {
    
    public function __construct() {
        parent::__construct("lobby", "Teleport to the lobby", "/lobby", ["hub", "spawn"]);
        $this->setPermission("lobbycore.command.lobby");
    }

    public function execute(CommandSender $sender, string $commandLabel, array $args): bool {
        if (!$sender instanceof Player) {
            $sender->sendMessage(TextFormat::RED . "This command can only be used in-game!");
            return false;
        }
    
        $config = Main::getInstance()->getConfig();
        
        if (!Main::getInstance()->isLobbyServer()) {
            return $this->transferToLobby($sender, $config);
        }

        return $this->teleportToLobbySpawn($sender, $config);
    }

    private function transferToLobby(Player $player, Config $config): bool {
        $serverName = $config->get("lobby-server", "lobby");
        if (empty($serverName)) {
            $player->sendMessage(TextFormat::RED . "Lobby server has not been set!");
            return false;
        }

        try {
            $player->getInventory()->clearAll();
            $player->getArmorInventory()->clearAll();
            $player->sendMessage(TextFormat::GREEN . "Transferring to lobby...");
            $player->transfer($serverName);
            return true;
        } catch (Throwable $e) {
            Main::getInstance()->getLogger()->error("Transfer failed: " . $e->getMessage());
            $player->sendMessage(TextFormat::RED . "Could not transfer to lobby server!");
            return false;
        }
    }

    private function teleportToLobbySpawn(Player $player, Config $config): bool {
        $world = $config->getNested("lobby.world");
        if (empty($world)) {
            $player->sendMessage(TextFormat::RED . "Lobby position has not been set!");
            return false;
        }

        $worldManager = $player->getServer()->getWorldManager();
        if (!$worldManager->isWorldLoaded($world)) {
            $worldManager->loadWorld($world);
        }

        $level = $worldManager->getWorldByName($world);
        if ($level === null) {
            $player->sendMessage(TextFormat::RED . "Lobby world not found!");
            return false;
        }

        $position = new Position(
            $config->getNested("lobby.x"),
            $config->getNested("lobby.y"),
            $config->getNested("lobby.z"),
            $level
        );

        $player->teleport(
            $position,
            $config->getNested("lobby.yaw", 0),
            $config->getNested("lobby.pitch", 0)
        );

        return true;
    }
}