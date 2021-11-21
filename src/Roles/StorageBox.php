<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class StorageBox extends Role
{
	public function __construct(private string $username, private string $password, private string $mount)
	{
	}
	
	public function apply(): void
	{
		$fstab = $this->file_get_contents('/etc/fstab');
		$fstab = array_filter(explode("\n", $fstab));
		$rendered = <<<DATA
//{$this->username}.your-storagebox.de/backup {$this->mount} cifs seal,iocharset=utf8,rw,credentials=/etc/{$this->username}-credentials.txt,uid=0,gid=0,file_mode=0660,dir_mode=0770 0 0
DATA;
		$found = false;
		$updated = false;
		foreach ($fstab as &$entry) {
			if (str_starts_with($entry, "//{$this->username}.your-storagebox.de/backup")) {
				$found = true;
				if ($entry !== $rendered) {
					$entry = $rendered;
					try {
						$this->exec("umount {$this->mount}");
					} catch (\LogicException) {
					}
					$updated = true;
				}
			}
		}
		if ( !$found) {
			$fstab[] = $rendered;
			$updated = true;
		}
		if ($updated) {
			$this->file_put_contents(
				"/etc/{$this->username}-credentials.txt",
				<<<DATA
username={$this->username}
password={$this->password}

DATA
			);
			$fstab = implode("\n", $fstab);
			$this->file_put_contents('/etc/fstab', $fstab."\n");
			$this->exec("mkdir {$this->mount}");
			$this->exec("mount {$this->mount}");
		}
	}
	
	public function getName(): string
	{
		return "StorageBox({$this->username}, {$this->mount})";
	}
}
