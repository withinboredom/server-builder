<?php

namespace ServerBuilder;

abstract class Role
{
	public ServerDescription $serverDescription;
	private $sshOpts = "-o 'GlobalKnownHostsFile=/dev/null' -o 'UserKnownHostsFile=/dev/null' -o 'StrictHostKeyChecking=no'";
	
	protected static function warn(string $message): void
	{
		self::log($message, '1;31');
	}
	
	private static function log(string|array $message, string $color): void
	{
		static $lastLogged = '';
		
		if (is_array($message)) {
			$message = print_r($message, true);
		}
		
		if ($message === $lastLogged) {
			return;
		}
		$lastLogged = $message;
		$date = date('H:i:s');
		echo "\e[{$color}m$date: $message\e[0m\n";
	}
	
	protected static function info(string|array $message): void
	{
		self::log($message, '1;34');
	}
	
	public abstract function apply(): void;
	
	public abstract function getName(): string;
	
	protected function createYaml(array $data): string
	{
		$filename = tempnam('/tmp', '');
		\yaml_emit_file($filename, $data, YAML_UTF8_ENCODING, YAML_LN_BREAK);
		$this->copyTo($filename, $filename);
		
		return $filename;
	}
	
	protected function copyTo($src, $dst): void
	{
		$this->copy($src, "{$this->serverDescription->hostname}:$dst");
	}
	
	private function copy($src, $dst): void
	{
		$command = "scp {$this->sshOpts} $src $dst";
		self::trace($command);
		system($command, $code);
		if ($code) {
			throw new \LogicException('Failed to upload file');
		}
	}
	
	protected static function trace(string|array $message): void
	{
		self::log($message, '0;35');
	}
	
	protected function file_get_contents(string $filename): string
	{
		$destination = tempnam('/tmp', '');
		$this->exec("cp $filename $destination");
		$this->copyFrom($filename, $destination);
		
		return file_get_contents($destination);
	}
	
	protected function exec(string $command): void
	{
		$command = $this->getCommand($command);
		self::trace($command);
		system($command, $code);
		if ($code !== 0) {
			throw new \LogicException("$command failed with error!");
		}
	}
	
	protected function copyFrom($src, $dst): void
	{
		$this->copy("{$this->serverDescription->hostname}:$src", $dst);
	}
	
	private function getCommand(string $command): string
	{
		return "ssh {$this->sshOpts} {$this->serverDescription->hostname} '$command'";
	}
	
	protected function kubectl($command, $namespace, $asArray = true): array
	{
		static $kubeconfig = null;
		if ($kubeconfig === null) {
			$kubeconfig = tempnam('/tmp', '');
			$this->copyFrom('/etc/rancher/k3s/k3s.yaml', $kubeconfig);
			$config = yaml_parse_file($kubeconfig);
			$config['clusters'][0]['cluster']['server'] = "https://{$this->serverDescription->hostname}:6443";
			if (empty($config['preferences'])) {
				unset($config['preferences']);
			}
			yaml_emit_file($kubeconfig, $config);
		}
		$command = "kubectl --kubeconfig $kubeconfig -n $namespace $command";
		self::trace($command);
		exec($command.($asArray ? ' -o yaml' : ''), $output, $code);
		if ($code !== 0) {
			print_r($output);
			throw new \LogicException("Failed to complete $command in $namespace namespace");
		}
		
		return $asArray ? yaml_parse(implode("\n", $output)) : $output;
	}
	
	protected function file_put_contents(string $filename, string $data): void
	{
		$destination = tempnam('/tmp', '');
		file_put_contents($destination, $data);
		$this->copyTo($destination, $destination);
		$this->exec("cp $destination $filename");
	}
	
	protected function internalExec(string $command): array
	{
		$command = $this->getCommand($command);
		self::trace($command);
		exec($command, $output, $result);
		
		if ($result) {
			throw new \LogicException();
		}
		
		return $output;
	}
}
