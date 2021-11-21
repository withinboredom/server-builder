<?php

namespace ServerBuilder\Roles;

use JetBrains\PhpStorm\Pure;
use ServerBuilder\Roles;
use ServerBuilder\ServerConfig;
use ServerBuilder\ServerDescription;

class BasicServer extends ServerConfig
{
	#[Pure]
	public function __construct(ServerDescription $serverDescription)
	{
		parent::__construct(
			$serverDescription,
			new Roles(
				new Rescue(),
				new Base(),
				new Reboot(),
				new NetworkConfig(),
				new SysCtl('net.ipv4.ip_forward', 1),
				new SysCtl('net.ipv6.conf.default.forwarding', 1),
				new SysCtl('net.ipv6.conf.all.forwarding', 1),
				new SysCtl('net.core.somaxconn', 32768),
				new SysCtl('net.ipv4.ip_local_port_range', '1024 65000'),
				new StorageBox('u123', 'a-password', '/mnt/registry-data'),
				new FirewallRule(22, 'allow'),
				new FirewallRule(80, 'allow'),
				new FirewallRule(443, 'allow'),
			)
		);
	}
}
