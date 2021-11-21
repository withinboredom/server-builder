<?php

use ServerBuilder\Roles\K3s;
use ServerBuilder\Roles\Network\Address;
use ServerBuilder\Roles\Network\EthernetCard;
use ServerBuilder\Roles\Network\Gateway6;
use ServerBuilder\Roles\Network\Nameservers;
use ServerBuilder\Roles\Network\NetPlan;
use ServerBuilder\Roles\Network\OnLinkRoute;
use ServerBuilder\Roles\Network\Vlan;
use ServerBuilder\ServerDescription;

$root = new ServerDescription(
	'example.com', region: 'somewhere', zone: 'rack1', netPlan: new NetPlan(
	new EthernetCard(
		'enp9s0',
		new Address('1.1.1.1/32'),
		new Address('2001::2/64'),
		new OnLinkRoute('1.0.0.1'),
		new Gateway6('fe80::1'),
		new Nameservers(
			new Address('185.12.64.1'), new Address('2a01:4ff:ff00::add:1')
		)
	), new Vlan(
		tag: 4000, mtu: 1400, link: 'enp9s0', configs: new Address('10.0.0.0/24')
	)
)
);

$servers = [
	new K3s($root, 'secret'),
];
