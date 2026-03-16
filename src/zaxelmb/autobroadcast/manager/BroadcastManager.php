<?php

namespace zaxelmb\autobroadcast\manager;

use zaxelmb\autobroadcast\Loader;
use zaxelmb\autobroadcast\utils\PlaceholderManager;
use pocketmine\Server;
use pocketmine\utils\TextFormat;

class BroadcastManager {
    
    private $plugin;
    private $messages = [];
    private $currentIndex = 0;
    private $totalBroadcastsSent = 0;
    private $shuffledMessages = [];
    private $placeholderManager;
    
    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
        $this->placeholderManager = new PlaceholderManager($plugin);
        $this->loadMessages();
    }
    
    public function loadMessages(): void {
        $this->messages = $this->plugin->getConfig()->get("messages", []);
        
        if($this->getMode() === "shuffle") {
            $this->shuffleMessages();
        }
    }
    
    private function shuffleMessages(): void {
        $this->shuffledMessages = $this->messages;
        shuffle($this->shuffledMessages);
    }
    
    public function getNextMessage(): ?string {
        if(empty($this->messages)) {
            return null;
        }
        
        $settings = $this->plugin->getConfig()->get("settings");
        $mode = $settings["mode"] ?? "sequential";
        
        if($this->shouldSendSpecialMessage()) {
            return $this->getRandomSpecialMessage();
        }
        
        switch($mode) {
            case "random":
                return $this->messages[array_rand($this->messages)];
                
            case "shuffle":
                $message = $this->shuffledMessages[$this->currentIndex];
                $this->currentIndex++;
                
                if($this->currentIndex >= count($this->shuffledMessages)) {
                    $this->currentIndex = 0;
                    $this->shuffleMessages();
                }
                
                return $message;
                
            case "sequential":
            default:
                $message = $this->messages[$this->currentIndex];
                $this->currentIndex++;
                
                if($this->currentIndex >= count($this->messages)) {
                    $this->currentIndex = 0;
                }
                
                return $message;
        }
    }
    
    private function shouldSendSpecialMessage(): bool {
        $special = $this->plugin->getConfig()->get("special-messages");
        
        if(!isset($special["enabled"]) || !$special["enabled"]) {
            return false;
        }
        
        $chance = $special["chance"] ?? 10;
        return mt_rand(1, 100) <= $chance;
    }
    
    private function getRandomSpecialMessage(): ?string {
        $special = $this->plugin->getConfig()->get("special-messages");
        $rareMessages = $special["rare-messages"] ?? [];
        
        if(empty($rareMessages)) {
            return null;
        }
        
        return $rareMessages[array_rand($rareMessages)];
    }
    
    public function sendBroadcast(?string $message = null): void {
        $settings = $this->plugin->getConfig()->get("settings");
        
        $minPlayers = $settings["minimum-players"] ?? 1;
        if(count(Server::getInstance()->getOnlinePlayers()) < $minPlayers) {
            return;
        }
        
        if($message === null) {
            $message = $this->getNextMessage();
        }
        
        if($message === null) {
            return;
        }
        
        $prefix = $settings["prefix"] ?? "";
        
        $format = $settings["format"] ?? "broadcast";
        
        switch($format) {
            case "broadcast":
                $fullMessage = TextFormat::colorize($prefix . $this->placeholderManager->replacePlaceholders($message));
                Server::getInstance()->broadcastMessage($fullMessage);
                break;
                
            case "message":
            case "title":
            case "actionbar":
                foreach(Server::getInstance()->getOnlinePlayers() as $player) {
                    $personalizedMessage = $this->placeholderManager->replacePlaceholders($message, $player);
                    $fullMessage = TextFormat::colorize($prefix . $personalizedMessage);
                    
                    switch($format) {
                        case "message":
                            $player->sendMessage($fullMessage);
                            break;
                        case "title":
                            $player->sendTitle(TextFormat::colorize($personalizedMessage));
                            break;
                        case "actionbar":
                            $player->sendActionBarMessage($fullMessage);
                            break;
                    }
                }
                break;
        }
        
        $this->totalBroadcastsSent++;
    }
    
    public function getMode(): string {
        return $this->plugin->getConfig()->get("settings")["mode"] ?? "sequential";
    }
    
    public function getCurrentIndex(): int {
        return $this->currentIndex;
    }
    
    public function getTotalMessages(): int {
        return count($this->messages);
    }
    
    public function getTotalBroadcastsSent(): int {
        return $this->totalBroadcastsSent;
    }
    
    public function resetStats(): void {
        $this->currentIndex = 0;
        $this->totalBroadcastsSent = 0;
    }
    
    public function getPlaceholderManager(): PlaceholderManager {
        return $this->placeholderManager;
    }
}