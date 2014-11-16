<?php
namespace Minifixio\mffreeze\manager;

use pocketmine\scheduler\PluginTask;
use pocketmine\Server;
use pocketmine\utils\TextFormat;
use Minifixio\mffreeze\MFFreeze;
use Minifixio\mffreeze\manager\FreezeManager;

class UnFreezeTask extends PluginTask{
	
	public $freezemanager;
	
	public function __construct(MFFreeze $plugin, FreezeManager $freezemanager){
		parent::__construct($plugin);
		$this->plugin = $plugin;
		$this->freezemanager = $freezemanager;
		$this->plugin->getServer()->broadcastMessage($freezemanager);
	}
	
	public function onRun($currentTick){
		$this->freezemanager->unFreezePlayers();
	}
}