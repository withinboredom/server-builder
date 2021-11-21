<?php

namespace ServerBuilder\Roles\Network;

class NetPlan
{
	protected array $configs;
	
	public function __construct(NetPlan ...$configs)
	{
		$this->configs = $configs;
	}
	
	public function render(): array
	{
		$map = [
			'network' => [
				'version'   => 2,
				'renderer'  => 'networkd',
				'ethernets' => $this->renderMap(EthernetCard::class),
				'vlans'     => $this->renderMap(Vlan::class),
			],
		];
		
		return $this->clean($map);
	}
	
	protected function renderMap(string ...$classes): array
	{
		$rendered = $this->renderSelected(...$classes);
		if (empty($rendered)) {
			return [];
		}
		
		return array_intersect_assoc(...$rendered);
	}
	
	private function clean(array $map): array
	{
		$target = [];
		foreach ($map as $key => $value) {
			if (is_array($value) && !empty($value)) {
				$target[$key] = $this->clean($value);
				continue;
			}
			if ( !is_array($value)) {
				$target[$key] = $value;
			}
		}
		
		return $target;
	}
	
	protected function renderArray(string ...$classes): array
	{
		return array_column($this->renderSelected(...$classes), 0);
	}
	
	protected function renderSelected(string ...$classes): array
	{
		return array_map(
			fn($config) => $config->render(),
			$this->getConfig(...$classes)
		);
	}
	
	public function getConfig(string ...$classes): array
	{
		return array_filter($this->configs, fn($config) => in_array($config::class, $classes));
	}
	
	protected function renderSingle(string ...$classes): mixed
	{
		return array_values($this->renderSelected(...$classes))[0] ?? null;
	}
}
