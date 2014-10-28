<?php

namespace Minifixio\mffreeze;

use pocketmine\plugin\PluginBase;
use pocketmine\command\CommandSender;
use pocketmine\command\Command;
use pocketmine\Player;
        
use Minifixio\mffreeze\manager\FreezeManager;
use Minifixio\mffreeze\manager\UnFreezeTask;

class MFFreeze extends PluginBase {
    
    public $freezemanager;
    
    public function onEnable(){
        $this->freezemanager = new FreezeManager($this);
        
        $time = 1 * 20;
        
        $unFreezeTask = new UnFreezeTask($this, $this->freezemanager);
        
        $this->getServer()->getScheduler()->scheduleRepeatingTask($unFreezeTask, $time);
    }
    
    public function onDisable() {}
    
    public function onCommand(CommandSender $sender, Command $command, $label, array $args) {
        
        $cmd = strtolower($command->getName());
        //tip: Using $command->getName() allows use for aliases. Using $label would make aliases useless.
        $this->getServer()->broadcastMessage("let's go my friends 2!");
        switch ($cmd){
                           
            case "mffreeze":
                if (count($args) == 0) {
                    if ($sender->hasPermission("mffreeze.freeze")){
                        return false;
                    }
                }
                
                if (count($args) == 1){
                    if ($sender->hasPermission("mffreeze.freeze")){
                        $target = $this->getServer()->getPlayer($args[0]);
                        if ($target == null){
                            $sender->sendMessage("Player '".$args[0]."' was not found!");
                            return true;
                        } else {
                            $this->freezemanager->freezePlayer($target, $sender);
                            return true;
                        }
                    }
                }
                break;
                
            case "mfunfreeze":
                if (count($args) == 0) {
                    if ($sender->hasPermission("mffreeze.unfreeze")){
                        return false;
                    }
                }
                
                if (count($args) == 1){
                    if ($sender->hasPermission("mffreeze.unfreeze")){
                        $target = $this->getServer()->getPlayer($args[0]);
                        if ($target == null){
                            $sender->sendMessage("Player '".$args[0]."' was not found!");
                            return true;
                        } else {
                            $this->freezemanager->unfreezePlayer($target, $sender);
                            return true;
                        }
                    }
                }
                break;
        }
        
        return true;
    }
    
}