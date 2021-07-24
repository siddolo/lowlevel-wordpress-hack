<?php
/**
 * LL-WP-Ban by Pasquale 'sid' Fiorillo
 * 
 * Automatically ban IP if they make too many requests.
 * BAN action: Return 403 HTTP Code without loading anymore.
 * 
 * Work with Amazon CloudFront/Elastic Balancer
 * Usage: Add it on top of wp-login.php or/and xmlrpc.php
 * 
 * This is a fucking piece of code that works as-is whitout Wordpress
 * integration. You may want to use a native plugin to do the work... 
 * so, please, be aware of this unless you know what you are doing.
 */

define('WPBAN_DBFILE', 'wpban.db');
define('WPBAN_REQUEST_MAX', 5);		// Number of requests which trigger ban action
define('WPBAN_REQUEST_TIME', 10);	// Request is counted if the time difference between two request is lower than REQUEST_TIME (in seconds)
define('WPBAN_BAN_TIME', 60 * 10);	// Ban duration (in seconds)

// Permanent referral ban, add your own
$bannedReferral = array(
	'anonymousfox.co'
);

// Permanent IP ban, add your own
$bannedIp = array(
	//'94.142.233.173'
);

// ************************* END CONFIGURATION *************************
$permanentFlag = FALSE;

// Get source IP address from X-Forwarded-For HTTP Header
$headers = getallheaders();
if (preg_match('|(.*), .*|', $headers['X-Forwarded-For'], $matches)) {
	$srcAddress = $matches[1];
} else {
	$srcAddress = $headers['X-Forwarded-For'];
}

// Open db if exists or initialize it
if (file_exists(WPBAN_DBFILE) && $buffer = file_get_contents(WPBAN_DBFILE)) {
	$addressList = unserialize($buffer);
} else {
	$addressList = array();
}

// Ban if referral match
if (in_array($_SERVER['HTTP_REFERER'], $bannedReferral)) {
	$addressList[$srcAddress] = array('banned' => TRUE);
	$permanentFlag = TRUE;
}

// Ban permeanent IP
foreach ($bannedIp as $address) {
	$addressList[$address] = array('banned' => TRUE);
	$permanentFlag = TRUE;
}

// Count attempt
if (!$permanentFlag) {
	if (
		$_SERVER['REQUEST_METHOD'] === 'POST' &&
		isset($addressList[$srcAddress]) && 
		isset($addressList[$srcAddress]['attempt']) && 
		isset($addressList[$srcAddress]['last']) && 
		isset($addressList[$srcAddress]['prev'])
	) {
		$addressList[$srcAddress]['prev'] = $addressList[$srcAddress]['last'];
		$addressList[$srcAddress]['last'] = time();
		if ($addressList[$srcAddress]['last'] - $addressList[$srcAddress]['prev'] <= WPBAN_REQUEST_TIME) {
			$addressList[$srcAddress]['attempt']++;
		}
		if ($addressList[$srcAddress]['attempt'] == WPBAN_REQUEST_MAX) {
			$addressList[$srcAddress]['banned'] = TRUE;
		}
	} else {
		$addressList[$srcAddress] = array(
			'attempt' => 1, 
			'prev' => time(), 
			'last' => time(), 
			'banned' => FALSE
		);
	}

	// Check if it is time to unban it
	if (
		isset($addressList[$srcAddress]) && 
		isset($addressList[$srcAddress]['last']) && 
		isset($addressList[$srcAddress]['prev']) && 
		($addressList[$srcAddress]['last'] - $addressList[$srcAddress]['prev'] >= WPBAN_BAN_TIME)
	) {
		// unban
		unset($addressList[$srcAddress]);
	}
}

// Update DB
file_put_contents(WPBAN_DBFILE, serialize($addressList), LOCK_EX);

// If banned, go away
if (
	isset($addressList[$srcAddress]) && 
	isset($addressList[$srcAddress]['banned']) && 
	$addressList[$srcAddress]['banned'] === TRUE
) {
	header('HTTP/1.0 403 Forbidden');
	echo "<html><body><h1>Forbidden</h1></body></html>";
	exit(0);
}
?>
