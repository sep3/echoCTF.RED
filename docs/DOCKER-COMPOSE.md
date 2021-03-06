# docker-compose instructions
The `docker-compose.yml` builds and starts all the needed applications and targets on a single host running docker.

Keep in mind that this may require a lot of memory to run (our tests are
performed on systems with at least 8GB ram).

The docker containers use the following networks

* `echoctfred_public`: `172.26.0.0/24`
* `echoctfred_private`: `172.24.0.0/24`
* `echoctfred_targets`: `10.0.160.0/24`
* `OpenVPN`: `10.10.0.0/16`

Furthermore the following ports are mapped on the host server and containers

* udp 0.0.0.0:1194 => echoctfred_vpn 172.26.0.1:1194 openvpn
* tcp 0.0.0.0:8082 => echoctfred_backend 172.26.0.2:80
* tcp 0.0.0.0:8080 => echoctfred_frontend 172.26.0.3:80
* tcp 0.0.0.0:3306 => echoctfred_db 172.24.0.253:3306
* tcp 0.0.0.0:11211 => echoctfred_db 172.24.0.253:11211

The following volumes are configured and used

* `echoctfred_data-mysql` For persistent mysql data
* `echoctfred_data-openvpn` For persistent openvpn data
* `echoctfred_data-challenges` under backend & frontend `/var/www/echoCTF.RED/*/web/uploads`
* `./themes/images` under `/var/www/echoCTF.RED/*/web/images` for logos and images

The following diagram illustrates the docker networks and containers that are configured by `docker-compose`.
![echoCTF.RED docker-compose topology](assets/docker-compose-topology.png?)

The easy way to start is to use the official docker images by executing
```sh
docker-compose pull
```

If you'd rather to build your own images make you sure you generate a Github OAuth Token to
be used by the composer utility. This is needed in order to avoid hitting
Github rate limits on their API, which is used by `composer`. More information
about generating a token to use can be found @[Creating a personal access token for the command line](https://help.github.com/en/github/authenticating-to-github/creating-a-personal-access-token-for-the-command-line)

Once you've generated your token you can build the images by executing
```sh
GITHUB_OAUTH_TOKEN=MY_TOKEN_HERE docker-compose build
```

Start the containers
```sh
docker-compose up
```

Configure mail address for player registrations
```sh
docker exec -it echoctfred_vpn ./backend/yii sysconfig/set mail_from dontreply@example.red
```

Create backend and frontend users to test
```sh
docker exec -it echoctfred_vpn ./backend/yii user/create echothrust info@echothrust.com echothrust
docker exec -it echoctfred_vpn ./backend/yii player/register echothrust info@echothrust.com echothrust echothrust offense 1
```

The syntax for the commands can be found at [Console-Commands.md](Console-Commands.md)


Set the IP or FQDN that participants will openvpn
```sh
docker exec -it echoctfred_vpn ./backend/yii sysconfig/set vpngw 172.22.0.4
# or
docker exec -it echoctfred_vpn ./backend/yii sysconfig/set vpngw vpn.example.red
```

Ensure that the docker containers can communicate with the participants. Once the `echoctfred_vpn` host is up run this on the host you run docker-compose at.
```sh
sudo route add -net 10.10.0.0/16 gw 10.0.160.1
```

You can also manipulate a particular container routing table by following the
example below. However keep in mind that this `route` will be deleted when the
container restarts, so the command above `route add -net`, is preferred.
```sh
pid=$(docker inspect -f '{{.State.Pid}}' echoctfred_target1)
sudo mkdir -p /var/run/netns
sudo ln -s /proc/$pid/ns/net /var/run/netns/$pid
sudo ip netns exec $pid ip route del default
sudo ip netns exec $pid ip route add default via 10.0.160.1
```

Make sure you configure the host dockerd daemon to have its API to listen tcp
to the new `private` network. However since the network becomes available only
after dockerd starts you will have to bind to _`0.0.0.0`_ (ie `-H tcp://0.0.0.0:2376`)

More information about enabling docker API https://success.docker.com/article/how-do-i-enable-the-remote-api-for-dockerd

Make sure you restrict connections to this port to `echoctfred_vpn/172.24.0.1` and `echoctfred_backend/172.24.0.2` containers only.

Your `frontend` is accessible at http://localhost:8080/

Login to the backend (http://localhost:8082/) and add a target with the following details

* Name: `echoctfred_target1`
* FQDN: `echoctfred_target1.example.com`
* Status: `online`
* Scheduled at: _empty_
* Difficulty: `0`
* Active: ✓
* Rootable: ✓
* Suggested XP: 0
* Required XP: 0
* Purpose: `this will appear when participants tweet about targets`
* Description: `this will to participants on the frontend`
* IP Address: `10.0.160.2` _Same as `target1` entry from `docker-compose.yml`_
* MAC Address: `02:42:0a:00:a0:02`
* Dns: `8.8.8.8`
* Net: `echoctfred_targets`
* Server: `tcp://172.24.0.254:2376`
* Image: `nginx:latest`
* Parameters (optional): `{"hostConfig":{"Memory":"512"}}`

Once the target is created, click the Spin button on top to start it up. If
everything is correct you should be able to see the container running
```sh
docker inspect echoctfred_target1
```

Keep in mind that you will have to configure firewall rules in order to limit
or restrict who can access the target containers as well as what the target
containers will be allowed to access. Keep in mind that these targets are meant
to be hacked, so take care at limiting their network access with iptables.
