<?php

namespace ServerBuilder;

class ServerConfig
{
	public function __construct(public ServerDescription $serverDescription, public Roles $roles)
	{
	}
}
