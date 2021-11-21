<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class FirewallRule extends Role
{
	protected static array|null $original = null;
	
	public function __construct(
		private int $port,
		private string $action,
		private string $from = 'Anywhere',
		private $kind = 'tcp'
	) {
	}
	
	public function apply(): void
	{
		if (self::$original === null) {
			self::$original = $this->internalExec('ufw status verbose');
			if (count(self::$original) < 4) {
				$this->exec('ufw allow 22/tcp');
				$this->exec('ufw --force enable');
				self::$original = null;
				$this->apply();
				
				return;
			}
		}
		
		$defaults = array_map(
			fn($x) => array_filter(explode(' ', $x)),
			explode(
				',',
				explode(':', self::$original[2])
				[1]
			)
		);
		$defaults = array_combine(array_column($defaults, 2), array_column($defaults, 1));
		if ($defaults['(incoming)'] !== 'deny') {
			$this->exec('ufw default deny incoming');
		}
		if ($defaults['(outgoing)'] !== 'allow') {
			$this->exec('ufw default allow outgoing');
		}
		if ($defaults['(routed)'] !== 'allow') {
			$this->exec('ufw default allow routed');
		}
		
		$body = array_slice(self::$original, 7);
		$body = array_map(
			fn($a) => [
				'port'      => (int) explode('/', $a[0])[0],
				'kind'      => explode('/', $a[0])[1] ?? 'tcp-implied',
				'mode'      => match ($a[1]) {
					'(v6)' => 'ip6',
					default => 'ip4'
				},
				'action'    => match ($a[1]) {
					'(v6)' => $a[2],
					default => $a[1]
				},
				'direction' => match ($a[1]) {
					'(v6)' => $a[3],
					default => $a[2]
				},
				'from'      => match ($a[1]) {
					'(v6)' => $a[4],
					default => $a[3]
				},
			],
			array_filter(
				array_map(
					fn($k) => array_values(
						array_filter(
							explode(' ', $k)
						)
					),
					$body
				)
			)
		);
		
		$found = false;
		
		foreach ($body as $rule) {
			if ($rule['port'] === $this->port && $this->from === $rule['from'] && $rule['direction'] === 'IN') {
				$found = true;
				if ($this->kind !== $rule['kind'] || strtoupper($this->action) !== $rule['action']) {
					$this->updateFirewall($rule);
					break;
				}
			}
		}
		
		if ( !$found) {
			$this->addFirewall();
		}
	}
	
	private function updateFirewall(array $rule): void
	{
		if ($this->port === 22) {
			$this->exec('ufw --force disable');
		}
		$kind = $rule['kind'] === 'tcp-implied' ? '' : '/'.$rule['kind'];
		$actualAction = strtolower($rule['action']);
		$actualPort = $rule['port'];
		$this->exec("ufw delete $actualAction $actualPort$kind");
		$this->addFirewall();
		if ($this->port === 22) {
			$this->exec('ufw --force enable');
		}
	}
	
	private function addFirewall()
	{
		$this->exec("ufw {$this->action} {$this->port}/{$this->kind}");
	}
	
	public function getName(): string
	{
		return "FirewallRule({$this->port}, {$this->action}, {$this->from})";
	}
}
