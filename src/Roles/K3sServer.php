<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class K3sServer extends Role
{
	public function __construct(protected string $clusterSecret)
	{
	}
	
	public function apply(): void
	{
		try {
			$this->exec(
				<<<CMD
curl -sfL https://get.k3s.io | \
K3S_CLUSTER_SECRET=\"{$this->clusterSecret}\" sh -s - server \
--no-deploy traefik \
--no-deploy local-storage \
--node-label topology.kubernetes.io/region={$this->serverDescription->region} \
--node-label topology.kubernetes.io/zone={$this->serverDescription->zone}
CMD
			);
		} catch (\LogicException) {
			$this->exec('journalctl -xe');
			exit(1);
		}
	}
	
	public function getName(): string
	{
		return "K3sServer(REDACTED)";
	}
}
