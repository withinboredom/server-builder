<?php

namespace ServerBuilder\Roles\Network;

class EthernetCard extends NetPlan
{
	public function __construct(protected string $name, NetPlan ...$configs)
	{
		parent::__construct(...$configs);
	}
	
	public function render(): array
	{
		return [
			$this->name => [
				'addresses'   => $this->renderArray(Address::class, true),
				'routes'      => $this->renderArray(OnLinkRoute::class),
				'gateway6'    => $this->renderSingle(Gateway6::class)[0] ?? [],
				'nameservers' => $this->renderMap(Nameservers::class),
			],
		];
	}
}
