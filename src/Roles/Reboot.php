<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class Reboot extends Role
{
	public function __construct(protected bool $force = false)
	{
	}
	
	public function apply(): void
	{
		if ( !$this->force) {
			try {
				$this->exec('stat /var/run/reboot-required');
			} catch (\LogicException) {
				$this->info('reboot not needed, skipping');
				
				return;
			}
		}
		
		try {
			$this->exec('reboot');
			sleep(30);
		} catch (\LogicException) {
			$this->info('waiting for reboot to complete');
		}
		
		$this->waitForBoot();
	}
	
	protected function waitForBoot(bool $assumeRebooted = false): void
	{
		if ($assumeRebooted && $this->ping()) {
			return;
		}
		
		// wait until we aren't running anymore...
		while (true) {
			self::info('waiting for power-off');
			$is_up = $this->ping();
			if ( !$is_up) {
				break;
			}
			sleep(2);
		}
		
		// now wait until we're running again
		while (true) {
			self::info('waiting for power-on');
			if ($this->ping()) {
				self::info('server is coming up, waiting for OS');
				sleep(10);
				break;
			}
		}
	}
	
	private function ping(): bool
	{
		exec('ping -c 1 '.$this->serverDescription->hostname, $output, result_code: $code);
		end($output);
		$line = prev($output);
		
		return str_contains($line, ' 1 received');
	}
	
	public function getName(): string
	{
		return 'Reboot()';
	}
}
