<?php

namespace ServerBuilder\Roles\Network;

class Gateway6 extends NetPlan
{
	public function __construct(protected string $gateway)
	{
		parent::__construct();
	}
	
	public function render(): array
	{
		return [$this->gateway];
	}
}
