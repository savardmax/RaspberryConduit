<?php

declare(strict_types=1);

namespace RaspberryConduit;

use Volatile;
use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\item\Sword;

class Main extends PluginBase implements Listener{

	public function onEnable() : void{
        $this->getLogger()->info("Loading Raspberry Conduit");
        $this->getServer()->getPluginManager()->registerEvents($this,$this);
        $this->conduit = new Volatile();
        $this->conduit_events = new Volatile();
        $this->mythread = new Listen\Listen($this->conduit);
        $this->mythread->start();
        $plugin = $this;
        $task = new Tasks\DoTask($plugin, $this->conduit, $this->conduit_events);
        $this->getScheduler()->scheduleRepeatingTask($task, 1);
    }

    public function onSwordRightClick(PlayerInteractEvent $event) {
        if ($event->getAction() == PlayerInteractEvent::RIGHT_CLICK_BLOCK){
            $player = $event->getPlayer();
            $block = $event->getBlock();
            $item = $event->getItem();
            $face = $event->getFace();
            if ($item instanceof Sword) {
                $data = [$block->x, $block->y, $block->z, $face, $player->getId()];
                $this->conduit_events[] = "$data[0],$data[1],$data[2],$data[3],$data[4]";
            }
        }

    } 

    public function onDisable() : void{
        $this->mythread->stop();
		$this->getLogger()->info("Stopping Raspberry Conduit");
    }



}
