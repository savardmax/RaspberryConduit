<?php

declare(strict_types=1);

namespace RaspberryConduit;

use Volatile;
use pocketmine\plugin\PluginBase;

class Main extends PluginBase {

	public function onEnable() : void{
        $this->getLogger()->info("Loading Raspberry Conduit");
        $this->conduit = new Volatile();
        $this->mythread = new Listen\Listen($this->conduit);
        $this->mythread->start();
        $plugin = $this;
        $task = new Tasks\DoTask($plugin, $this->conduit);
        $this->getScheduler()->scheduleRepeatingTask($task, 1);
	}

	public function onDisable() : void{
        $this->mythread->stop();
		$this->getLogger()->info("Stopping Raspberry Conduit");
    }
}
