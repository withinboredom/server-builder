<?php

namespace ServerBuilder;

use ServerBuilder\Roles\Network\Address;
use ServerBuilder\Roles\Network\EthernetCard;
use ServerBuilder\Roles\Network\NetPlan;
use ServerBuilder\Roles\Network\Vlan;

class ServerDescription
{
	private Address $externalIpv4;
	private Address $externalIpv6;
	private Address $vlanIpv4;
	
	public function __construct(
		public string $hostname,
		public NetPlan $netPlan,
		public string $region,
		public string $zone
	) {
		$cards = $this->netPlan->getConfig(EthernetCard::class);
		$addresses = array_merge(...array_map(fn(EthernetCard $card) => $card->getConfig(Address::class), $cards));
		$ip4s = array_filter($addresses, fn(Address $address) => $address->isIp4());
		$ip6s = array_filter($addresses, fn(Address $address) => !$address->isIp4());
		$this->externalIpv4 = array_shift($ip4s);
		$this->externalIpv6 = array_shift($ip6s);
		
		$vnets = $this->netPlan->getConfig(Vlan::class);
		$addresses = array_values(array_merge(...array_map(fn(Vlan $a) => $a->getConfig(Address::class), $vnets)));
		$this->vlanIpv4 = $addresses[0];
	}
	
	public function externalIpv4(): Address
	{
		return $this->externalIpv4;
	}
	
	public function externalIpv6(): Address
	{
		return $this->externalIpv6;
	}
	
	public function vlanIp(): Address
	{
		return $this->vlanIpv4;
	}
}
