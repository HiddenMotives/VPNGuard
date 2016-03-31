<?php
namespace basprohop\libraries;

use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Server;

class Async extends AsyncTask {
    private $data;
    public function __construct($mode, $player, $ipAddress, $userAgent, $cfg, $cfgCommands, $cache)
    {
        $this->mode = $mode; //Either 1 or 2, 1 being on player Login, 2 being on /vpnguard lookup command.
        $this->player = $player; $this->ip = $ipAddress;
        $this->userAgent = $userAgent; $this->cfg = $cfg; $this->cfgCommands = $cfgCommands;
        $this->cache = serialize($cache);
    }

    public function onRun() {
        if(!empty($this->cfg["api-key"])) {
            $api = "http://tools.xioax.com/networking/ip/" . $this->ip . "/" . $this->cfg["api-key"];
        } else {
            $api = "http://tools.xioax.com/networking/ip/" . $this->ip;
        }

        $this->cache = unserialize($this->cache);
        if($this->cache instanceof SimpleCache) {
            if($this->cache->is_cached($this->ip)) {
                $this->data = $this->cache->get_cache($this->ip);
                $this->data = serialize($this->data);
            } else {
                $this->data = $this->cache->do_curl($api, $this->cfg["timeout"], $this->userAgent);
                $this->data = serialize($this->data);
            }
        } else {
            $this->data = $this->cache->do_curl($api, $this->cfg["timeout"], $this->userAgent);
            $this->data = serialize($this->data);
        }
        $this->cache = serialize($this->cache);
    }

    public function onCompletion(Server $server){

        $this->cache = unserialize($this->cache);
        $this->data = unserialize($this->data);
        $obj = json_decode($this->data, true);
        $player = $server->getPlayer($this->player);

        if(empty($obj)) {
            if($this->mode == 1) {
                $server->getLogger()->critical("API Server Seems to be Down");
                if (!$this->cfg["bypass-check"]) {
                    $player->close("", $this->cfg["bypass-message"]);
                }
            } else if($this->mode == 2) {
                $player->sendMessage("API Server Seems to be Down, try again later.");
            }
        } else {

            if ($obj['status'] === "success") {

                //Cache only if API returned success status string
                if($this->cache instanceof SimpleCache) {
                    if(!$this->cache->is_cached($this->ip)) {
                        $this->cache->set_cache($this->ip, $this->data);
                    }
                }
                if ($obj['host-ip']) {
                    $provider = $obj["org"];
                    $countryCode = $obj["cc"];

                    if($this->mode == 1) {
                        if($player != null) {

                            foreach ($this->cfgCommands as $command) {
                                $command = str_replace("%p", $player->getName(), $command);
                                $server->dispatchCommand(new ConsoleCommandSender(), $command);
                            }

                            if($this->cfg["logging"]) {
                                $server->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                                    " tried connecting with a anonymizer: IP Details -> " . $provider . "," . $countryCode);
                            }
                        }
                    } else if ($this->mode == 2) {
                        $player->sendMessage($this->ip . " belongs to a hosting organization");
                        $player->sendMessage("IP Details: " . $provider . "," . $countryCode);
                    }

                } else {
                    if($this->mode == 1) {
                        if($player != null) {
                            if($this->cfg["logging"]) {
                                $server->getLogger()->info(TEXTFormat::GREEN . $player->getName() . TextFormat::WHITE .
                                    " has passed VPNGuard checks.");
                            }
                        }
                    } else if ($this->mode == 2) {
                        $player->sendMessage($this->ip . " does not seem to belong to a hosting organization.");
                        $player->sendMessage("If you believe this is an error please report it to us to have it fixed.");

                    }
                }
            } else {
                if($this->mode == 1) {
                    if($player != null) {
                        $server->getLogger()->warning(TextFormat::WHITE . "API Server Returned Error Message: " .
                            TextFormat::RED . $obj['msg'] . TextFormat::WHITE . " when " . TextFormat::GOLD . $player->getName() .
                            TextFormat::WHITE . " tried to connect");

                        if (((strpos($obj['msg'], "Invalid API Key")) || (strpos($obj['msg'], "Payment Overdue"))) === false) {
                            $server->getLogger()->critical("Shutting down server to prevent blacklisting on API Database");
                            $server->getLogger()->alert("Plugin 'VPNGuard' attempted to stop the server but was stopped in mid-process due to translation error from counter-plugin NoMoreCrash")
                            return;
                        } else {
                            if (!$this->cfg["bypass-check"]) {
                                $player->close("", $this->cfg["bypass-message"]);
                            }
                        }
                    }
                } else if ($this->mode == 2) {
                    $player->sendMessage("API Server Returned Error Message: " . $obj["msg"]);
                }
            }
        }
    }


}
