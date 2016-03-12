# VPNGuard
PocketMine-MP Plugin: VPNGuard will prevent players from joining your server behind any type of anonymizer.

This plugin is a port of the Bukkit Version: https://www.spigotmc.org/resources/vpnguard.6246/

### Commands
| Command   | Description |
| :-------- | :---------- |
|  vpnguard  | **The main command for VPNGuard.** |
|  vpnguard clearcache | **Deletes all locally saved cache files** |
|  vpnguard clearip {ip}  | **Deletes the locally saved cache file for the specified IP address** |
|  vpnguard lookup {ip}  | **Allows you to search any IP address in-game and check whether or not it belongs to a hosting organization.** |
|  vpnguard ban {ip/subnet block} | **Allows you to ban an IP address subnet** |
|  vpnguard unban {ip/subnet block} | **Allows you to unban an IP address subnet** |
|  vpnguard about  | **Information about the plugin** |

### Permissions
| Node  | Default | Description |
| :-------- | :---------- | :---------- |
| vpnguard.command.vpnguard | true | Allows you to use the **/vpnguard** command |
| vpnguard.command.clearcache | op | Allows you to use the **/vpnguard clearcache** command |
| vpnguard.command.clearip | op | Allows you to use the **/vpnguard clearip** command |
| vpnguard.command.lookup | op | Allows you to use the **/vpnguard lookup** command |
| vpnguard.command.ban | op | Allows you to use the **/vpnguard ban** command |
| vpnguard.command.unban | op | Allows you to use the **/vpnguard unban** command |
| vpnguard.command.about | true | Allows you to use the **/vpnguard about** command |


