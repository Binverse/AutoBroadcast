<?php

namespace zaxelmb\autobroadcast\task;

use zaxelmb\autobroadcast\Loader;
use pocketmine\scheduler\Task;

class BroadcastTask extends Task {
    
    private $plugin;
    
    public function __construct(Loader $plugin) {
        $this->plugin = $plugin;
    }
    
    public function onRun(): void {
        $manager = $this->plugin->getBroadcastManager();
        $manager->sendBroadcast();
    }
}