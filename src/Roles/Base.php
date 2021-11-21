<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class Base extends Role
{
	private const PACKAGES = [
		'apparmor',
		'build-essential',
		'open-iscsi',
		'cifs-utils',
		'ufw',
		'wireguard',
	];
	
	public function apply(): void
	{
		$this->exec('apt update');
		$this->exec('apt upgrade -y');
		$this->exec('apt install -y '.implode(' ', self::PACKAGES));
	}
	
	public function getName(): string
	{
		return 'Base()';
	}
}
