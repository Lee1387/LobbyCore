<?php

namespace Lee1387\LobbyCore\events;

use Lee1387\LobbyCore\forms\GameSelectorForm;
use Lee1387\LobbyCore\items\LobbyItems;
use Lee1387\LobbyCore\Main;
use Lee1387\LobbyCore\player\PlayerManager;
use Lee1387\LobbyCore\world\WorldManager;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\BlockPlaceEvent;
use pocketmine\event\entity\EntityDamageEvent;
use pocketmine\event\entity\EntityTeleportEvent;
use pocketmine\event\inventory\InventoryTransactionEvent;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerDeathEvent;
use pocketmine\event\player\PlayerDropItemEvent;
use pocketmine\event\player\PlayerExhaustEvent;
use pocketmine\event\player\PlayerItemUseEvent;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\event\player\PlayerTransferEvent;
use pocketmine\player\Player;
use pocketmine\scheduler\Task;
use pocketmine\world\Position;

class EventListener implements Listener {

    public function onJoin(PlayerJoinEvent $event): void {
        $player = $event->getPlayer();
        $event->setJoinMessage("");
        
        if (!Main::getInstance()->isLobbyServer()) return;
    
        $config = Main::getInstance()->getConfig();
        $welcomeMessages = $config->getNested("messages.welcome", [
            "",
            "§8§l▪ §r§b§lWELCOME TO THE SERVER",
            "§8§l▪ §r§7Welcome, §b{player}§7!",
            ""
        ]);

        foreach ($welcomeMessages as $line) {
            $player->sendMessage(str_replace("{player}", $player->getName(), $line));
        }

        $world = $config->getNested("lobby.world");
        if (empty($world)) return;
        
        $worldManager = $player->getServer()->getWorldManager();
        if (!$worldManager->isWorldLoaded($world) && !$worldManager->loadWorld($world)) return;
        
        $level = $worldManager->getWorldByName($world);
        if ($level === null) return;
        
        $x = (int)$config->getNested("lobby.x", 0);
        $z = (int)$config->getNested("lobby.z", 0);
        $level->loadChunk($x >> 4, $z >> 4);
        
        $position = new Position(
            floatval($config->getNested("lobby.x", 0)),
            floatval($config->getNested("lobby.y", 64)),
            floatval($config->getNested("lobby.z", 0)),
            $level
        );
    
        $player->teleport(
            $position,
            floatval($config->getNested("lobby.yaw", 0)),
            floatval($config->getNested("lobby.pitch", 0))
        );
        
        PlayerManager::setupLobbyPlayer($player);
    }


    public function onDeath(PlayerDeathEvent $event): void {
        $player = $event->getPlayer();
        if (!WorldManager::isPlayerInLobby($player)) return;
        
        $event->setDeathMessage("");
        Main::getInstance()->getScheduler()->scheduleDelayedTask(
            new class($player) extends Task {
                public function __construct(private Player $player) {}
                
                public function onRun(): void {
                    if ($this->player->isOnline() && Main::getInstance()->isLobbyServer()) {
                        $this->player->getInventory()->setItem(0, LobbyItems::getGameSelector());
                    }
                }
            }, 
            1
        );
    }

    public function onDrop(PlayerDropItemEvent $event): void {
        if (WorldManager::isPlayerInLobby($event->getPlayer()) && 
            Main::getInstance()->getConfig()->getNested("preventions.item-drop", true)) {
            $event->cancel();
        }
    }
    
    public function onInventoryTransaction(InventoryTransactionEvent $event): void {
        $player = $event->getTransaction()->getSource();
        if ($player instanceof Player && 
            WorldManager::isPlayerInLobby($player) && 
            Main::getInstance()->getConfig()->getNested("preventions.inventory-move", true)) {
            $event->cancel();
        }
    }

    public function onDamage(EntityDamageEvent $event): void {
        $entity = $event->getEntity();
        if ($entity instanceof Player && 
            WorldManager::isPlayerInLobby($entity) && 
            Main::getInstance()->getConfig()->getNested("preventions.damage", true)) {
            $event->cancel();
        }
    }

    public function onExhaust(PlayerExhaustEvent $event): void {
        if (WorldManager::isPlayerInLobby($event->getPlayer()) && 
            Main::getInstance()->getConfig()->getNested("preventions.hunger", true)) {
            $event->cancel();
        }
    }

    public function onBreak(BlockBreakEvent $event): void {
        if (WorldManager::isPlayerInLobby($event->getPlayer()) && 
            Main::getInstance()->getConfig()->getNested("preventions.block-break", true)) {
            $event->cancel();
        }
    }

    public function onPlace(BlockPlaceEvent $event): void {
        if (WorldManager::isPlayerInLobby($event->getPlayer()) && 
            Main::getInstance()->getConfig()->getNested("preventions.block-place", true)) {
            $event->cancel();
        }
    }

    public function onItemUse(PlayerItemUseEvent $event): void {
        $player = $event->getPlayer();
        if (WorldManager::isPlayerInLobby($player) && $event->getItem()->equals(LobbyItems::getGameSelector())) {
            GameSelectorForm::send($player);
        }
    }

    public function onWorldChange(EntityTeleportEvent $event): void {
        $entity = $event->getEntity();
        if (!$entity instanceof Player) return;

        $fromWorld = $event->getFrom()->getWorld();
        $toWorld = $event->getTo()->getWorld();
        
        if ($fromWorld === $toWorld) return;

        if (WorldManager::isLobbyWorld($fromWorld->getFolderName()) && 
            !WorldManager::isLobbyWorld($toWorld->getFolderName())) {
            PlayerManager::resetPlayer($entity);
        } elseif (!WorldManager::isLobbyWorld($fromWorld->getFolderName()) && 
            WorldManager::isLobbyWorld($toWorld->getFolderName())) {
            PlayerManager::setupLobbyPlayer($entity);
        }
    }

    public function onServerTransfer(PlayerTransferEvent $event): void {
        if (!Main::getInstance()->isLobbyServer()) {
            PlayerManager::resetPlayer($event->getPlayer());
        }
    }
}