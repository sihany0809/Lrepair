<?php

namespace Lrepair;

use pocketmine\block\Block;
use pocketmine\network\mcpe\protocol\PlayerInputPacket;
use pocketmine\plugin\PluginBase;
use pockketmine\event\Listener;
use onebone\economyapi\EconomyAPI;
use pocketmine\command\Command;
use pocketmine\command\Commandsender;
use pocketmine\utils\Config;
use pocketmine\event\player\PlayerInteractEvent;
use pocketmine\event\block\BlockBreakEvent;
use pocketmine\event\block\SignChangeEvent;
use pocketmine\entity\EffectInstance;
use TEffectAPI\TEffectAPI;

class main extends PluginBase implements Listener {
private $config, $cf;
private $data, $db;
public function onEnable() {
$this->getServer()->getPluginManager()->registerEvents($this, $this);
$this->getServer()->getLogger()->notice("수리 플러그인-made by sihun0809");
@mkdir($this->getDataFolder());
$this->config = new Config($this->getDataFolder(). "Config.yml", Config::YAML, [
"lrepair-price" => [],
"lrepair-percent" => []
]);
$this->data = new Config($this->getDataFolder()."Data.yml", Config::YAML, [
"signs" => []
]);

$this->cf = $this->config->getAll();
$this->db = $this->data->getAll();
}

public function save() {
$this->config->setAll($this->cf);
$this->config->save();
$this->data->setAll($this->db);
$this->data->save();
}

public function onCommand(Commandsender $sender, Command $command, string $label, array $args):bool {
$command = $command->getName();
if($command =="수리생성") {
if($sender->isOp()) {
$sender->sendMessage($this->cf, "권한이 없습니다");
return true;
}
if(!isset($args[0]))
$args [0] = 'x';
switch ($args[0]){
    case "수리가격설정":
        if (!isset($args[1])) {
            $sender->sendMessage($this->cf."수리가격을 입력하세요");
            return true;
        }
        if (!is_numeric($args[1])){
            $sender->sendMessage($this->cf."가격은 숫자로만 입력해야합니다");
            return true;
        }
        $this->cf ["lrepair-price"] = $args[1];
        $sender->sendMessage($this->cf."이 수리의 가격을 {$args[1]}으로 설정하였습니다");
        $this->save();
        break;
    case "확률설정":
        if (!isset($args[1])){
            $sender->sendMessage($this->cf."수리 성공 확률을 입력하세요");
            return true;
        }
        if (!is_numeric($args[1])){
            $sender->sendMessage($this->cf."수리 확률은 숫자로만 입력해야합니다");
            return true;
        }
        $this->cf["lrepair-percent"]=$args[1];
        $sender->sendMessage($this->cf."수리 성공 확률을 {$args[1]}%로 설정하였습니다");
        $this->save();
        break;
    default:
        $sender->sendMessage("/수리가격설정");
        }
}
}
       public function onInteract(PlayerInteractEvent $event){
    $player = $event->getPlayer();
    $item = $player->getInventory()->getItemInHand();
    $id = $item->getId();
    $block = $event->getBlock();
    $x = $block->x;
    $y = $block->y;
    $z = $block->z;
    $damage = $item->getDamage();
    $money = $this->cf["lrepair-price"];
    if (!isset($this->db["signs"][$x.$y.$z])){
        if ($id == 0){
            $player->sendMessage($this->cf."수리를 할 아이템을 들어주세요");
            return true;
        }
        if($damage < 1){
            $player->sendMessage($this->cf."이 아이템을 수리를 할 필요가 없습니다");
            return true;
        }
        if(EconomyAPI::getInstance()->myMoney($player)<$money ){
            $player->sendMessage("수리를 할 돈이 부족합니다");
            return true;
        }
        $rand = mt_rand(1,(int) $this->getPercent());
        if ($rand! ==1){
            $player->sendMessage("수리를 실패하였습니다..");
            EconomyAPI::getInstance()->reduceMoney($player, $money);
            TEffectAPI::getInstance()->setEffect($player, 18);
            return true;
        }
        EconomyAPI::getInstance()->reduceMoney($player, $money);
        $item->setDamage(0);
        $player->getInventory()->setItemlnHand($item);
        $player->sendMessage($this->cf."아이템이 수리 완료 되었습니다!");
        TEffectAPI::getInstance()->setEffect($player, 5);
    }
       }
       public function onDisable()
       {
           $this->save();
           $this->getLogger()->info("Lrepair플러그인이 비활성화 되었습니다");
       }
       public function onLoad()
       {
           $this->getLogger()->info("Lrepair플러그인이 활성화 되었습니다");
       }
       public function getPercent(){
    $a = $this->cf["lrepair-percent"];
    if(!is_numeric($a))
        return false;
    $b = ceil((int)100/$a);
    return $b;
       }
       public function onSignPoint(SignChangeEvent $event){
    $block = $event->getBlock();
    $x = $block->x;
    $y = $block->y;
    $z = $block->z;
    $line = $event->getLines();
    if ($line[0]=="아이템 수리"){
        if (!$event->getPlayer()->isOp())
            return;
        $event->setLine(0,"[수리 표지판]");
        $event->setLine(1,"수리 가격:".$this->cf["lrepair-price"]);
        $event->setLine(3, "수리 확률:".$this->cf["lrepair-percent"]);
        $event->getPlayer()->sendMessage($this->cf."수리 표지판을 생성하였습니다");
        $this->setRepairSign($x, $y, $z);
        $this->save();
    }
       }
       public function DisableSignPoint(BlockBreakEvent $event){
    $block = $event->getBlock();
    $x = $block->x;
    $y = $block->y;
    $z = $block->z;
    if ($block->getId()==Block::SIGN_POST || $block->getId()==Block::WALL_SIGN) {
        if ($event->getPlayer()->isOp()){
            if (isset($this->db["signs"][$x.$y.$z])){
                unset($this->db["signs"][$x.$y.$z]);
                $event->getPlayer()->sendMessage($this->cf."수리 표지판 제거 완료");
            }
        }
    }
       }

}
