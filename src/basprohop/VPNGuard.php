<?php
namespace basprohop;

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
    public $apiKey, $cache;
    private $cfg, $commands;

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


        if(!empty($this->cfg["api-key"])) {
            $this->apiKey = $this->cfg["api-key"];
        } else {
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
        $obj = $this->getJSON($ipAddress);

        if(empty($obj)) {
            $this->getLogger()->critical("API Server Seems to be Down");
            if($this->cfg["bypass-check"]) {
                return;
            } else {
                $player->close("", $this->cfg["bypass-message"]);
                $event->setCancelled();
            }
        } else {

            if ($obj['status'] === "success") {
                if ($obj['host-ip']) {
                    $provider = $obj["org"];
                    $countryCode = $obj["cc"];
                    $player->close("", $this->cfg["kick-message"]);

                    $this->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                        " has been disconnected for using an anonymizer: IP Details -> " . $provider . "," . $countryCode);

                    $event->setCancelled();
                } else {
                    $this->getLogger()->info(TEXTFormat::GREEN . $player->getName() . TextFormat::WHITE .
                        " has passed VPNGuard checks.");
                    return;
                }
            } else {
                $this->getLogger()->warning(TextFormat::WHITE . "API Server Returned Error Message: " .
                    TextFormat::RED . $obj['msg'] . TextFormat::WHITE . " when " . TextFormat::GOLD . $player->getName() .
                    TextFormat::WHITE . " tried to connect");
                if ($this->cfg["bypass-check"]) {
                    return;
                } else {
                    $player->close("", $this->cfg["bypass-message"]);
                    $event->setCancelled();
                }
            }
        }
    }

    /**
     * Function that pulls information up regarding an IP address.
     *
     * @param $ip - IP that wil be validated.
     * @return string - JSON formatted response from API server
     */
    public function getJSON($ip)
    {
        if(!empty($this->apiKey)) {
            $api = "http://tools.xioax.com/networking/ip/" . $ip . "/" . $this->apiKey;
        } else {
            $api = "http://tools.xioax.com/networking/ip/" . $ip;
        }

        $timeout = $this->cfg["timeout"];
        $userAgent = ("VPNGuard (PocketMine:" .
            $this->getServer()->getVersion() . ") on " . $this->getServer()->getPort());


        if($this->cache instanceof SimpleCache) {
            if ($data = $this->cache->get_cache($ip)) {
                $data = json_decode($data, true);
            } else {
                $data = $this->cache->do_curl($api, $timeout, $userAgent);

                $this->cache->set_cache($ip, $data);
                $data = json_decode($data, true);
            }
        } else {
            $data = SimpleCache::do_curl($api, $timeout, $userAgent);
            $data = json_decode($data, true);
        }
        return $data;
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
