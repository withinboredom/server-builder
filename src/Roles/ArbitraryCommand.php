<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class ArbitraryCommand extends Role
{
	public function __construct(private string $command)
	{
	}
	
	public function apply(): void
	{
		$this->exec($this->command);
	}
	
	public function getName(): string
	{
		return "ArbitraryCommand({$this->command})";
	}
}
