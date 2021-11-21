<?php

namespace ServerBuilder\Roles\Network;

class Address extends NetPlan
{
	public function __construct(protected string $address)
	{
		parent::__construct();
	}
	
	public function render(): array
	{
		return [$this->address];
	}
	
	public function isIp4(): bool
	{
		return str_contains($this->getIp(), '.');
	}
	
	public function getIp(): string
	{
		return explode('/', $this->address)[0];
	}
	
	public function getCidr(): int
	{
		return (int) explode('/', $this->address)[1];
	}
}
