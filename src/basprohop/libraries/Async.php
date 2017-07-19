<?php
namespace basprohop\libraries;

use basprohop\VPNGuard;
use pocketmine\scheduler\AsyncTask;
use pocketmine\utils\TextFormat;
use pocketmine\command\ConsoleCommandSender;
use pocketmine\Player;
use pocketmine\Server;

class Async extends AsyncTask {
    private $data;
    public function __construct($mode, VPNGuard $plugin, $sender, $ipAddress){
        $this->mode = $mode; //Either 1 or 2, 1 being on player Login, 2 being on /vpnguard lookup command.
        $this->sender = $sender;
        $this->ip = $ipAddress;
        $this->userAgent = $plugin->getUserAgent();
        $this->cfg = $plugin->cfg;
        $this->cfgCommands = $plugin->cfgCommands;
        $this->countries = $plugin->countries;
        $this->cache = serialize($plugin->cache);
    }

    public function onRun() {
        if(!empty($this->cfg["api-key"])) {
            $api = "http://tools.xioax.com/networking/v2/json/" . $this->ip . "/" . $this->cfg["api-key"];
        } else {
            $api = "http://tools.xioax.com/networking/v2/json/" . $this->ip;
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

        $player = $server->getPlayer($this->sender);
        $countries = (array)$this->countries;

        $remaining_requests = null;

        if((!empty($obj)) && ($obj['status'] === "success")) {
          $org = $obj["org"];
          $package = $obj["package"];

          if($this->cache instanceof SimpleCache) {
              if(!$this->cache->is_cached($this->ip)) {
                  if ($package === "Free") {
                    $remaining_requests = $obj["remaining_requests"];
                  }
                  $this->cache->set_cache($this->ip, $this->data);
              }
          } else {
            if ($package === "Free") {
              $remaining_requests = $obj["remaining_requests"];
            }
          }

          if($package === "Professional") {
            $countryName = $obj['country']['name'];
            $countryCode = $obj['country']['code'];
            $subdivisionName = $obj['subdivision']['name'];
            $locationLat = $obj['location']['lat'];
            $locationLong = $obj['location']['long'];
            $postal = $obj['postal'];
            $city = $obj['city'];

            $listed = implode(", ", preg_split("/[\s]+/", $countryName . " "
            . $subdivisionName . " " . $city . " " . $postal));

            if($this->mode == 1) {
              if(in_array($countryCode, $countries)) {
                if(strtolower($this->cfg["country_mode"]) === "blacklist") {
                  if( ($player instanceof Player)) {

                    $player->close("",$this->cfg["country_ban-message"]);

                    if($this->cfg["logging"]) {
                        $server->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                            " has been disconnected for connecting from " . $countryName . " which is on the blacklist!");
                    }
                    return;
                  }
                }
              } else {
                if(strtolower($this->cfg["country_mode"]) === "whitelist") {
                  if( ($player instanceof Player)) {

                    $player->close("",$this->cfg["country_ban-message"]);

                    if($this->cfg["logging"]) {
                        $server->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                            " has been disconnected for connecting from " . $countryName . " which isn't on the whitelist!");
                    }
                    return;
                  }
                }
              }
            }
          }
        }


        if($this->mode == 1) {
          if(empty($obj)) {
            $server->getLogger()->critical("API Server Seems to be Down");
            if (!$this->cfg["bypass-check"]) {
                $player->close("",$this->cfg["bypass-message"]);
            }
          } else {
            if ($obj['status'] === "success") {
              if ($obj['host-ip']) {
                if(($player instanceof Player)) {
                    foreach ($this->cfgCommands as $command) {
                        $command = str_replace("%p", $player->getName(), $command);
                        $server->dispatchCommand(new ConsoleCommandSender(), $command);
                    }

                    if($this->cfg["logging"]) {
                      $output_details = $org . " ";
                      if($package === "Professional") {
                        $output_details .= $listed;
                      } elseif($remaining_requests != null) {
                        $output_details .= "/ Remaining API Requests: " . $remaining_requests ;
                      }
                        $server->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                            " connected with a anonymizer");
                            if(!empty(preg_replace('/\s+/', '', $output_details))) {
                              $server->getLogger()->info(TextFormat::DARK_RED . $player->getName() . TextFormat::WHITE .
                                  " Details -> " . $output_details);
                            }
                    }
                }
              } else {
                if(($player instanceof Player)) {
                    if($this->cfg["logging"]) {
                        $server->getLogger()->info(TEXTFormat::GREEN . $player->getName() . TextFormat::WHITE .
                            " has passed VPNGuard checks.");
                    }
                }
              }
            } else {
              if(($player instanceof Player)) {
                  $server->getLogger()->warning(TextFormat::WHITE . "API Server Returned Error Message: " .
                      TextFormat::RED . $obj['msg'] . TextFormat::WHITE . " when " . TextFormat::GOLD . $player->getName() .
                      TextFormat::WHITE . " connected");

                  if (((strpos($obj['msg'], "Invalid API Key")) || (strpos($obj['msg'], "Payment Overdue"))) === false) {
                      $server->getLogger()->critical("Shutting down server to prevent blacklisting on API Database");
                      $server->shutdown();
                      return;
                  } else {
                      if (!$this->cfg["bypass-check"]) {
                          $player->close("",$this->cfg["bypass-message"]);
                      }
                  }
              }
            }
          }
        } elseif ($this->mode == 2) {
          if(empty($obj)) {
            $player->sendMessage("API Server Seems to be Down, try again later.");
          } else {
            if ($obj['status'] === "success") {
              if ($obj['host-ip']) {
                $player->sendMessage(TextFormat::GREEN . $this->ip . " belongs to a hosting organization");
              } else {
                $player->sendMessage(TextFormat::RED . $this->ip . " does not seem to belong to a hosting organization.");
                $player->sendMessage("If you believe this is an error please report it to the API provider to have it fixed.");
              }
              if($package === "Free") {
                if($remaining_requests != null) {
                  $player->sendMessage("Remaining Requests on your Package: " . $remaining_requests);
                }
                $player->sendMessage("API Package: " . TextFormat::GRAY . strtoupper($package));
              } else {
                $player->sendMessage("API Package: " . TextFormat::GREEN . strtoupper($package));
              }
              if(!empty($org)) {
                $player->sendMessage("Organization " . $org);
              }

              if($package === "Professional") {
                if(!empty($countryName)) {
                  $player->sendMessage("Country: " . $countryName);
                }
                if(!empty($subdivisionName)) {
                  $player->sendMessage("State: " . $subdivisionName);
                }
                if(!empty($city)) {
                  $player->sendMessage("City: " . $city);
                }
                if(!empty($postal)) {
                  $player->sendMessage("Postal: " . $postal);
                }
                if((!empty($locationLat)) && (!empty($locationLong))) {
                  $player->sendMessage("Lat: " . $locationLat . ", Long:" . $locationLong);
                }
              }
            } else {
              $player->sendMessage("API Server Returned Error Message: " . $obj["msg"]);
            }
          }
        }
    }
}
