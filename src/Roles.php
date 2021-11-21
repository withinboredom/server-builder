<?php

namespace ServerBuilder;

class Roles
{
	/**
	 * @var \ServerBuilder\Role[]
	 */
	public array $roles;
	
	public function __construct(Role ...$roles)
	{
		$this->roles = $roles;
	}
	
	public function appendRole(Role $role): void
	{
		$this->roles[] = $role;
	}
	
	public function appendRoles(Role ...$roles): void
	{
		$this->roles = array_merge($this->roles, $roles);
	}
	
	public function prependRole(Role $role): void
	{
		$this->roles = array_merge([$role], $this->roles);
	}
}
