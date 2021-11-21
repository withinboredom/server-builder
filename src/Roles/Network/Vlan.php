<?php

namespace ServerBuilder\Roles\Network;

class Vlan extends NetPlan
{
	public function __construct(
		protected int $tag,
		protected int $mtu,
		protected string $link,
		NetPlan ...$configs
	) {
		parent::__construct(...$configs);
	}
	
	public function render(): array
	{
		return [
			"{$this->link}.{$this->tag}" => [
				'id'        => $this->tag,
				'link'      => $this->link,
				'mtu'       => $this->mtu,
				'addresses' => $this->renderArray(Address::class),
			],
		];
	}
}
