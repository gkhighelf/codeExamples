<?php
/**
* @package YandexAfishaParser
* @author Konstantin Gritsenko
* @version 0.5.0
*/

$cfg = array();
$cfg['pwd'] = str_replace( '\\', '/', getcwd() ) . '/';

function _dbg_( $text="" )
{
	if( __DEBUG__ ) echo $text;
	_flush();
}

function _flush (){
	flush();
	/*
	if (ob_get_length()){
		@ob_flush();
		@flush();
		@ob_end_flush();
	}
	@ob_start();
	*/
}

function get_execution_time()
{
	static $time_start = null;
	if($time_start === null)
	{
		$time_start = time();
		return 0.0;
	}
	return time(true) - $time_start;
}

function load_html( $url )
{
	$html = file_get_html( $url );
	if( $html === null )
	{
		echo "<h4>Get contents failed, may be now is update time for server data on yandex.</h4>";
		return null;
	}
	if( __DEBUG_DUMP__ ) $html->dump();
	return $html;
}

function get_file($filename,$newfile = TRUE) {
	if ($newfile === TRUE) {
		fclose(fopen($filename,"a+"));
	}
	@chmod($filename, 0700 );
	$fp = fopen($filename,'r');
	$filesize = filesize($filename);
	$filesize = ($filesize ===0) ? 1:$filesize-8;
	fseek($fp,8);
	$str = fread($fp,$filesize);
	fclose($fp);
	return (!empty($str))?unserialize($str):null;
}

function save_file($filename,$arr){
	$fp = fopen($filename,'w');
	fwrite( $fp, '<?die;?>'.serialize($arr) );
	fclose( $fp );
	@chmod( $filename, 0700 );
}
?>