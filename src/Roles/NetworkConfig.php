<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class NetworkConfig extends Role
{
	public function __construct()
	{
	}
	
	public function apply(): void
	{
		$path = '/etc/netplan/01-netcfg.yaml';
		
		$yaml = $this->createYaml($this->serverDescription->netPlan->render());
		$existingConfig = $this->file_get_contents($path);
		$newConfig = file_get_contents($yaml);
		if ($newConfig !== $existingConfig) {
			self::warn('overwriting existing netplan!');
			try {
				$this->exec("cp $yaml $path");
				$this->exec('/lib/netplan/generate');
				$this->exec('systemctl restart systemd-networkd');
			} catch (\LogicException) {
				self::warn('Detected error applying netplan, attempting to rollback!');
				$this->file_put_contents($path, $existingConfig);
			}
		}
	}
	
	public function getName(): string
	{
		return 'NetworkConfig('.print_r($this->serverDescription->netPlan, true).')';
	}
}
