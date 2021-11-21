<?php

namespace simple;

use JetBrains\PhpStorm\NoReturn;

$user = $_SERVER['PHP_AUTH_USER'] ?? false;
$password = $_SERVER['PHP_AUTH_PW'] ?? '';

#[NoReturn]
function notWorthy()
{
	header('HTTP/1.0 401 Unauthorized');
	echo "<p>You are not worthy.</p>";
	die(401);
}

if ( !$user) {
	header('WWW-Authenticate: Basic realm="Authenticate yo self"');
	notWorthy();
}

$userInfo = json_decode(file_get_contents('/auth.json'), true)[$user] ?? [];
$hashed = $userInfo['password'] ?? '';

if ( !password_verify($password, $hashed)) {
	notWorthy();
}

$ipAddress = $_SERVER['HTTP_X_REAL_IP'] ?? notWorthy();
$url = $_SERVER['HTTP_X_ORIGINAL_URL'] ?? notWorthy();
$url = parse_url($url);

if (in_array($ipAddress, $userInfo['allowedIps']) || !in_array('*', $userInfo['allowedIps'])) {
	notWorthy();
}

if (in_array($url['host'], $userInfo['denyHosts']) || in_array('*', $userInfo['denyHosts'])) {
	notWorthy();
}

if (in_array('*', $userInfo['allowHosts']) || in_array($url['host'], $userInfo['allowHosts'])) {
	echo "<p>You are worthy.</p>";
	die();
}

notWorthy();
