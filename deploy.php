#!/usr/bin/php
<?php

require_once __DIR__.'/vendor/autoload.php';
require_once __DIR__.'/servers.php';

use ServerBuilder\Applier;
use ServerBuilder\Roles;

const DOCKER_USER = 'withinboredom';
const HASH_COST = 11;

global $servers;
global $root;

$target = $argv[2] ?? null;
$mode = $argv[1] ?? 'server';
$all = $mode === 'all';

switch ($mode) {
	case 'server':
		foreach ($servers as $hostname => $server) {
			foreach ($server->roles->roles as $role) {
				if ($target !== null && $target !== $server->serverDescription->hostname) {
					continue;
				}
				Applier::ApplyRole($role, $server);
			}
			Applier::ApplyFinal();
		}
		break;
	
	case 'cluster':
		Applier::ApplyRole(new Roles\FileSync(__DIR__.'/auth.json', '/mnt/registry-data/auth.json'), $root);
		Applier::ApplyRole(new Roles\FileSync(__DIR__.'/bin/kgctl-linux-amd64', '/usr/local/bin/kgctl', '+x'), $root);
		Applier::ApplyRole(new Roles\Dapr('1.5.0'), $root);
		
		foreach (scandir(__DIR__.'/deployments') as $file) {
			if (in_array($file, ['.', '..'])) {
				continue;
			}
			
			$role = (new class($file) extends \ServerBuilder\Role {
				public function __construct(private string $file)
				{
				}
				
				public function apply(): void
				{
					$path = __DIR__.'/deployments/'.$this->file;
					$file = file_get_contents($path);
					if (str_starts_with($file, '# from: ')) {
						$path = str_replace('# from: ', '', explode("\n", $file, 2)[0]);
						self::info('using deployment from '.$path);
						$file = file_get_contents($path);
						$this->file_put_contents("/var/lib/rancher/k3s/server/manifests/{$this->file}", $file);
						
						return;
					}
					$data = yaml_parse($file);
					if ($namespace = $data['spec']['targetNamespace'] ?? false) {
						try {
							$this->kubectl("create namespace $namespace", "default");
						} catch (LogicException) {
							// already exists
						}
					}
					$this->copyTo($path, '/var/lib/rancher/k3s/server/manifests');
				}
				
				public function getName(): string
				{
					return "Deploy ".$this->file;
				}
			});
			Applier::ApplyRole($role, $root);
		}
		Applier::ApplyFinal();
		break;
	
	case 'user':
		if ($argv[2] !== 'update') {
			$user = readline('User: ');
			$password = readline('Password: ');
			$reason = readline('Reason: ');
			$users = json_decode(file_get_contents(__DIR__.'/auth.json'), true);
			if (isset($users[$user])) {
				echo "Will write over existing password in 2s";
				sleep(2);
			}
			$users[$user] = [
				'password'   => password_hash($password, PASSWORD_BCRYPT, ["cost" => HASH_COST]),
				'reason'     => $reason,
				'allowHosts' => ['*'],
				'denyHosts'  => [],
				'allowedIps' => ['*'],
			];
			ksort($users);
			file_put_contents(__DIR__.'/auth.json', json_encode($users, JSON_PRETTY_PRINT));
		}
		$tag = sha1(file_get_contents(__DIR__.'/auth.json'));
		$dockerUser = DOCKER_USER;
		system("docker build --push --pull -t $dockerUser/passwords:$tag -f auth.Dockerfile .");
		$deployment = yaml_parse_file(__DIR__.'/deployments/registry.yaml', -1);
		$output = [];
		foreach ($deployment as $doc) {
			if ($doc['kind'] === 'Deployment' && $doc['metadata']['name'] === 'registry-auth') {
				$doc['spec']['template']['spec']['containers'][0]['image'] = "$dockerUser/passwords:$tag";
			}
			
			$output[] = explode("\n", yaml_emit($doc));
		}
		file_put_contents(
			__DIR__.'/deployments/registry.yaml',
			implode(
				"\n",
				array_map(
					fn($x) => implode(
						"\n",
						['---'] + array_slice($x, 0, -2) + ['---']
					),
					$output
				)
			)
		);
		system('./deploy cluster');
		break;
}

exit(0);
