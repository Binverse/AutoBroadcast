<?php

namespace zaxelmb\autobroadcast;

use pocketmine\plugin\PluginBase;
use pocketmine\utils\TextFormat;
use zaxelmb\autobroadcast\manager\BroadcastManager;
use zaxelmb\autobroadcast\task\BroadcastTask;
use zaxelmb\autobroadcast\command\BroadcastCommand;
use zaxelmb\autobroadcast\utils\ConfigManager;

class Loader extends PluginBase {
    
    private static $instance;
    private $broadcastManager;
    private $broadcastTask;
    private $configManager;
    
    public function onEnable(): void {
        self::$instance = $this;
        
        $this->saveDefaultConfig();
        $this->saveResource("messages.yml");
        
        $this->configManager = new ConfigManager($this);
        
        $errors = $this->configManager->validateConfig();
        if(!empty($errors)) {
            $this->getLogger()->warning("Errores en la configuración:");
            foreach($errors as $error) {
                $this->getLogger()->warning("- " . $error);
            }
        }
        
        $this->broadcastManager = new BroadcastManager($this);
        
        $this->getServer()->getCommandMap()->register("autobroadcast", new BroadcastCommand($this));
        
        if($this->configManager->getSetting("enabled", true)) {
            $this->startBroadcastTask();
        }
        
        $this->getLogger()->info("AutoBroadcast plugin enabled");
    }
    
    public function onDisable(): void {
        if($this->broadcastTask !== null) {
            $this->broadcastTask->getHandler()->cancel();
        }
        
        $this->getLogger()->info("AutoBroadcast plugin disabled.");
    }
    
    public function startBroadcastTask(): void {
        $interval = $this->configManager->getSetting("interval", 180) * 20;
        
        $this->broadcastTask = new BroadcastTask($this);
        $this->getScheduler()->scheduleRepeatingTask($this->broadcastTask, $interval);
    }
    
    public function stopBroadcastTask(): void {
        if($this->broadcastTask !== null) {
            $this->broadcastTask->getHandler()->cancel();
            $this->broadcastTask = null;
        }
    }
    
    public function getBroadcastManager(): BroadcastManager {
        return $this->broadcastManager;
    }
    
    public function getConfigManager(): ConfigManager {
        return $this->configManager;
    }
    
    public static function getInstance(): Main {
        return self::$instance;
    }
}