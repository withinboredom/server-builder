<?php

namespace ServerBuilder\Roles;

use ServerBuilder\Role;

class Dapr extends Role
{
	public function __construct(protected string $version)
	{
	}
	
	public function apply(): void
	{
		try {
			$versions = $this->internalExec('dapr --version');
			$cliVersion = trim(array_map(fn($x) => explode(':', $x, 2)[1] ?? null, array_filter($versions))[0]);
			$pods = $this->kubectl('get pods', 'dapr-system');
			$installedVersion = null;
			foreach ($pods['items'] as $pod) {
				if ($pod['metadata']['labels']['app'] === 'dapr-operator') {
					$installedVersion = trim($pod['metadata']['labels']['app.kubernetes.io/version']);
				}
			}
			if ($cliVersion !== $this->version) {
				$this->install();
			}
			if ($installedVersion !== $this->version && $installedVersion === null) {
				$this->install();
			} elseif ($installedVersion !== $this->version) {
				$this->exec("dapr upgrade -k --runtime-version {$this->version}");
			}
		} catch (\LogicException) {
			$this->install();
		}
	}
	
	private function install(): void
	{
		$this->exec(
			"wget -q https://raw.githubusercontent.com/dapr/cli/master/install/install.sh -O - | /bin/bash -s - {$this->version}"
		);
	}
	
	public function getName(): string
	{
		return "Dapr({$this->version})";
	}
}
