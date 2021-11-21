<?php

namespace ServerBuilder\Roles;

class Rescue extends Reboot
{
	public function __construct()
	{
		parent::__construct(true);
	}
	
	public function apply(): void
	{
		$this->waitForBoot(true);
		try {
			$hostname = $this->internalExec('hostname');
			if ($hostname[0] !== 'rescue') {
				return;
			}
			self::info('Building from rescue');
			$this->file_put_contents(
				'/autosetup',
				file_get_contents(
					__DIR__.'/../../'.$this->serverDescription->hostname.'.conf'
				)
			);
			$this->exec('/root/.oldroot/nfs/install/installimage');
			
			parent::apply();
		} catch (\LogicException) {
			$this->apply();
		}
	}
	
	public function getName(): string
	{
		return "Rescue()";
	}
}
