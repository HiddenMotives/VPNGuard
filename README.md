# VPNGuard
PocketMine-MP Plugin: VPNGuard will prevent players from joining your server behind any type of anonymizer.

VPNGuard will prevent players from joining your server behind any type of anonymizer (whether it be a VPN or a Proxy). This will effectively help reduce spammers/bots and Miscellaneous individuals from joining your server by kicking them if they have an IP address which belongs to a hosting organization.

VPNGuard uses a privately managed blocking list which is updated almost daily to Combat new threats
Easy installation requires almost no configuration, simply download and install.


Updated for: **3.0.0 +**

Latest Plugin Release: **VPNGuard v1.0.7**

### Commands
| Command   | Description |
| :-------- | :---------- |
|  vpnguard  | **The main command for VPNGuard.** |
|  vpnguard clearcache | **Deletes all locally saved cache files** |
|  vpnguard clearip {ip}  | **Deletes the locally saved cache file for the specified IP address** |
|  vpnguard lookup {ip/player}  | **Allows you to search any IP address or online player in-game and view IP details** |
|  vpnguard subnet {ban/unban} {ip/subnet block} | **Allows you to ban or unban a IP address subnet** |
|  vpnguard country {add/remove} {cc} | **Allows you to add or remove a country from your blacklist or whitelist** |
|  vpnguard about  | **Information about the plugin** |

### Permissions
| Node  | Default | Description |
| :-------- | :---------- | :---------- |
| vpnguard.command.vpnguard | true | Allows you to use the **/vpnguard** command |
| vpnguard.command.clearcache | op | Allows you to use the **/vpnguard clearcache** command |
| vpnguard.command.clearip | op | Allows you to use the **/vpnguard clearip** command |
| vpnguard.command.lookup | op | Allows you to use the **/vpnguard lookup** command |
| vpnguard.command.subnet | op | Allows you to use the **/vpnguard subnet** command |
| vpnguard.command.country | op | Allows you to use the **/vpnguard country** command |
| vpnguard.command.about | true | Allows you to use the **/vpnguard about** command |

### Frequently Asked Questions
**Q**. The plugin API is outdated when will you make an update?

A. Please update/bump the API version in the plugin.yml, the plugin should still work. If not please open a Github Issue and let me know.

**Q**. How many players can the plugin look information up for?

A. At the time of writing this the backend API provider has a 500 monthly request limit. Which means if you enable api-cache within config.yml you can get 500 monthly unique players and lookup information regarding them with no problem!

**Q**. What happens when the API monthly limit is reached?

A. The API server will no longer provide information regarding additional/new users who attempt to join your server, from there those users would be either allowed to join your server without any sort of checking or they would be kicked, this is based on the value specified in the config.yml under bypass-check.

**Q**. I have a server and more than 500 new users join per month is it possible I can get more monthly requests?

A. Yes you can you would need to purchase an API key, at the time of writing this its only $5/mo

**Q**. I have more than one server can I use the API key on more than one server at a time?

A. Yes you can use a single API key on as many servers as you wish, that you own.

**Q**. Why can't this be completely free for unlimited requests?

A. It costs money to maintain and upkeep such a service. You are not paying for the plugin but are paying for the service to use the API if you choose to do so.

**Q**. Where can I view details/purchase the API that is being used?

A. You can visit the homepage by the API provider located at: http://bit.ly/host-blocker

**Q**. Right after I installed the plugin it says "Monthly Limit Reached" but this is the first time im running it what do I do?

A. You most likely are on shared hosting and chances are someone already has used up the 500 Free Monthly Request Limit for the IP Address. You would need to either test the plugin locally on your computer, on a different server or purchase a API Key.

**Q**. I just installed the plugin I need help configuring command section in the config.

A. Configuring the plugin commands is super simple! Take a look at the config file: https://github.com/HiddenMotives/VPNGuard/blob/master/resources/config.yml it is pretty straight forward, the command section is a list of commands to run when a user joins with a VPN. %p represents the player name connecting and gets replaced automatically.

~~~~
command:
- kick %p You seem to be using a VPN or Proxy
~~~~
The above command to run would kick the player trying to connect with a VPN with the message "You seem to be using a VPN or Proxy". Or you could specify more than one command by adding another line like:
~~~~
command:
- kick %p You seem to be using a VPN or Proxy
- say %p is a naughty crafter.
~~~~
Which would both kick the player trying to connect to the server and broadcast "playerName is a naughty crafter"

**Q**. I found a IP from a VPN organization not blocked what do I do?

A. Contact the API provider

**Q**. My question is not listed here

A. Contact me with your Question!


