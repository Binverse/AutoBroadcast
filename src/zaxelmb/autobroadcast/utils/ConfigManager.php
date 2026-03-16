<?php

namespace zaxelmb\autobroadcast\utils;

use zaxelmb\autobroadcast\Loader;
use pocketmine\utils\Config;
use pocketmine\utils\TextFormat as TF;

class ConfigManager {
    
    private $plugin;
    private $config;
    private $messagesConfig;
    
    public function __construct(Main $plugin) {
        $this->plugin = $plugin;
        $this->loadConfigs();
    }
    
    public function loadConfigs(): void {
        $this->config = $this->plugin->getConfig();
        
        $this->messagesConfig = new Config($this->plugin->getDataFolder() . "messages.yml", Config::YAML);
    }
    
    public function reload(): bool {
        try {
            $this->plugin->reloadConfig();
            $this->loadConfigs();
            return true;
        } catch (\Exception $e) {
            $this->plugin->getLogger()->error("Error al recargar configuración: " . $e->getMessage());
            return false;
        }
    }
    
    public function get(string $key, $default = null) {
        return $this->config->get($key, $default);
    }
    
    public function set(string $key, $value): void {
        $this->config->set($key, $value);
    }
    
    public function save(): void {
        $this->config->save();
    }
    
    public function getMessage(string $key, array $replacements = []): string {
        $message = $this->messagesConfig->getNested($key, $key);
        
        if(is_array($message)) {
            $message = implode("\n", $message);
        }
        
        foreach($replacements as $search => $replace) {
            $message = str_replace($search, $replace, $message);
        }
        
        return TF::colorize($message);
    }
    
    public function getPrefix(): string {
        return TF::colorize($this->messagesConfig->get("prefix", "&8[&6AutoBroadcast&8] &r"));
    }
    
    public function getSettings(): array {
        return $this->config->get("settings", []);
    }

    public function getSetting(string $key, $default = null) {
        $settings = $this->getSettings();
        return $settings[$key] ?? $default;
    }
    
    public function setSetting(string $key, $value): void {
        $settings = $this->getSettings();
        $settings[$key] = $value;
        $this->config->set("settings", $settings);
    }
    
    public function getBroadcastMessages(): array {
        return $this->config->get("messages", []);
    }
    
    public function addBroadcastMessage(string $message): void {
        $messages = $this->getBroadcastMessages();
        $messages[] = $message;
        $this->config->set("messages", $messages);
    }
    
    public function removeBroadcastMessage(int $index): bool {
        $messages = $this->getBroadcastMessages();
        
        if(!isset($messages[$index])) {
            return false;
        }
        
        unset($messages[$index]);
        $messages = array_values($messages);
        $this->config->set("messages", $messages);
        return true;
    }
  
    
    public function getSpecialMessages(): array {
        $special = $this->config->get("special-messages", []);
        return $special["rare-messages"] ?? [];
    }
    
    public function areSpecialMessagesEnabled(): bool {
        $special = $this->config->get("special-messages", []);
        return $special["enabled"] ?? false;
    }
    
    public function getSpecialMessageChance(): int {
        $special = $this->config->get("special-messages", []);
        return $special["chance"] ?? 10;
    }
    
    public function getTimedBroadcasts(): array {
        $timed = $this->config->get("timed-broadcasts", []);
        return $timed["broadcasts"] ?? [];
    }
    
    public function areTimedBroadcastsEnabled(): bool {
        $timed = $this->config->get("timed-broadcasts", []);
        return $timed["enabled"] ?? false;
    }
    
    public function validateConfig(): array {
        $errors = [];
        
        $interval = $this->getSetting("interval");
        if($interval === null || $interval < 10) {
            $errors[] = "El intervalo debe ser al menos 10 segundos";
        }
        
        $mode = $this->getSetting("mode");
        if(!in_array($mode, ["sequential", "random", "shuffle"])) {
            $errors[] = "Modo inválido. Debe ser: sequential, random o shuffle";
        }
        
        $messages = $this->getBroadcastMessages();
        if(empty($messages)) {
            $errors[] = "No hay mensajes configurados";
        }
        
        $minPlayers = $this->getSetting("minimum-players");
        if($minPlayers !== null && $minPlayers < 0) {
            $errors[] = "minimum-players no puede ser negativo";
        }
        
        return $errors;
    }
    
    public function getConfigInfo(): array {
        return [
            "enabled" => $this->getSetting("enabled", false),
            "interval" => $this->getSetting("interval", 180),
            "mode" => $this->getSetting("mode", "sequential"),
            "format" => $this->getSetting("format", "broadcast"),
            "prefix" => $this->getSetting("prefix", ""),
            "minimum_players" => $this->getSetting("minimum-players", 1),
            "total_messages" => count($this->getBroadcastMessages()),
            "special_enabled" => $this->areSpecialMessagesEnabled(),
            "timed_enabled" => $this->areTimedBroadcastsEnabled()
        ];
    }
    
   public function resetToDefaults(): void {
        $this->plugin->saveResource("config.yml", true);
        $this->plugin->saveResource("messages.yml", true);
        $this->loadConfigs();
    }
    
   public function exportConfig(): array {
        return [
            "config" => $this->config->getAll(),
            "messages" => $this->messagesConfig->getAll()
        ];
    }
}