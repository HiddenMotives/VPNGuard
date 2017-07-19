<?php
namespace basprohop\libraries;

use basprohop\VPNGuard;
use pocketmine\command\CommandSender;
use pocketmine\Player;
use pocketmine\utils\TextFormat;

class CommandFunctions {
    private $plugin;

    public function __construct(VPNGuard $plugin){
        $this->plugin = $plugin;
    }

    /**
     * Function that is executed when /vpnguard about is invoked.
     * Displays information regarding the plugin and shows commands available to the user.
     * @param CommandSender $sender - Command Sender
     */
    public function cmdEmpty(CommandSender $sender) {
        $sender->sendMessage($this->plugin->msg("VPNGuard Command List"));
        if($sender->hasPermission("vpnguard.command.vpnguard")) {
            if($this->plugin->cache instanceof SimpleCache) {
              if($sender->hasPermission("vpnguard.command.clearcache")) {
                  $sender->sendMessage($this->plugin->msg("/vpnguard clearcache"));
              }
              if($sender->hasPermission("vpnguard.command.clearip")) {
                  $sender->sendMessage($this->plugin->msg("/vpnguard clearip <ipv4 address>"));
              }
            }
            if($sender->hasPermission("vpnguard.command.lookup")) {
                $sender->sendMessage($this->plugin->msg("/vpnguard lookup <ipv4 address>"));
            }
            if($sender->hasPermission("vpnguard.command.subnet")) {
                $sender->sendMessage($this->plugin->msg("/vpnguard subnet <ban/unban> <ipv4 address/subnet>"));
            }
            if($sender->hasPermission("vpnguard.command.country")) {
                $sender->sendMessage($this->plugin->msg("/vpnguard country <add/remove> <country code>"));
            }
            if($sender->hasPermission("vpnguard.command.about")) {
                $sender->sendMessage($this->plugin->msg("/vpnguard about"));
            }
        } else {
            $sender->sendMessage($this->plugin->msg("You have no permissions to run any commands."));
        }
    }

    /**
     * Function that is executed when /vpnguard clearcache is invoked
     * Deletes all the Cached Files.
     * @param CommandSender $sender - Command Sender
     */
    public function cmdClearCache(CommandSender $sender) {
        if($this->plugin->cache instanceof SimpleCache) {
          $this->plugin->cache->remove_all_cache();
          $sender->sendMessage($this->plugin->msg("All Tasks Completed!"));
        } else {
          $sender->sendMessage($this->plugin->msg("API Caching is Disabled."));
        }
    }

    /**
     * Function that is executed when /vpnguard clearip <ip> is invoked
     * Deletes the specified IP address cache file.
     * @param CommandSender $sender - Command Sender
     * @param $ip - IP address whose cached file will be deleted
     */
    public function cmdClearIP(CommandSender $sender, $ip) {
        if($this->plugin->cache instanceof SimpleCache) {
          if (!filter_var($ip, FILTER_VALIDATE_IP) === false) {
              if($this->plugin->cache->is_cached($ip)) {
                  if($this->plugin->cache->remove_cache($ip)) {
                      $sender->sendMessage($this->plugin->msg("Successfully deleted the cached API file."));
                  } else {
                      $sender->sendMessage($this->plugin->msg("Unable to delete the cached file. Sorry!"));
                  }
              } else {
                  $sender->sendMessage($this->plugin->msg("No cache file found matching the ipv4 address: " . $ip));
              }
          } else {
              $sender->sendMessage($this->plugin->msg($ip . " is not a valid IP address."));
          }
        } else {
          $sender->sendMessage($this->plugin->msg("API Caching is Disabled."));
        }
    }

