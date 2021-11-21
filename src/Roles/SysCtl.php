<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Applier;
use ServerBuilder\Role;

class SysCtl extends Role
{
	protected static array $finalized = [];
	protected array $sysctl = [];
	
	public function __construct(protected string $name, protected string $value)
	{
		if (str_contains($this->value, ' ')) {
			$this->value = "\"{$this->value}\"";
		}
	}
	
	public function apply(): void
	{
		if (empty($this->sysctl)) {
			$this->updateLocal();
		}
		
		$found = false;
		$updated = false;
		foreach ($this->sysctl as &$line) {
			if (str_starts_with($line, $this->name)) {
				[$setting, $value] = explode('=', $line, 2);
				if ($setting === $this->name) {
					$found = true;
					if ($value !== $this->value) {
						$line = "{$this->name}={$this->value}";
						$updated = true;
					}
				}
			}
		}
		
		if ( !$found) {
			$this->sysctl[] = "{$this->name}={$this->value}";
			$updated = true;
		}
		
		if ($updated) {
			$this->updateLocal(true);
			Applier::RegisterFinalizer(fn() => $this->finalize());
		}
		
		$this->exec("sysctl -w {$this->name}={$this->value}");
	}
	
	protected function updateLocal(bool $upload = false): void
	{
		if ($upload) {
			$file = implode("\n", $this->sysctl);
			$this->file_put_contents('/etc/sysctl.d/99-bc.conf', $file);
		}
		$this->exec('touch /etc/sysctl.d/99-bc.conf');
		$sysctl = $this->file_get_contents('/etc/sysctl.d/99-bc.conf');
		$this->sysctl = explode("\n", $sysctl);
	}
	
	protected function finalize(): void
	{
		if ($this->isFinalized()) {
			return;
		}
		$this->exec('sysctl -p');
		$this->markFinalized();
	}
	
	protected function isFinalized(): bool
	{
		return self::$finalized[$this->serverDescription->hostname] ?? false;
	}
	
	protected function markFinalized(): void
	{
		self::$finalized[$this->serverDescription->hostname] = true;
	}
	
	public function getName(): string
	{
		return "SysCtl({$this->name}, {$this->value})";
	}
}
