<?php
/**
 * LL-WP-Crawler-DoS by Pasquale 'sid' Fiorillo
 * 
 * Temporary reduce server load caused by too fast googlebot/bingbot
 * crawl rate.
 * THIS IS DANGEROUS script, please control crawl rate by robots.txt 
 * and Google/Bing Webmaster Tools instead.
 * 
 * This is a fucking piece of code that works as-is whitout Wordpress
 * integration. You may want to use a native plugin to do the work... 
 * so, please, be aware of this unless you know what you are doing.
 */
 
define('AGE_THRESHOLD', 365); 					// Days
define('CACHE_CONTROL_MAX_AGE', 3600 * 30); 	// Seconds

function bye() {
	header("HTTP/1.1 304 Not Modified"); 
	header('Cache-Control: public, must-revalidate, proxy-revalidate, max-age=' . CACHE_CONTROL_MAX_AGE);
	exit(0);
}

$header = getallheaders();
if (isset($header['From']) && (preg_match('|^googlebot|', $header['From']) || preg_match('|^bingbot|', $header['From']))) {
	if (preg_match('|^\/([0-9]{4}\/[0-9]{2}\/[0-9]{2})\/|', $_SERVER['REQUEST_URI'],  $match)) {
		$date = DateTime::createFromFormat('Y/m/d', $match[1]);
		if ($date) {
			$now = new DateTime();
			$age = $date->diff($now)->days;
			if ($age >= AGE_THRESHOLD) {
				bye();
			}
		}
	} elseif (preg_match('|^\/tags?\/|', $_SERVER['REQUEST_URI'])) {
			bye();
	}
}
?>
