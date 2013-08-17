#!/bin/bash

# This software is distributed under the terms of the GPLv2, see LICENSE file or 
# visit https://www.gnu.org/licenses/gpl-2.0.html

this_script="$(readlink -f "$0")"
this_dir="${this_script%/${0##*/}}"


required_packages="vim git ssh sudo coreutils libssl-dev openssl build-essential uthash-dev libjansson-dev autoconf pkg-config libtool libcurl4-openssl-dev libncurses5-dev nginx php5-cli php5-fpm tor"

# prefer bfgminer from Luke Jr. to cgminer, better optimized for BFL devices, 
# checkout code directly from github at url below
bfgminer_url="git://github.com/luke-jr/bfgminer.git"
bfgminer_tag="bfgminer-3.1.4"
bfgminer_build_dir="/tmp/bfgminer-build"

#regenerate ssh keys
rm /etc/ssh/ssh_host_* && dpkg-reconfigure openssh-server

#boilerplate debian system upgrade
export DEBIAN_FRONTEND="noninteractive"
sed -i '/cdrom:/d' /etc/apt/sources.list
apt-get update -y
apt-get dist-upgrade -y
apt-get autoremove

#install required packages
apt-get install -y aptitude
for p in $required_packages ; do
	echo "installing $p"
	aptitude install -y "$p"
done


#set hostname to PIckaxe
echo "PIckaxe" > /etc/hostname
echo "PIckaxe" > /proc/sys/kernel/hostname   2>/dev/null


#install bfgminer to /usr/local/bin/bfgminer
mkdir -p  "$bfgminer_build_dir"
rm    -rf "$bfgminer_build_dir"
git clone "$bfgminer_url" "$bfgminer_build_dir"
cd "$bfgminer_build_dir"
git checkout "$bfgminer_tag"
sh autogen.sh
./configure --disable-opencl
make
make install
cp /usr/local/lib/libblkmak*so* /usr/lib
cd /tmp
rm -rf "$bfgminer_build_dir"

# bfgminer config
# set icarus options for block eruptors so no further config is necessary
# also, set a fairly long queue (12), so we can handle larger numbers of devices
cat << 'EOF' >/usr/local/share/bfgminer/bfgminer.conf
{
	"pools": [
		{
			"url"  : "",
			"user" : "",
			"pass" : ""
		},
		{
			"url"  : "",
			"user" : "",
			"pass" : ""
		}

	],

	"api-listen" : true,
	"api-port" : "4028",
	"log" : "5",
	"no-pool-disable" : true,
	"queue" : "12",
	"scan-time" : "60",
	"worktime" : true,
	"shares" : "0",
	"expiry" : "120",
	"failover-only" : true,
	"kernel-path" : "/usr/local/bin",
	"api-allow" : "0/0",
	"icarus-options" : "115200:1:1",
	"icarus-timing" : "3.0=100",
	"donation"      : "0.00"
}
EOF
chown www-data:www-data /usr/local/share/bfgminer/bfgminer.conf



#bfgminer init
cat << 'EOF' >/etc/init.d/bfgminer
#!/bin/bash

### BEGIN INIT INFO
# Provides: bfgminer
# Required-Start: $local_fs $remote_fs $network $named $time
# Required-Stop: $local_fs $remote_fs $network $named $time
# Should-Start: $syslog
# Default-Start: 2 3 4 5
# Default-Stop: 0 1 6
# Short-Description: bfgminer
# Description: bfgminer
### END INIT INFO

NAME="bfgminer"
BIN="/usr/local/bin/$NAME"
CONFIG="/usr/local/share/bfgminer/$NAME.conf"
DEVICE_LIST_FILE="/usr/local/share/bfgminer/BFG_DEVICES"

do_start()
{
	if [ ! -e "$CONFIG" ] ; then
		echo "ERROR: config file '$CONFIG' does not exist"
		exit 1;
	fi
	
	start-stop-daemon --status --exec "$BIN" 
	if [ "$?" = "0" ] ; then
		echo "ERROR: $NAME is already running"
		exit 1
	fi

	"$BIN" -S all -d?  2>/dev/null | sed 's/^.*:[0-9][0-9]\] *//g' | grep "^[0-9]" | sed 's/^[0-9]*\. //g' |  sed 's/[a-z] .driver/ (driver/g' | uniq > "$DEVICE_LIST_FILE"
	( start-stop-daemon --start --quiet  --exec "$BIN" --  -S all --config "$CONFIG" --real-quiet >/dev/null 2>/dev/null & )

        sleep 3


	start-stop-daemon --status --exec "$BIN" 
	if [ "$?" = "0" ] ; then
		echo "$NAME started successfully"
		echo ""
	else
		echo "$NAME failed to start"
		exit 1
	fi

}
do_stop()
{
	start-stop-daemon --status --exec "$BIN"
        if [ "$?" = "0" ] ; then
		start-stop-daemon --stop --quiet --oknodo --exec "$BIN"
		sleep 5
	fi
}


case "$1" in
  start)
	do_start
	;;
  stop)
	do_stop
	;;
  restart)
	do_stop
	do_start
	;;
  *)
	echo "Usage: $0 {start|stop|restart}"
	exit 1
	;;
esac

exit 0



EOF
chmod 755 /etc/init.d/bfgminer
update-rc.d bfgminer defaults
update-rc.d bfgminer enable

#detect devices  if any and start bfgminer. Will stop if no devices present
/etc/init.d/bfgminer start >/dev/null 2>&1

#setup nginx
cat << 'EOF' >/etc/nginx/sites-available/default
server {
	listen   80; ## listen for ipv4; this line is default and implied

	root /var/www;
	index index.php index.html index.htm;

	server_name localhost pickaxe ;

	try_files $uri $uri/ =404;


	location ~ \.php$ {
		fastcgi_split_path_info ^(.+\.php)(/.+)$;
		fastcgi_pass unix:/var/run/php5-fpm.sock;
		fastcgi_index index.php;
		include fastcgi_params;
	}
}
EOF

mkdir -p /var/www
cp -r "$this_dir/pickaxe_webif/"* /var/www


/etc/init.d/php5-fpm restart
/etc/init.d/nginx restart




# allow www-data unlimited sudo without password
echo '%www-data ALL=(ALL:ALL) NOPASSWD: NOPASSWD: ALL' >>/etc/sudoers


# tor, disable by default
# tor iptables rules by Eric Bishop, code at https://github.com/ericpaulbishop/iptables_torify
cd /tmp
git clone git://github.com/ericpaulbishop/iptables_torify.git
cd iptables_torify
./debian_install.sh
/etc/init.d/torify stop
/etc/init.d/tor stop
/usr/sbin/update-rc.d tor  disable
/usr/sbin/update-rc.d torify disable 

# by default display settings without login
touch "/etc/pickaxe_show_nl_status"
chmod 644 "/etc/pickaxe_show_nl_status"

