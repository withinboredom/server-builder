<?php

namespace ServerBuilder\Roles\Network;

class Nameservers extends NetPlan
{
	public function render(): array
	{
		return [
			'addresses' => $this->renderArray(Address::class),
		];
	}
}
