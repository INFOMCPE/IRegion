<?php
namespace infomcpe;

use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\utils\Utils;
use pocketmine\event\player\PlayerJoinEvent;
use pocketmine\utils\Config;
use pocketmine\event\Listener;
use pocketmine\plugin\Plugin;
use pocketmine\Server;
use pocketmine\plugin\PluginDescription;
use infomcpe\CheckVersionTask;

//use infomcpe\UpdaterTask; WIP

class Region extends PluginBase implements Listener {
	
    public function onLoad(){
	} 
        private function getMoney($player) {
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
                $money = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->myMoney($player); 
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               	
               $money = EconomyPlus::getInstance()->getMoney($player);
               }
               return $money;
        }
        private function addMoney($player, $amount) {
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
               $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->addMoney($player, $amount); 
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               	
               EconomyPlus::getInstance()->addMoney($player, $amount); 
               }
               
        }
        private function reduceMoney($player, $amount) {
            if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
               $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player, $amount); 
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               	
               EconomyPlus::getInstance()->reduceMoney($player, $amount); 
               }
               
        }
	private function dataSave($playerName, $tip, $data){ 
           $Sfile = (new Config($this->getDataFolder() . "data/".strtolower($playerName).".json", Config::JSON))->getAll(); 
           $Sfile[$tip] = $data;
           $Ffile = new Config($this->getDataFolder() . "data/".strtolower($playerName).".json", Config::JSON); 
           $Ffile->setAll($Sfile); 
           $Ffile->save(); 
} 
	public function dataGet($playerName, $tip){ 
        $Sfile = (new Config($this->getDataFolder() . "data/".strtolower($playerName).".json", Config::JSON))->getAll(); 
        return $Sfile[$tip]; 
} 
	public function onEnable(){
			if(!is_dir($this->getDataFolder())){
				@mkdir($this->getDataFolder());
                                @mkdir($this->getDataFolder().'data');
                        }
			$this->saveDefaultConfig();
			$this->getServer()->getPluginManager()->registerEvents($this, $this);
			//$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this));
			$this->data = (new Config($folder.'data.json', Config::JSON))->getAll();
                        if ($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")) {
                            $this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this, 317)); 
                        }
		
    }
        public function onPlayerJoin(PlayerJoinEvent $event) {
            $player = $event->getPlayer();
            if($this->data[strtolower($player->getName())] == NULL){
                $this->data[strtolower($player->getName())] = array();
            }
        }
	public function onDisable(){
	}
        public function sessionAPI() {
            return $this->getServer()->getPluginManager()->getPlugin('SessionAPI');
        }
        public function wguard() {
            return $this->getServer()->getPluginManager()->getPlugin('WGuard');
        }
        public function ProtectionAreas() {
            return $this->getServer()->getPluginManager()->getPlugin('ProtectionAreas');
        }
        public function getRegionPlugin() {
            
            if($this->wguard() != NULL){
                $result = 'WGuard';
                
            }elseif($this->ProtectionAreas() != NULL){
                $result = 'ProtectionAreas';
                
            }
            return $result;
        }
        
        public function onCommand(CommandSender $sender, Command $command, $label, array $args){
            
		 $this->region = $this->getServer()->getPluginManager()->getPlugin($this->getRegionPlugin());
                 //$alldata = $this->region->areas->getAll();
		switch($command->getName()){
                    case 'br':
                        if(count($args) == 0){
                            $sender->sendMessage("§9§l—————§aBuy§aRegin§9—————\n§6/br add [регион][цена] - Выставить на продажу регион \n§6/br del [регион] - Снять регион с продажи \n§6/br buy [регион] - Купить регион");
                        }
                        switch ($args[0]) {
                            case 'add':
                               if($this->region->areas->get(strtolower($args[1])) != NULL){
                                   if($this->dataGet($args[1], 'price') == NULL){
                                   if($this->region->areas->get(strtolower($args[1]))["owners"] == array(strtolower($sender->getName()))){
								if(is_numeric($args[2])){
                                                                    $this->dataSave(strtolower($args[1]), "price", $args[2]);
                                                                    $this->dataSave(strtolower($args[1]), "owner", strtolower($sender->getName()));
                                                                    $sender->sendMessage('Вы успешно выставили свой регион не продажу');
									  //this->dataSave(strtolower($sender->getName()), "region", $args[1);
									 }else{
									$sender->sendMessage('Укажите цену');
									   }
                                      //$this->region->areas->set(strtolower($args[1]), array("owners" => array(strtolower($sender->getName())))); 
                                   }else{
                                       $sender->sendMessage('Вас не обноружено как основным владельцем удалите всеx кто есть в привате (кроме себя)');
                                   }
                                   } else {
                                       $sender->sendMessage('Этот региогн уже продаеться');
                                   }
                               } else {
                                   $sender->sendMessage('Региона не существует');
                               }
                           
                               break;
//                            case 'addsign':
//                                $this->sessionAPI()->createSession($sender->getName(), 'createSign', TRUE);
//                                
//                                break;
                               case 'buy':
                                   if ($this->dataGet(strtolower($args[1]), 'price') != NULL) {
                                       if($this->getMoney($sender) >= $this->dataGet($args[1], 'price')){
                                           $this->reduceMoney($sender, $this->dataGet($args[1], 'price'));
                                           $this->region->areas->set(strtolower($args[1]), array("owners" => array(strtolower($sender->getName()))));
                                           $sender->sendMessage('Вы успешно купили регион: '.strtolower($args[1]));
                                           $lastOwner = $this->getServer()->getPlayer($this->dataGet(strtolower($args[1]), 'owner'));
                                           $this->addMoney($lastOwner, $this->dataGet($args[1], 'price'));
                                           @unlink($this->getDataFolder().'data/'.strtolower($args[1]).'.json');
                                           
                                       } else {
                                           $sender->sendMessage('У вас не достаточно денег для покупки нужно: '.$this->dataGet($args[1], 'price').'$');
                                       }
                                       
                                   } else {
                                       $sender->sendMessage('Регион не найден в продаже');
                                   }
                               break;
                            case 'del':
                                if($this->region->areas->get(strtolower($args[1])) != NULL){
                                    if($this->dataGet(strtolower($args[1]), 'owner') == strtolower($sender->getName())){
                                    @unlink($this->getDataFolder().'data/'.strtolower($args[1]).'.json');
                                    $sender->sendMessage('Успешно удалено');
                                    } else {
                                        $sender->sendMeessage('Ошибка. Регион который вы пытаетесь удалить не ваш');
                                    }
                                }else {
                                   $sender->sendMessage('Региона не существует');
                                }
                                break;;
                }
                        
                 
                
                }
        }
                
                }
        



?>

