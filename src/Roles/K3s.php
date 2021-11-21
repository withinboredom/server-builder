<?php

namespace ServerBuilder\Roles;

use ServerBuilder\ServerDescription;

class K3s extends BasicServer
{
	public function __construct(ServerDescription $serverDescription, string $clusterSecret)
	{
		parent::__construct($serverDescription);
		$this->roles->appendRoles(
			new FirewallRule(6443, 'allow'),
			new FirewallRule(51820, 'allow', kind: 'udp'),
			new FileSync(__DIR__.'/../../registries.yaml', '/etc/rancher/k3s/registries.yaml'),
			new K3sServer($clusterSecret),
		);
	}
}
