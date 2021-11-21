<?php

namespace ServerBuilder\Roles\Network;

class OnLinkRoute extends NetPlan
{
	public function __construct(protected string $gateway)
	{
		parent::__construct();
	}
	
	public function render(): array
	{
		return [
			[
				'on-link' => true,
				'to'      => '0.0.0.0/0',
				'via'     => $this->gateway,
			],
		];
	}
}
