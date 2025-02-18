<?php

namespace Lee1387\LobbyCore;

use Lee1387\LobbyCore\commands\FlyCommand;
use Lee1387\LobbyCore\commands\LobbyCommand;
use Lee1387\LobbyCore\commands\SetLobbyCommand;
use Lee1387\LobbyCore\events\EventListener;
use pocketmine\event\EventPriority;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\player\GameMode;
use pocketmine\player\Player;
use pocketmine\plugin\PluginBase;
use pocketmine\utils\Config;

class Main extends PluginBase {

    private static Main $instance;
    private Config $config;
    private bool $isLobbyServer = false;

    protected function onEnable(): void {
        self::$instance = $this;
        $this->initializeConfig();
        $this->setupServerType();
        $this->registerCommands();
        $this->registerEvents();
    }
    
    private function initializeConfig(): void {
        $this->saveDefaultConfig();
        $this->checkConfigUpdates();
        $this->config = $this->getConfig();
    }
    
    private function setupServerType(): void {
        $currentServer = $this->resolveServerAddress($this->getServer()->getIp()) . ":" . $this->getServer()->getPort();
        $internalServer = $this->resolveServerAddress($this->getConfig()->get("internal-address", "127.0.0.1:19134"));
        
        $this->isLobbyServer = ($currentServer === $internalServer);
        
        $this->getLogger()->info("Resolved Current Server: " . $currentServer);
        $this->getLogger()->info("Resolved Internal Server: " . $internalServer);
        $this->getLogger()->info("Is Lobby Server: " . ($this->isLobbyServer ? "Yes" : "No"));
    }
    
    private function resolveServerAddress(string $address): string {
        return str_replace("0.0.0.0", "127.0.0.1", $address);
    }
    
    private function registerCommands(): void {
        $commandMap = $this->getServer()->getCommandMap();
        $commandMap->register("lobbycore", new LobbyCommand());
        
        if ($this->isLobbyServer) {
            $commandMap->register("lobbycore", new SetLobbyCommand());
            $commandMap->register("lobbycore", new FlyCommand());
        }
    }
    
    private function registerEvents(): void {
        if ($this->isLobbyServer) {
            $this->getLogger()->info("Server registered as lobby server - enabling lobby features");
            $this->getServer()->getPluginManager()->registerEvents(new EventListener(), $this, EventPriority::HIGH);
        } else {
            $this->registerNonLobbyEvents();
        }
    }
    
    private function registerNonLobbyEvents(): void {
        $this->getServer()->getPluginManager()->registerEvent(
            PlayerJoinEvent::class,
            function(PlayerJoinEvent $event): void {
                $this->cleanupLobbyFeatures($event->getPlayer());
            },
            EventPriority::HIGH,
            $this
        );
        
        foreach ($this->getServer()->getOnlinePlayers() as $player) {
            $this->cleanupLobbyFeatures($player);
        }
    }

    protected function onDisable(): void {
        if (!$this->isLobbyServer) {
            foreach ($this->getServer()->getOnlinePlayers() as $player) {
                $this->cleanupLobbyFeatures($player);
            }
        }
    }

    private function checkConfigUpdates(): void {
        $currentVersion = $this->getConfig()->get("config-version", 0);
        $latestVersion = 1;
        
        if ($currentVersion < $latestVersion) {
            $this->getLogger()->notice("Updating config file...");
            $this->backupConfig($currentVersion);
            $this->saveResource("config.yml", true);
            $this->reloadConfig();
            $this->getLogger()->notice("Config updated! Old config backed up as: config_backup_v" . $currentVersion . ".yml");
        }
    }
    
    private function backupConfig(int $version): void {
        $oldConfig = $this->getDataFolder() . "config.yml";
        $backupConfig = $this->getDataFolder() . "config_backup_v" . $version . ".yml";
        copy($oldConfig, $backupConfig);
    }
    
    private function cleanupLobbyFeatures(Player $player): void {
        $player->getInventory()->clearAll();
        $player->getArmorInventory()->clearAll();
        $player->setGamemode(GameMode::SURVIVAL());
        $player->setAllowFlight(false);
        $player->setFlying(false);
        
        foreach ($player->getEffects()->all() as $effect) {
            $player->getEffects()->remove($effect->getType());
        }
    }

    public static function getInstance(): Main {
        return self::$instance;
    }

    public function isLobbyServer(): bool {
        return $this->isLobbyServer;
    }

    public function getPluginConfig(): Config {
        return $this->config;
    }
}