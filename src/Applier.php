<?php

namespace ServerBuilder;

abstract class Applier extends Role
{
	private static array $finalizers = [];
	
	public static function ApplyRole(Role $role, ServerConfig|ServerDescription $server): void
	{
		$role->serverDescription = $server instanceof ServerConfig ? $server->serverDescription : $server;
		self::info("Applying role: ".$role->getName());
		$role->apply();
	}
	
	public static function RegisterFinalizer(callable $callback): void
	{
		self::$finalizers[] = $callback;
	}
	
	public static function ApplyFinal(): void
	{
		while (count(self::$finalizers)) {
			$cb = array_shift(self::$finalizers);
			$cb();
		}
	}
}
