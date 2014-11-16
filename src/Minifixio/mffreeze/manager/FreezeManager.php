<?php

namespace Minifixio\mffreeze\manager;

use pocketmine\plugin\PluginBase;
use pocketmine\event\Listener;
use pocketmine\event\entity\EntityMoveEvent;
use pocketmine\event\entity\EntityDamageByEntityEvent;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\Player;
use pocketmine\command\CommandSender;
use Minifixio\mffreeze\manager\FreezeInfo;

class FreezeManager implements Listener {
    
    public $plugin;
    private $activeForWorlds;
    public $frozen = array();
    public $frozenInfo = array();
    public $freezerTimeInfo = array();
    
    const FREEZING_ITEM_ID = 341;
    const FREEZE_TIME_IN_SECONDS = 3;
    const FREEZE_RIGHT_DELAY_IN_SECONDS = 15;
    
    public function __construct(PluginBase $plugin, $activeForWorlds) {
        $this->plugin = $plugin;
        $this->activeForWorlds = $activeForWorlds;
        $this->plugin->getServer()->getPluginManager()->registerEvents($this, $this->plugin);
    }
    
    public function freezePlayer(Player $player, CommandSender $sender){
        $id = $player->getID();
        $name = $player->getName();
        
        $freezeInfo = new FreezeInfo();
        
        $now = new \DateTime(); 
        $freezeInfo->freezeTime = $now->getTimestamp();
        $freezeInfo->victim = $player;
        
        if (in_array($id, $this->frozen)){
            $sender->sendMessage($this->formatPlayerMessage($name." est déjà congelé"));
        } else {
            $this->frozenInfo[$name] = $freezeInfo;
            $this->frozen[$name] = $id;
            $this->freezerTimeInfo[$sender->getName()] = $now->getTimestamp();
        }
    }
    
    public function unfreezePlayer(Player $player, CommandSender $sender){
        $id = $player->getID();
        $name = $player->getName();
        if (in_array($id, $this->frozen)){
            $index = array_search($id, $this->frozen);
            if ($index === false){
                $sender->sendMessage($this->formatPlayerMessage($name." n'était pas congelé"));
            } else {
                unset($this->frozen[$index]);
                $sender->sendMessage($this->formatPlayerMessage($name." a été congelé"));
                $player->sendMessage($this->formatPlayerMessage("Vous avez été décongelé"));
            }
        }
    }
    
    public function getFrozenPlayers(){
        return $this->frozen;
    }
    
    /**
     * Check frozen players and unfreeze them if the remaining time was overlapsed 
     */
    public function unFreezePlayers(){
    	$now = new \DateTime();
    	foreach ($this->frozenInfo as $key => $freezeInfo) {
    		$remainingFreezeTime = $freezeInfo->freezeTime + self::FREEZE_TIME_IN_SECONDS - $now->getTimestamp();
    		$freezeInfo->victim->sendMessage($this->formatPlayerMessage("Décongelation dans " . $remainingFreezeTime . " secondes ..."));
    		if($now->getTimestamp() - $freezeInfo->freezeTime >= self::FREEZE_TIME_IN_SECONDS){
    			$freezeInfo->victim->sendMessage($this->formatPlayerMessage("Vous avez ete décongelé"));
    			unset($this->frozen[$key]);
    			unset($this->frozenInfo[$key]);
    		}
    	}
    	
    }
    
    //Did NOT realize there was an EntityMoveEvent the first time :c
    public function onEntityMove(EntityMoveEvent $event)
    {
        $entity = $event->getEntity();
        if ($entity instanceof Player){
            $player = $entity;
            foreach ($this->frozen as $name => $id){
                if ($player->getName() === $name && $player->getID() === $id){
                    $event->setCancelled();
                }
            }
        }
    }
    
    
    /**
     * When a player damages another player with the freezing item
     * @param EntityDamageByEntityEvent $event
     */
    public function onEntityDamageByEntity(EntityDamageByEntityEvent $event){
    	    	
    	$this->logOnConsole($this->activeForWorlds);
    	
    	// Check that its an entity by entity damage
    	if($event instanceof EntityDamageByEntityEvent){
	    	$damager = $event->getDamager();
	    	$victim = $event->getEntity();  	
	    	
	    	// Check that both entities are players
	    	if($damager instanceof Player && $victim instanceof Player){
	    		$itemInHand = $damager->getInventory()->getItemInHand();
	    		
	    		// Check that the player has the right item
	    		if($itemInHand->getID() == self::FREEZING_ITEM_ID){

	    			// Check that MFFreeze is activated for current world
	    			$worldName = $event->getEntity()->getLevel()->getFolderName();
	    			if($worldName != "world"){
	    				$damager->sendMessage($this->formatPlayerMessage("Congélation désactivée pour ce monde"));
	    				return;
	    			}	    			
	    			
	    			if(isset($this->freezerTimeInfo[$damager->getName()])){
	    				$now = new \DateTime();
	    				$timeSinceLastFreeze = $now->getTimestamp() - $this->freezerTimeInfo[$damager->getName()]; 
	    				if($timeSinceLastFreeze < self::FREEZE_RIGHT_DELAY_IN_SECONDS){
	    					$damager->sendMessage($this->formatPlayerMessage("Impossible de congeler un joueur avant " . (self::FREEZE_RIGHT_DELAY_IN_SECONDS - $timeSinceLastFreeze) . " secondes !"));
	    					return ;
	    				}
	    			}
    				$damager->sendMessage($this->formatPlayerMessage("Vous avez congelé " . $victim->getName()));
    				$victim->sendMessage($this->formatPlayerMessage("Vous avez été congelé par " . $damager->getName()));
    				$this->freezePlayer($victim, $damager);
	    		}
	    	}
    	}
    }    
    
    private function logOnConsole($message){
    	$this->plugin->getServer()->broadcastMessage($message);
    }
    
    private function formatPlayerMessage($message){
    	return "/ " . $message . "\n";
    }
}