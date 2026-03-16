<?php

namespace zaxelmb\autobroadcast\command;

use zaxelmb\autobroadcast\Loader;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat as TF;
use pocketmine\utils\Config;

class BroadcastCommand extends Command {
    
    private $plugin;
    
    public function __construct(Main $plugin) {
        parent::__construct("autobroadcast", "Gestiona AutoBroadcast", "/autobroadcast <reload|toggle|send|stats|mode>", ["ab", "broadcast"]);
        $this->setPermission("autobroadcast.command");
        $this->plugin = $plugin;
    }
    
    public function execute(CommandSender $sender, string $label, array $args): bool {
        if(!$this->testPermission($sender)) {
            return false;
        }
        
        $messages = new Config($this->plugin->getDataFolder() . "messages.yml", Config::YAML);
        $prefix = TF::colorize($messages->get("prefix"));
        
        if(empty($args)) {
            $sender->sendMessage($prefix . TF::YELLOW . "Uso: /ab <reload|toggle|send|stats|mode>");
            return false;
        }
        
        switch(strtolower($args[0])) {
            case "reload":
                $this->plugin->reloadConfig();
                $this->plugin->getBroadcastManager()->loadMessages();
                $sender->sendMessage($prefix . TF::colorize($messages->get("commands")["reload-success"]));
                break;
                
            case "toggle":
                $config = $this->plugin->getConfig();
                $settings = $config->get("settings");
                $settings["enabled"] = !$settings["enabled"];
                $config->set("settings", $settings);
                $config->save();
                
                if($settings["enabled"]) {
                    $this->plugin->startBroadcastTask();
                    $sender->sendMessage($prefix . TF::colorize($messages->get("commands")["plugin-enabled"]));
                } else {
                    $this->plugin->stopBroadcastTask();
                    $sender->sendMessage($prefix . TF::colorize($messages->get("commands")["plugin-disabled"]));
                }
                break;
                
            case "send":
                if(!isset($args[1])) {
                    $this->plugin->getBroadcastManager()->sendBroadcast();
                    $sender->sendMessage($prefix . TF::GREEN . "Broadcast enviado manualmente.");
                } else {
                    $message = implode(" ", array_slice($args, 1));
                    $this->plugin->getBroadcastManager()->sendBroadcast($message);
                    $sender->sendMessage($prefix . str_replace("{message}", $message, TF::colorize($messages->get("commands")["broadcast-sent"])));
                }
                break;
                
            case "stats":
                $manager = $this->plugin->getBroadcastManager();
                $settings = $this->plugin->getConfig()->get("settings");
                
                $stats = $messages->get("commands")["stats"];
                foreach($stats as $line) {
                    $line = str_replace([
                        "{status}", "{mode}", "{interval}", "{total}", "{current}", "{sent}"
                    ], [
                        $settings["enabled"] ? TF::GREEN . "Habilitado" : TF::RED . "Deshabilitado",
                        $manager->getMode(),
                        $settings["interval"],
                        $manager->getTotalMessages(),
                        $manager->getCurrentIndex() + 1,
                        $manager->getTotalBroadcastsSent()
                    ], $line);
                    
                    $sender->sendMessage(TF::colorize($line));
                }
                break;
                
            case "mode":
                if(!isset($args[1])) {
                    $sender->sendMessage($prefix . TF::YELLOW . "Uso: /ab mode <sequential|random|shuffle>");
                    return false;
                }
                
                $mode = strtolower($args[1]);
                if(!in_array($mode, ["sequential", "random", "shuffle"])) {
                    $sender->sendMessage($prefix . TF::colorize($messages->get("commands")["invalid-mode"]));
                    return false;
                }
                
                $config = $this->plugin->getConfig();
                $settings = $config->get("settings");
                $settings["mode"] = $mode;
                $config->set("settings", $settings);
                $config->save();
                
                $sender->sendMessage($prefix . TF::GREEN . "Modo cambiado a: " . TF::YELLOW . $mode);
                break;
                
            default:
                $sender->sendMessage($prefix . TF::YELLOW . "Subcomando inválido.");
                break;
        }
        
        return true;
    }
}