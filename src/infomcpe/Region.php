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
use pocketmine\Player;
use pocketmine\Server;
use pocketmine\plugin\PluginDescription;
use infomcpe\CheckVersionTask;
use pocketmine\event\block\SignChangeEvent;
//use infomcpe\UpdaterTask; WIP

class Region extends PluginBase implements Listener {
	   const Prfix = '§f[§aIRegion§f]§e ';
    public function onLoad(){
	}
       
	public function onEnable(){
			if(!is_dir($this->getDataFolder())){
				@mkdir($this->getDataFolder());
                                @mkdir($this->getDataFolder().'data');
                        }

			$this->getServer()->getPluginManager()->registerEvents($this, $this);
			//$this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this));

                        if ($this->getServer()->getPluginManager()->getPlugin("PluginDownloader")) {
                            $this->getServer()->getScheduler()->scheduleAsyncTask(new CheckVersionTask($this, 317));
                        }
                        if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
               $this->eco = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI");
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
        public function WorldGuardian() {
            return $this->getServer()->getPluginManager()->getPlugin('WorldGuardian');
        }
        public function region() {
            return $this->getServer()->getPluginManager()->getPlugin($this->getRegionPlugin());
        }
        public function getRegionPlugin() {
            if($this->wguard() != NULL){
                $result = 'WGuard';
            }elseif($this->ProtectionAreas() != NULL){
                $result = 'ProtectionAreas';
            }elseif ($this->WorldGuardian()) {
                $result = 'WorldGuardian';
        }
            return $result;
        }
        public function changeOwner($region, $member) {
            if($this->getRegionPlugin() == 'ProtectionAreas' or $this->getRegionPlugin() == 'WGuard'){
                //$this->region() ->areas->set(strtolower($region), array("owners" => array(strtolower($nicname))));
                $this->getLogger()->info($this->ROAPFA($this->getOwner($region), $region, "owners", "remove"));
                $this->getLogger()->info($this->ROAPFA($member, $region, "owners", "add"));

            }elseif ($this->getRegionPlugin() == 'WorldGuardian') {
                $this->region()->db->query("UPDATE `AREAS` SET `Owner`= '".$member."' WHERE Region ='".$region."'");
        }
        }

        public function SignChange(SignChangeEvent $event) {
             if($event->getBlock()->getId() == 68 || $event->getBlock()->getId() == 63){
                   if ($event->getLine(0) == "region" ){
                       if($this->dataGet(strtolower($event->getLine(1)), 'price') != NULL){
                           $region = strtolower($event->getLine(1));

                            $event->setLine(0,"§4Продается §eРегион!");
                            $event->setLine(1, '§a'.$region);
                            $event->setLine(2, '§eЦена: '.$this->dataGet($region, 'price').'$§4');
                            $event->setLine(3, '§eВладелец: '.$this->dataGet($region, 'owner'));
                       } else {
                           $event->setLine(0,"§4Регион, не найден");
                           $event->getPlayer()->sendMessage('Регион, не найден');
                       }

                   }
             }
        }
        public function onPlayerTouch(PlayerInteractEvent $event){
                 if($event->getBlock()->getId() == 68 || $event->getBlock()->getId() == 63){
	           $sign = $event->getPlayer()->getLevel()->getTile($event->getBlock());
                   $signtext = $sign->getText();
                        if($signtext[0] == "§4Продается §eРегион!"  ){
                            $this->buyRegion($event->getPlayer(), str_replace('§a', '',$signtext[1]), $sign);
                            }
                            if ($this->sessionAPI() != NULL){
                                if($this->sessionAPI()->getSessionData($event->getPlayer()->getName(), "addsign") != null){
                                  $region = $this->sessionAPI()->getSessionData($event->getPlayer()->getName(), "addsign");
                                  $sign->setText("§4Продается §eРегион!", '§a'.$region, '§eЦена '.$this->dataGet($region, 'price').'$§4', '§eВладелец '.$this->dataGet($region, 'owner'));
                                  $this->sessionAPI()->deleteSession($event->getPlayer()->getName());
                                  $event->getPlayer()->sendMessage(Region::Prfix."Табличка изменена, успешно");
                                }
                            }
                        }
                 }

        public function getOwner($region) {
            $region = strtolower($region);
            if($this->getRegionPlugin() == 'ProtectionAreas' || $this->getRegionPlugin() == 'WGuard'){
                //$result = $this->region(->areas->get(strtolower($region))["owners"];
                  foreach ($this->region()->areas->getAll() as $name => $info) {
                      if($name == $region){
                          $result = $info["owners"][0];
                      }
                  }
            }else if ($this->getRegionPlugin() == 'WorldGuardian'){
                $data = $this->region()->db->query("SELECT * FROM AREAS WHERE  Region = '".$region."';")->fetchArray(SQLITE3_ASSOC);
                echo json_encode($data);
                $result = $data['Owner'];
            }
            $result = strtolower($result);
            return $result;
        }
        
    public function buyRegion($player, $region, $sign = null) {
          if ($this->dataGet(strtolower($region), 'price') != NULL) {
                                       if($this->getMoney($player) >= $this->dataGet($region, 'price')){
                                           $this->reduceMoney($player, $this->dataGet($region, 'price'));
                                          // $this->region()  ->areas->set(strtolower($args[1]), array("owners" => array(strtolower($sender->getName()))));
                                           if($sign){
                                                $sign->setText('§aРегион продан!', '§eВладелец: '.$player->getName());
                                           }
                                           $player->sendMessage(Region::Prfix.'Вы успешно, купили регион: '.strtolower($region));

                                           $this->addMoney($this->getOwner($region), $this->dataGet($region, 'price'));
                                           $this->changeOwner($region, $player->getName());
                                           @unlink($this->getDataFolder().'data/'.strtolower($region).'.json');
                                           $result = true;
                                       } else {
                                           $player->sendMessage(Region::Prfix.'У вас не достаточно денег для покупки, нужно: '.$this->dataGet($region, 'price').'$');
                                           $result = false;
                                       }

                                   } else {
                                       $player->sendMessage(Region::Prfix.'Регион, не найден в продаже');
                                       $result = false;
                                   }
                                   return $result;
    }
    public function getList($player, $page, $private = false) {
        $dir = str_replace('.json', '', scandir($this->getDataFolder().'data'));
        $regions = array();
        if($private == false){
        foreach ($dir as $region){
            if($this->dataGet($region, 'price')){
            $regions[] = "Регион: {$region} Цена: {$this->dataGet($region, 'price')}$ Владелец: {$this->dataGet($region, 'owner')}";
        }
        }

            }elseif ($private == true) {
             foreach ($dir as $region){
            if($this->dataGet($region, 'owner') == $player->getName()){
            $regions[] = "Регион: {$region} Цена: {$this->dataGet($region, 'price')}$ Владелец: {$this->dataGet($region, 'owner')}";
        }
        }
            }
            if($regions == null){
                $regions[] = Region::Prfix."У вас нет регионов, которые выставлены на продажу.";
            }
        $pageHeight = $sender instanceof ConsoleCommandSender ? 48 : 6;
        $chunkedRegions = array_chunk($regions, $pageHeight);
        $maxPageNumber = count($chunkedRegions);
        if(!isset($page) || !is_numeric($page) || $page <= 0) {
            $pageNumber = 1;
        }
        else if($page > $maxPageNumber){
            $pageNumber = $maxPageNumber;
        }else{
            $pageNumber = $page;
        }
        foreach($chunkedRegions[$pageNumber - 1] as $sendRegion){
            $player->sendMessage(Region::Prfix.' - ' . $sendRegion);
        }
    }
    public function cheakRegion($region) {
       if($this->getRegionPlugin() == 'ProtectionAreas' || $this->getRegionPlugin() == 'WGuard'){
           $result = $this->region()->areas->get(strtolower($region));
       }elseif ($this->getRegionPlugin() == 'WorldGuardian') {
            $result =  $data = $this->region()->db->query("SELECT * FROM AREAS WHERE  Region = '".$region."';")->fetchArray(SQLITE3_ASSOC);
        }
        return $result;
    }
        public function onCommand(CommandSender $sender, Command $command, $label, array $args){


                 //$alldata = $this->region() ->areas->getAll();
		switch($command->getName()){
                    case 'br':
                        if(count($args) == 0){
                            $sender->sendMessage("§6/br add [регион][цена] - Выставить регион на продажу \n§6/br del [регион] - Снять регион с продажи \n§6/br buy [регион] - Купить, регион \n§6/br list - Посмотреть список, продоваймыx регионов \n§6/br my - Посмотреть список, свовиx регионов в продаже\n§6/br addsign [Регион] - Создать табличку");
                        }
                        switch ($args[0]) {
                            
                            case 'add':
                               if($this->cheakRegion($args[1]) != false){
                                   if($this->dataGet($args[1], 'price') == NULL){
                                   if($this->getOwner($args[1]) == strtolower($sender->getName())){
								if(is_numeric($args[2])){
                                                                    $this->dataSave(strtolower($args[1]), "price", $args[2]);
                                                                    $this->dataSave(strtolower($args[1]), "owner", strtolower($sender->getName()));
                                                                    $sender->sendMessage(Region::Prfix.'Вы успешно выставили свой регион на продажу');
									  //this->dataSave(strtolower($sender->getName()), "region", $args[1);
									 }else{
									$sender->sendMessage(Region::Prfix.'Укажите, цену');
									   }
                                      //$this->region(->areas->set(strtolower($args[1]), array("owners" => array(strtolower($sender->getName()))));
                                   }else{
                                       $sender->sendMessage(Region::Prfix.'Вас не обноружено, как основным владельцем, удалите всеx кто есть в привате (кроме себя)');
                                   }
                                   } else {
                                       $sender->sendMessage(Region::Prfix.'Этот региогн, уже продаеться');
                                   }
                               } else {
                                   $sender->sendMessage(Region::Prfix.'Региона, не существует');
                               }

                               break;
                               case 'buy':
                                   $this->buyRegion($sender, $args[1]);
                               break;
                            case 'del':
                                if($this->region()->areas->get(strtolower($args[1])) != NULL){
                                    if($this->dataGet(strtolower($args[1]), 'owner') == strtolower($sender->getName())){
                                    @unlink($this->getDataFolder().'data/'.strtolower($args[1]).'.json');
                                    $sender->sendMessage(Region::Prfix.'Успешно, удалено');
                                    } else {
                                        $sender->sendMeessage(Region::Prfix.'Ошибка. Регион который вы пытаетесь удалить не ваш');
                                    }
                                }else {
                                   $sender->sendMessage(Region::Prfix.'Региона не существует');
                                }
                                break;;
                            case 'list':
                                $this->getList($sender, $args[1]);
                                break;
                            case 'my':
                                 $this->getList($sender, $args[1], true);
                                break;
                            case 'addsign':
                                if($args[1] != NULL){
                                    if($this->sessionAPI() != null){
                                         if($this->dataGet(strtolower($args[1]), 'owner') != null){
                                        if($this->dataGet(strtolower($args[1]), 'owner') == strtolower($sender->getName())){
                                        $this->sessionAPI()->createSession($sender->getName(), "addsign", $args[1]);
                                        $sender->sendMessage(Region::Prfix."Успешно. Теперь нажмите на табличку");
                                        } else {
                                            $sender->sendMessage( Region::Prfix."Ошибка. Регион который вы пытаетесь добавитьб не ваш");
                                        }
                                    } else {
                                        $sender->sendMessage(Region::Prfix."Регионб не найден");
                                    }
                                    } else {
                                        $sender->sendMessage( Region::Prfix."Команда не доступна отсутсвует плагин SessionAPI");
                                    }
                                } else {
                                $sender->sendMessage(Region::Prfix."Вы не указали название региона");
                                }
                                break;
                           
                }



                }
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
                $result = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->addMoney($player, $amount);
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               $result = EconomyPlus::getInstance()->addMoney($player, $amount);
               }
               return $result;
        }
        private function reduceMoney($player, $amount) {
          if($this->getServer()->getPluginManager()->getPlugin("EconomyAPI") != null){
               $result = $this->getServer()->getPluginManager()->getPlugin("EconomyAPI")->reduceMoney($player, $amount);
               }
               if($this->getServer()->getPluginManager()->getPlugin("EconomyPlus") != null){
               $result = EconomyPlus::getInstance()->reduceMoney($player, $amount);
               }
               return $result;
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
public function ROAPFA($playerForAddOrRemove, $rg, $fromRemove, $removeOrAdd){
        $PFAOR = $playerForAddOrRemove;
        $area = strtolower($rg);
        $areas = $this->region()->areas->getAll();
        $ROA = strtolower($removeOrAdd);
        $FR = strtolower($fromRemove);
        if (isset($areas[$area])) {

                if ($ROA == "add") {
                    $list = $areas[$area][$FR];
                    $list[] = $PFAOR;
                    $areas[$area][$FR] = $list;
                    $this->region()->areas->setAll($areas);
                    $this->region()->areas->save();
                    return "§eИгрок §6{$PFAOR} §eбыл добавлен в регион §6{$area}";
                } else {
                    $rlist = $areas[$area][$FR];
                    $key = array_search($PFAOR, $rlist);
                    unset($rlist[$key]);
                    $areas[$area][$FR] = NULL;
                    $this->region()->areas->setAll($areas);
                    $this->region()->areas->save();
                    return "§eИгрок §6{$PFAOR} §eбыл удален из региона §6{$area}";
                }

        } else {
            return "§eРегиона §6{$area} §eне существует";
        }
    }
                }

?>

