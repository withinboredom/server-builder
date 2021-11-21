<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class FileSync extends Role
{
	public function __construct(protected string $src, protected string $dst, protected string $mod = '')
	{
	}
	
	public function apply(): void
	{
		$copied = false;
		try {
			$this->exec('mkdir -p '.dirname($this->dst));
			$mtime = $this->internalExec("date -r {$this->dst}");
			$dmtime = new \DateTimeImmutable($mtime[0]);
			$smtime = new \DateTimeImmutable(date('Y-m-d H:i:s', filemtime($this->src)));
			if ($dmtime < $smtime) {
				$this->copyTo($this->src, $this->dst);
				$copied = true;
			}
		} catch (\LogicException) {
			self::info("No {$this->dst}");
			$this->copyTo($this->src, $this->dst);
			$copied = true;
		}
		if ($copied && !empty($this->mod)) {
			$this->exec("chmod {$this->mod} {$this->dst}");
		}
	}
	
	public function getName(): string
	{
		return "FileSync({$this->src}, {$this->dst})";
	}
}
