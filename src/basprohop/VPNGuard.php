<?php
namespace basprohop;

use basprohop\libraries\Async;
use basprohop\libraries\SimpleCache;
use basprohop\libraries\CommandFunctions;
use pocketmine\event\Listener;
use pocketmine\event\player\PlayerPreLoginEvent;
use pocketmine\plugin\PluginBase;
use pocketmine\command\Command;
use pocketmine\command\CommandSender;
use pocketmine\utils\TextFormat;


class VPNGuard extends PluginBase implements Listener
{
    public $cache, $cfg;
    private $commands;

    public function onEnable()
    {
        @mkdir($this->getDataFolder());
        $this->saveDefaultConfig();
        $this->cfg = $this->getConfig()->getAll();
        
        $this->commands = new CommandFunctions($this);

        //If API Cache is Enabled make Cache folder and initialize $cache
        if($this->cfg["api-cache"]) {
            @mkdir($this->getDataFolder() . "cache/");

            $this->cache = new SimpleCache();

            $this->cache->cache_path = $this->getDataFolder() . "cache/";
            $this->cache->cache_time = ($this->cfg["api-cache-time"] * 3600);
        }


        if(empty($this->cfg["api-key"])) {
            $this->getLogger()->info(TextFormat::YELLOW . "No API key specified, using free version.");
        }
        $this->getServer()->getPluginManager()->registerEvents($this, $this);
    }

    public function onPlayerLogin(PlayerPreLoginEvent $event)
    {
        $player = $event->getPlayer();
        $ipAddress = $player->getAddress();
        $this->getLogger()->info(TextFormat::WHITE . "Player " . TextFormat::GOLD . $player->getName() .
            TextFormat::WHITE . " is trying to connect with IP: " . TextFormat::GRAY . $ipAddress);

        $this->getServer()->getScheduler()->scheduleAsyncTask(
            new Async(1, $player->getName(), $ipAddress, $this->getUserAgent(), $this->cfg, $this->cache));
    }

    /**
     * Used for internal networking purposes.
     * Function that makes a useragent based on Plugin version and PocketMine version.
     * @return string - User agent.
     */
    public function getUserAgent() {
       return ("VPNGuard v" . $this->getDescription()->getVersion() . " (PocketMine:" .
            $this->getServer()->getVersion() . ") on " . $this->getServer()->getPort());
    }

    /**
     * Function that appends the VPNGuard prefix.
     * @param $msg - message to be appended.
     * @return string - appending string with prefix.
     */
    public function msg($msg) {
        return TextFormat::DARK_GRAY . "[" . TextFormat::RED ."VPNGuard" .
        TextFormat::DARK_GRAY. "] " . TextFormat::WHITE .$msg;
    }

    //********************************* Commands ********************************//
    public function onCommand(CommandSender $sender, Command $command, $label, array $args){
        $numArgs = count($args);
        $insufficientPerm = $this->msg(TextFormat::RED."You do not have permission to use this command!");
        switch($command->getName()){
            case "vpnguard":
                if ($sender->hasPermission("vpnguard.command.vpnguard")) {
                    if ($numArgs == 0) {
                        $this->commands->cmdEmpty($sender);
                        return true;
                    } else if ($numArgs >= 1) {
                        if( strtolower($args[0]) === "clearcache") {
                            if ($sender->hasPermission("vpnguard.command.clearcache")) {
                                $this->commands->cmdClearCache($sender);
                            } else {
                                $sender->sendMessage($insufficientPerm);
                            }
                            return true;
                        } else if( strtolower($args[0]) === "clearip") {
                            if ($sender->hasPermission("vpnguard.command.clearip")) {
                                if(($numArgs == 2)) {
                                    $this->commands->cmdClearIP($sender, $args[1]);
                                } else {
                                    $sender->sendMessage($this->msg("Usage: /vpnguard clearip <ipv4 address>"));
                                }
                            } else {
                                $sender->sendMessage($insufficientPerm);
                            }
                            return true;
                        } else if( strtolower($args[0]) === "lookup") {
                            if ($sender->hasPermission("vpnguard.command.lookup")) {
                                if(($numArgs == 2)) {
                                    $this->commands->cmdLookup($sender, $args[1]);
                                } else {
                                    $sender->sendMessage($this->msg("Usage: /vpnguard lookup <ipv4 address>"));
                                }
                            } else {
                                $sender->sendMessage($insufficientPerm);
                            }
                            return true;
                        } else if ( strtolower($args[0]) === "about") {
                            if ($sender->hasPermission("vpnguard.command.about")) {
                                $this->commands->cmdAbout($sender);
                            } else {
                                $sender->sendMessage($insufficientPerm);
                            }
                            return true;
                        }
                        return true;
                    }
                    return true;
                } else {
                    $sender->sendMessage($insufficientPerm);
                    return true;
                }
            default:
                return false;
        }
    }
}
