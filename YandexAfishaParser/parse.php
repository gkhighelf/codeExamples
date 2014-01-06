<?php
/**
* @package YandexAfishaParser
* @author Konstantin Gritsenko
* @version 0.5.0
*/

	error_reporting(E_ALL);
	require 'includes/simple_html_dom.php';
	require 'includes/functions.php';
	require 'includes/classes.php';

	set_time_limit(0);
	get_execution_time();
	$afisha = get_file( $cfg['pwd'] . 'cache.php' );
	if( $afisha === null )
	{
		$afisha = new afisha();
		save_file( $cfg['pwd'] . 'cache.php', $afisha );
	}

	if( !isset( $_GET['country'] ) && !isset( $_GET['city'] ) )
	{
		header( 'Content-Type: text/html; charset=UTF-8');
		$afisha->toHTML();
		echo memory_get_usage()."</br>";
	}
	else
	{
		header('Content-Type: application/xml');
		//header("Content-length: 9999");
		header('Content-Disposition: attachment ; filename="schedule.xml"');
		//header( 'Content-Type: text/xml; charset=UTF-8');
		echo "<?xml version=\"1.0\" encoding=\"UTF-8\"?>\r\n";
		echo "<cinema_schedule>\r\n";
		_flush();
		$city = $afisha->countries[ $_GET['country'] ]->cities[ $_GET[ 'city' ] ];
		$city->parse();
		echo "<parsed/>\r\n";
		_flush();
		$g_films->toXML();
		_flush();
		echo $city->toXML();
		echo "</cinema_schedule>";
	}
	save_file( $cfg['pwd'] . 'films_cache.php', $g_films );
?>