<?php
/** 
 * LL-WP-Cache by Pasquale 'sid' Fiorillo
 *  
 * Set Cache-Control HTTP header based on post's date.
 * Useful to control Amazon CloudFront cache automatically.
 * 'Pretty Permalink' must be enabled: /2012/12/31/post-name/
 * Consider to use apache modules mod_headers.c and mod_expires.c to
 * power up your cache.
 * 
 * Usage: Add it on top of index.php
 * 
 * This is a fucking piece of code that works as-is whitout Wordpress
 * integration. You may want to use a native plugin to do the work... 
 * so, please, be aware of this unless you know what you are doing.
 */
 
if (preg_match('|^\/([0-9]{4}\/[0-9]{2}\/[0-9]{2})\/|', $_SERVER['REQUEST_URI'],  $match)) {
	$date = DateTime::createFromFormat('Y/m/d', $match[1]);
	if ($date) {
		$now = new DateTime();
		$age = $date->diff($now)->days;
		$control = '';
		if ($age == 0) {
			$max_age = 60*10; //10 minutes
			$control = 'must-revalidate, proxy-revalidate, ';
		} elseif ($age == 1) {
			$max_age = 60*30; //30 minutes
		} elseif ($age >= 2 && $age <= 7) {
			$max_age = 86400 * $age - 86400; //$age -1 day
		} elseif ($age > 7 && $age <= 30) {
			$max_age = 86400 * 7; //1 week
		} else {
			$max_age = 86400 * 30; //1 month
		}
		header('Cache-Control: public, '. $control .'max-age='.$max_age);
		//header('Expires: '.gmdate('D, d M Y H:i:s \G\M\T', time() + $max_age));
	}
}
?>
