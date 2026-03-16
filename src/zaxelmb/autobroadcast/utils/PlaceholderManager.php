<?php

namespace zaxelmb\autobroadcast\utils;

use zaxelmb\autobroadcast\Loader;
use pocketmine\Server;
use pocketmine\player\Player;
use pocketmine\utils\TextFormat as TF;

class PlaceholderManager {
    
    private $plugin;
    private $customPlaceholders = [];
    
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->registerDefaultPlaceholders();
    }
    
    private function registerDefaultPlaceholders(): void {
        $this->registerPlaceholder("online", function() {
            return count(Server::getInstance()->getOnlinePlayers());
        });
        
        $this->registerPlaceholder("max", function() {
            return Server::getInstance()->getMaxPlayers();
        });
        
        $this->registerPlaceholder("max_players", function() {
            return Server::getInstance()->getMaxPlayers();
        });
        
        $this->registerPlaceholder("tps", function() {
            return round(Server::getInstance()->getTicksPerSecond(), 2);
        });
        
        $this->registerPlaceholder("motd", function() {
            return Server::getInstance()->getNetwork()->getName();
        });
        
        $this->registerPlaceholder("time", function() {
            return date("H:i:s");
        });
        
        $this->registerPlaceholder("date", function() {
            return date("d/m/Y");
        });
        
        $this->registerPlaceholder("day", function() {
            return date("d");
        });
        
        $this->registerPlaceholder("month", function() {
            return date("m");
        });
        
        $this->registerPlaceholder("year", function() {
            return date("Y");
        });
        
        $this->registerPlaceholder("hour", function() {
            return date("H");
        });
        
        $this->registerPlaceholder("minute", function() {
            return date("i");
        });
        
        $this->registerPlaceholder("second", function() {
            return date("s");
        });
        
        $this->registerPlaceholder("day_name", function() {
            $days = ["Domingo", "Lunes", "Martes", "Miércoles", "Jueves", "Viernes", "Sábado"];
            return $days[date("w")];
        });
        
        $this->registerPlaceholder("month_name", function() {
            $months = ["Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio", 
                      "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"];
            return $months[date("n") - 1];
        });
        
        $this->registerPlaceholder("broadcast_count", function() {
            return $this->plugin->getBroadcastManager()->getTotalBroadcastsSent();
        });
        
        $this->registerPlaceholder("current_message", function() {
            return $this->plugin->getBroadcastManager()->getCurrentIndex() + 1;
        });
        
        $this->registerPlaceholder("total_messages", function() {
            return $this->plugin->getBroadcastManager()->getTotalMessages();
        });
    }
    
    public function registerPlaceholder(string $identifier, callable $callback): void {
        $this->customPlaceholders[$identifier] = $callback;
    }
    
    public function replacePlaceholders(string $message, ?Player $player = null): string {
        foreach($this->customPlaceholders as $identifier => $callback) {
            $placeholder = "{" . $identifier . "}";
            if(strpos($message, $placeholder) !== false) {
                $value = $callback();
                $message = str_replace($placeholder, $value, $message);
            }
        }
        
        if($player !== null) {
            $message = $this->replacePlayerPlaceholders($message, $player);
        }
        
        return $message;
    }
    
    private function replacePlayerPlaceholders(string $message, Player $player): string {
        $replacements = [
            "{player}" => $player->getName(),
            "{player_name}" => $player->getName(),
            "{display_name}" => $player->getDisplayName(),
            "{ping}" => $player->getNetworkSession()->getPing(),
            "{health}" => round($player->getHealth(), 1),
            "{max_health}" => $player->getMaxHealth(),
            "{food}" => $player->getHungerManager()->getFood(),
            "{level}" => $player->getWorld()->getFolderName(),
            "{world}" => $player->getWorld()->getDisplayName(),
            "{x}" => round($player->getPosition()->getX(), 1),
            "{y}" => round($player->getPosition()->getY(), 1),
            "{z}" => round($player->getPosition()->getZ(), 1),
            "{gamemode}" => $this->getGamemodeName($player->getGamemode()->id()),
        ];
        
        if($this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
            $economy = \onebone\economyapi\EconomyAPI::getInstance();
            $replacements["{money}"] = $economy->myMoney($player);
            $replacements["{balance}"] = $economy->myMoney($player);
        }
        
        return str_replace(array_keys($replacements), array_values($replacements), $message);
    }
    
    private function getGamemodeName(int $id): string {
        $modes = [
            0 => "Survival",
            1 => "Creative",
            2 => "Adventure",
            3 => "Spectator"
        ];
        return $modes[$id] ?? "Unknown";
    }
    
    public function getAvailablePlaceholders(): array {
        $placeholders = [];
        

        foreach(array_keys($this->customPlaceholders) as $identifier) {
            $placeholders[] = "{" . $identifier . "}";
        }
        
        $playerPlaceholders = [
            "{player}", "{player_name}", "{display_name}", "{ping}", 
            "{health}", "{max_health}", "{food}", "{level}", "{world}",
            "{x}", "{y}", "{z}", "{gamemode}"
        ];
        
        if($this->plugin->getServer()->getPluginManager()->getPlugin("EconomyAPI") !== null) {
            $playerPlaceholders[] = "{money}";
            $playerPlaceholders[] = "{balance}";
        }
        
        return array_merge($placeholders, $playerPlaceholders);
    }
    
    public function isValidPlaceholder(string $placeholder): bool {
        $identifier = trim($placeholder, "{}");
        return isset($this->customPlaceholders[$identifier]);
    }
}