    /**
     * Function that is executed when /vpnguard lookup <ip> is invoked
     * Looks up information regarding the specified IP address and displays it to user.
     * @param CommandSender $sender - Player that sent the command.
     * @param $check - IP address that will be checked or player name that will be checked.
     */
    public function cmdLookup(CommandSender $sender, $check) {
      if($sender instanceof Player) {
        if (!filter_var($check, FILTER_VALIDATE_IP) === false) {
          $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new Async(2, $this->plugin, $sender->getName(), $check));
        } else {
          $player = $this->plugin->getServer()->getPlayer($check);
          if(($player !== null && $player->isOnline())) {
            $this->plugin->getServer()->getScheduler()->scheduleAsyncTask(new Async(2, $this->plugin, $sender->getName(), $player->getAddress()));
          } else {
            $sender->sendMessage($this->plugin->msg($check . " is not a valid IP address or the Player name you entered is not Online."));
          }
        }
      } else {
         $sender->sendMessage($this->plugin->msg("This command can only be run in-game."));
      }
    }

    /**
     * Function that is executed when /vpnguard about is invoked
     * Displays information about the plugin to the sender.
     * @param CommandSender $sender - Command Sender
     */
    public function cmdAbout(CommandSender $sender) {
        $sender->sendMessage($this->plugin->msg("VPNGuard v" . $this->plugin->getDescription()->getVersion()));
        if(empty($this->plugin->cfg["api-key"])) {
            $sender->sendMessage($this->plugin->msg("Using API Key? " . TextFormat::GRAY . "NO"));
        } else {
            $sender->sendMessage($this->plugin->msg("Using API Key? " . TextFormat::GREEN . "YES"));
        }

        if($this->plugin->cache instanceof SimpleCache) {
            $total = $this->totalCached();
            $sender->sendMessage($this->plugin->msg("API Request Caching: " . TextFormat::GREEN . "ENABLED"));
            if($total > 0) {
                $sender->sendMessage($this->plugin->msg("API Requests Cached: " . TextFormat::AQUA . $total));
            }
        } else {
            $sender->sendMessage($this->plugin->msg("API Request Caching: " . TextFormat::GRAY . "DISABLED"));
        }
        $sender->sendMessage($this->plugin->msg("API Homepage: " . TextFormat::AQUA . "http://xioax.com/host-blocker/"));

    }

    /**
    * Function that is executed when /vpnguard subnet is invoked
    * Allows you to ban or unban a subnet
    * @param CommandSender $sender
    * @param $mode - Are we banning or unbanning? Accepts only 'ban' or 'unban'
    * @param $ip - IP in CIDR Format
    */
    public function cmdSubnet(CommandSender $sender, $mode, $ip) {
      $mode = strtolower($mode);
      $split = explode("/", $ip);

      if(($mode === "ban") || ($mode === "unban")) {

        if( (count($split) != 2)) {
            $sender->sendMessage($this->plugin->msg("Enter a valid IP in CIDR format."));
            return;
        }

        if(!is_numeric($split[1])) {
            $sender->sendMessage($this->plugin->msg("Enter a valid IP subnet in CIDR format."));
            return;
        }

        if (!filter_var($split[0], FILTER_VALIDATE_IP) === false) {
            if (($split[1] <= 32) && ($split[1] >= 0)) {

              if($mode === "ban") {
                if(in_array($ip, $this->plugin->subnets)) {
                    $sender->sendMessage($this->plugin->msg($ip . " is already banned."));
                } else {
                    array_push($this->plugin->subnets, $ip);
                    $this->plugin->subnet_list->set("subnets", $this->plugin->subnets);
                    $this->plugin->subnet_list->save();
                    $sender->sendMessage($this->plugin->msg($ip . " has been banned."));
                }
              } elseif($mode === "unban") {
                if(in_array($ip, $this->plugin->subnets)) {
                    $key = array_search($ip,$this->plugin->subnets);
                    if($key!==false){
                        $ipRemove = $ip;
                        unset($ipRemove,$this->plugin->subnets[$key]);
                        $this->plugin->subnet_list->set("subnets", $this->plugin->subnets);
                        $this->plugin->subnet_list->save();
                        $sender->sendMessage($this->plugin->msg($ip . " has been unbanned."));
                    } else {
                        $sender->sendMessage($this->plugin->msg("Unable to unban " . $ip));
                    }
                } else {
                    $sender->sendMessage($this->plugin->msg($ip . " is not banned."));
                }
              }
            } else {
              $sender->sendMessage($this->plugin->msg($split[1] . " is not a valid IP subnet, it must be in CIDR format."));
            }
        } else {
          $sender->sendMessage($this->plugin->msg($split[0] . " is not a valid IP address."));
        }
      } else {
        $sender->sendMessage($this->plugin->msg("Usage: /vpnguard subnet <ban/unban> <ipv4 address/subnet>"));
      }
    }

    /**
    * Function that is executed when /vpnguard country is invoked
    * Allows you to add or remove a country code from your blacklist/whitelist
    * @param CommandSender $sender
    * @param $mode - Are we adding or removing? Accepts only 'add' or 'remove'
    * @param $countryCode - 2 Digit character string in ISO Country Code Format
    */
    public function cmdCountry(CommandSender $sender, $mode, $countryCode) {
      $mode = strtolower($mode);
      $countryCode = strtoupper($countryCode);
      $codesList = $this->getCountryCodeList();

      if(array_key_exists($countryCode,$codesList)) {
        if($mode === "add") {
          if(in_array($countryCode, $this->plugin->countries)) {
              $sender->sendMessage($this->plugin->msg($codesList[$countryCode] . " is already in your " .strtolower($this->plugin->cfg["country_mode"])));
          } else {
              array_push($this->plugin->countries, $countryCode);
              $this->plugin->country_list->set("country_codes", $this->plugin->countries);
              $this->plugin->country_list->save();
              $sender->sendMessage($this->plugin->msg($codesList[$countryCode] . " has been added to your " . strtolower($this->plugin->cfg["country_mode"])));
          }
        } elseif ($mode === "remove") {
          if(in_array($countryCode, $this->plugin->countries)) {
              $key = array_search($countryCode,$this->plugin->countries);
              if($key!==false){
                  $countryCodeRemove = $countryCode;
                  unset($countryCodeRemove,$this->plugin->countries[$key]);
                  $this->plugin->country_list->set("country_codes", $this->plugin->countries);
                  $this->plugin->country_list->save();
                  $sender->sendMessage($this->plugin->msg($codesList[$countryCode] . " has been removed from your " . strtolower($this->plugin->cfg["country_mode"])));
              } else {
                  $sender->sendMessage($this->plugin->msg("Unable to remove " . $codesList[$countryCode] . " from your " . strtolower($this->plugin->cfg["country_mode"])));
              }
          } else {
              $sender->sendMessage($this->plugin->msg($codesList[$countryCode] . " is not on your " . strtolower($this->plugin->cfg["country_mode"])));
          }
        } else {
          $sender->sendMessage($this->plugin->msg("Usage: /vpnguard country <add/remove> <country code>"));
        }
      } else {
          $sender->sendMessage($this->plugin->msg($countryCode . " is not a valid 2-digit ISO Country Code."));
      }
    }


    /**
     * Function that returns the number of cached files currently stored.
     * @return int - Number of Cached Files
     */
    private function totalCached() {
        $i = 0;
        foreach(glob($this->plugin->cache->cache_path . '*') as $file){
            if(is_file($file)) {
                $i++;
            }
        }
        return $i;
    }

    /**
    * @return array
    */
    private function getCountryCodeList() {
        return array(
          'AF' => 'Afghanistan','AX' => 'Aland Islands','AL' => 'Albania',
          'DZ' => 'Algeria','AS' => 'American Samoa','AD' => 'Andorra',
          'AO' => 'Angola','AI' => 'Anguilla','AQ' => 'Antarctica',
          'AG' => 'Antigua and Barbuda','AR' => 'Argentina','AM' => 'Armenia',
          'AW' => 'Aruba','AU' => 'Australia','AT' => 'Austria',
          'AZ' => 'Azerbaijan', 'BS' => 'Bahamas','BH' => 'Bahrain',
          'BD' => 'Bangladesh','BB' => 'Barbados', 'BY' => 'Belarus',
          'BE' => 'Belgium','BZ' => 'Belize','BJ' => 'Benin', 'BM' => 'Bermuda',
          'BT' => 'Bhutan','BO' => 'Bolivia',
          'BQ' => 'Bonaire, Saint Eustatius and Saba',
          'BA' => 'Bosnia and Herzegovina', 'BW' => 'Botswana',
          'BV' => 'Bouvet Island', 'BR' => 'Brazil',
          'IO' => 'British Indian Ocean Territory',
          'VG' => 'British Virgin Islands','BN' => 'Brunei','BG' => 'Bulgaria',
          'BF' => 'Burkina Faso','BI' => 'Burundi','KH' => 'Cambodia',
          'CM' => 'Cameroon', 'CA' => 'Canada','CV' => 'Cape Verde',
          'KY' => 'Cayman Islands', 'CF' => 'Central African Republic',
          'TD' => 'Chad','CL' => 'Chile','CN' => 'China',
          'CX' => 'Christmas Island', 'CC' => 'Cocos Islands', 'CO' => 'Colombia',
          'KM' => 'Comoros', 'CK' => 'Cook Islands', 'CR' => 'Costa Rica',
          'HR' => 'Croatia', 'CU' => 'Cuba', 'CW' => 'Curacao', 'CY' => 'Cyprus',
          'CZ' => 'Czech Republic', 'CD' => 'Democratic Republic of the Congo',
          'DK' => 'Denmark', 'DJ' => 'Djibouti', 'DM' => 'Dominica',
          'DO' => 'Dominican Republic', 'TL' => 'East Timor', 'EC' => 'Ecuador',
          'EG' => 'Egypt', 'SV' => 'El Salvador', 'GQ' => 'Equatorial Guinea',
          'ER' => 'Eritrea', 'EE' => 'Estonia', 'ET' => 'Ethiopia',
          'FK' => 'Falkland Islands', 'FO' => 'Faroe Islands', 'FJ' => 'Fiji',
          'FI' => 'Finland', 'FR' => 'France', 'GF' => 'French Guiana',
          'PF' => 'French Polynesia', 'TF' => 'French Southern Territories',
          'GA' => 'Gabon', 'GM' => 'Gambia', 'GE' => 'Georgia', 'DE' => 'Germany',
          'GH' => 'Ghana', 'GI' => 'Gibraltar', 'GR' => 'Greece', 'GL' => 'Greenland',
          'GD' => 'Grenada', 'GP' => 'Guadeloupe', 'GU' => 'Guam', 'GT' => 'Guatemala',
          'GG' => 'Guernsey', 'GN' => 'Guinea', 'GW' => 'Guinea-Bissau', 'GY' => 'Guyana',
          'HT' => 'Haiti', 'HM' => 'Heard Island and McDonald Islands', 'HN' => 'Honduras',
          'HK' => 'Hong Kong', 'HU' => 'Hungary', 'IS' => 'Iceland', 'IN' => 'India',
          'ID' => 'Indonesia', 'IR' => 'Iran', 'IQ' => 'Iraq', 'IE' => 'Ireland',
          'IM' => 'Isle of Man', 'IL' => 'Israel', 'IT' => 'Italy',
          'CI' => 'Ivory Coast', 'JM' => 'Jamaica', 'JP' => 'Japan', 'JE' => 'Jersey',
          'JO' => 'Jordan', 'KZ' => 'Kazakhstan', 'KE' => 'Kenya', 'KI' => 'Kiribati',
          'XK' => 'Kosovo', 'KW' => 'Kuwait', 'KG' => 'Kyrgyzstan', 'LA' => 'Laos',
          'LV' => 'Latvia', 'LB' => 'Lebanon', 'LS' => 'Lesotho', 'LR' => 'Liberia',
          'LY' => 'Libya', 'LI' => 'Liechtenstein', 'LT' => 'Lithuania', 'LU' => 'Luxembourg',
          'MO' => 'Macao', 'MK' => 'Macedonia', 'MG' => 'Madagascar', 'MW' => 'Malawi',
          'MY' => 'Malaysia', 'MV' => 'Maldives', 'ML' => 'Mali', 'MT' => 'Malta',
          'MH' => 'Marshall Islands', 'MQ' => 'Martinique', 'MR' => 'Mauritania',
          'MU' => 'Mauritius', 'YT' => 'Mayotte', 'MX' => 'Mexico', 'FM' => 'Micronesia',
          'MD' => 'Moldova', 'MC' => 'Monaco', 'MN' => 'Mongolia', 'ME' => 'Montenegro',
          'MS' => 'Montserrat', 'MA' => 'Morocco', 'MZ' => 'Mozambique', 'MM' => 'Myanmar',
          'NA' => 'Namibia', 'NR' => 'Nauru', 'NP' => 'Nepal', 'NL' => 'Netherlands',
          'NC' => 'New Caledonia', 'NZ' => 'New Zealand', 'NI' => 'Nicaragua',
          'NE' => 'Niger', 'NG' => 'Nigeria', 'NU' => 'Niue', 'NF' => 'Norfolk Island',
          'KP' => 'North Korea', 'MP' => 'Northern Mariana Islands', 'NO' => 'Norway',
          'OM' => 'Oman', 'PK' => 'Pakistan', 'PW' => 'Palau', 'PS' => 'Palestinian Territory',
          'PA' => 'Panama', 'PG' => 'Papua New Guinea', 'PY' => 'Paraguay', 'PE' => 'Peru',
          'PH' => 'Philippines', 'PN' => 'Pitcairn', 'PL' => 'Poland', 'PT' => 'Portugal',
          'PR' => 'Puerto Rico', 'QA' => 'Qatar', 'CG' => 'Republic of the Congo',
          'RE' => 'Reunion', 'RO' => 'Romania', 'RU' => 'Russia', 'RW' => 'Rwanda',
          'BL' => 'Saint Barthelemy', 'SH' => 'Saint Helena', 'KN' => 'Saint Kitts and Nevis',
          'LC' => 'Saint Lucia', 'MF' => 'Saint Martin', 'PM' => 'Saint Pierre and Miquelon',
          'VC' => 'Saint Vincent and the Grenadines', 'WS' => 'Samoa', 'SM' => 'San Marino',
          'ST' => 'Sao Tome and Principe', 'SA' => 'Saudi Arabia', 'SN' => 'Senegal',
          'RS' => 'Serbia', 'SC' => 'Seychelles', 'SL' => 'Sierra Leone', 'SG' => 'Singapore',
          'SX' => 'Sint Maarten', 'SK' => 'Slovakia','SI' => 'Slovenia','SB' => 'Solomon Islands',
          'SO' => 'Somalia','ZA' => 'South Africa','GS' => 'South Georgia and the South Sandwich Islands',
          'KR' => 'South Korea','SS' => 'South Sudan','ES' => 'Spain','LK' => 'Sri Lanka','SD' => 'Sudan',
          'SR' => 'Suriname','SJ' => 'Svalbard and Jan Mayen','SZ' => 'Swaziland','SE' => 'Sweden',
          'CH' => 'Switzerland','SY' => 'Syria','TW' => 'Taiwan','TJ' => 'Tajikistan','TZ' => 'Tanzania',
          'TH' => 'Thailand','TG' => 'Togo','TK' => 'Tokelau','TO' => 'Tonga','TT' => 'Trinidad and Tobago',
          'TN' => 'Tunisia','TR' => 'Turkey','TM' => 'Turkmenistan','TC' => 'Turks and Caicos Islands',
          'TV' => 'Tuvalu','VI' => 'U.S. Virgin Islands','UG' => 'Uganda','UA' => 'Ukraine',
          'AE' => 'United Arab Emirates','GB' => 'United Kingdom','US' => 'United States',
          'UM' => 'United States Minor Outlying Islands','UY' => 'Uruguay','UZ' => 'Uzbekistan',
          'VU' => 'Vanuatu','VA' => 'Vatican','VE' => 'Venezuela','VN' => 'Vietnam',
          'WF' => 'Wallis and Futuna','EH' => 'Western Sahara','YE' => 'Yemen','ZM' => 'Zambia','ZW' => 'Zimbabwe',
      );
    }

}
