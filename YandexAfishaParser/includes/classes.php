<?php
/**
* @package YandexAfishaParser
* @author Konstantin Gritsenko
* @version 0.5.0
*/

define( 'YANDEX_AFISHA_BASE', 'http://afisha.yandex.ru' );
define( 'COUNTRIES_AND_CITIES', '/change_city/' );
define( 'FILMS', '/events/' );
define( 'CINEMAS_URL', 'places/?category=cinema&limit=1000' );
define( 'CINEMA_SCHEDULE', '?days=100' );
define( 'COUNTRIES_HOLDER', 'div.choose_city' );
define( 'COUNTRY_ENTRY', 'div.country' );
define( 'CITY_FILMS', 'table.chooser_data' );
define( 'CITY_CINEMAS', 'table.chooser_data' );
define( 'EVENT_SCHEDULE', 'li.vevent' );
define( 'FILM_TITLE', 'div.announce h2');
define( 'FILM_DESCRIPTION', 'dl.critique dd p');
define( '__DEBUG__', false );
define( '__DEBUG_DUMP__', false );

$g_films = get_file( $cfg['pwd'] . 'films_cache.php' );
if( $g_films === null )
{
	$g_films = new films();
}

class afisha
{
	public $countries = array();
	public $films = null;
	
	function __construct()
	{
		$html = load_html( YANDEX_AFISHA_BASE . COUNTRIES_AND_CITIES );
		if( $html == null ) return null;
		foreach( $html->find( COUNTRIES_HOLDER ) as $choose_city )
		{
			$cnt = 0;
			foreach( $choose_city->find( COUNTRY_ENTRY ) as $h_country ) {
				$this->countries[] = new country( $h_country, $cnt++ );
				unset( $h_country );
			}
			unset( $choose_city );
		}
		$html->clear();
		unset( $html );
	}

	function toXML()
	{
		global $g_films;
		$res = "<cinema_schedule>";
		$res .= $g_films->toXML();
		$res .= "<countries>";
		foreach( $this->countries as $country )
		{
			$res .= $country->toXML();
		}
		$res .= "</countries></cinema_schedule>";
		return $res;
	}

	function toHTML()
	{
		$str = "<table width='100%'><tr>";
		foreach( $this->countries as $c )
		{
			$str .= "<td valign='top'>".$c->toHTML()."</td>";
		}
		$str .= "</tr></table>";
		echo $str;
		unset( $str );
	}
}

class country
{
	public $c_id = null;
	public $c_name = null;
	public $cities = array();
	public $c_db_id = null;

	function __construct( $dom = null, $id = 0 )
	{
		if( $dom == null ) return;
		$this->c_name = $dom->find( 'h2', 0 )->plaintext;
		$this->c_id = $id;
		_dbg_( "<h1>Страна - ".$this->c_name." : </h1>" );

		$cnt = 0;
		foreach( $dom->find( "a" ) as $h_city )
		{
			$this->cities[] = new city( $h_city, $cnt++ ); 
		}
	}

	function toXML()
	{
		$res = "<country><title>" . $this->c_name . "</title><cities>\r\n";
		foreach( $this->cities as $city )
		{
			$res .= $city->toXML();
		}
		$res .= "</cities></country>\r\n";
		return $res;
	}

	function toHTML()
	{
		$res = "<h4>" . $this->c_name . "</h4>";
		foreach( $this->cities as $city )
		{
			$res .= $city->toHTML( '&country='.$this->c_id );
		}
		return $res;
	}
}

class city
{
	public $c_id = null;
	public $c_name = null;
	public $c_href = null;
	public $cinemas = array();
	public $c_db_id = null;
	public $country = null;
	public $last_cinema_id = 0;

	function __construct( $dom = null, $id = 0 )
	{
		if( $dom == null ) return;
		$this->last_cinema_id = 0;
		$this->c_id = $id;
		$this->c_name = $dom->plaintext;
		$this->c_href = $dom->href;
		_dbg_( "<h2>Город - ".$this->c_name." : </h2>" );
	}

	function parse()
	{
		if( ( $html = load_html( YANDEX_AFISHA_BASE . $this->c_href . CINEMAS_URL ) ) == null ) return;
		foreach( $html->find( CITY_CINEMAS ) as $h_cinemas_table )
		{
			$this->last_cinema_id = 0;
			foreach( $h_cinemas_table->find( 'tr.place' ) as $h_cinema_row )
			{
				$this->cinemas[] = new cinema( $h_cinema_row, $this->last_cinema_id++ );
				unset( $h_cinema_row );
			}
			unset( $h_cinemas_table );
		}
		$html->clear();
		unset( $html );
	}

	function toXML()
	{
		echo "<city><title>" . $this->c_name . "</title><cinemas>\r\n";
		foreach( $this->cinemas as $cinema )
		{
			echo $cinema->toXML();
			_flush();
		}
		echo "</cinemas></city>\r\n";
	}

	function toHTML( $variables )
	{
		return "<a href='parse.php?city=".$this->c_id.$variables."'>".$this->c_name."</a></br>";
	}
}

class cinema
{
	public $c_id = null;
	public $c_name = null;
	public $c_schedules = array();
	public $c_db_id = null;
	public $c_yandex_id = null;
	public $c_href = null;
	public $films = array();

	function __construct( $dom = null, $cid = 0 )
	{
		if( $dom == null ) return;
		$this->c_id = $cid;
		$a_cinema = $dom->find( 'a', 0 );
		//echo $a_cinema->href."\r\n";
		_flush();
		$str = substr( $a_cinema->href, 1, strlen( $a_cinema->href ) - 1 );
		$a_str = explode( "/", $str );
		$this->c_href = $a_cinema->href;
		$this->c_yandex_id = $a_str[2];
		$this->c_name = $a_cinema->plaintext;
		_dbg_( "<h3>Кинотеатр - " . $this->c_name . " : </h3>" );
		unset( $a_str );
		unset( $str );
		unset( $a_cinema );
		$this->parse_schedule();
	}

	function parse_schedule()
	{
		global $g_films;
		_dbg_( "<h3>" . YANDEX_AFISHA_BASE . $this->c_href . CINEMA_SCHEDULE . "</h3>" );
//		if( ( $html = load_html( YANDEX_AFISHA_BASE . $this->c_href . CINEMA_SCHEDULE ) ) == null ) return;
		if( ( $html = load_html( YANDEX_AFISHA_BASE . $this->c_href . CINEMA_SCHEDULE ) ) === null ) return;
		foreach( $html->find( EVENT_SCHEDULE ) as $v_schedule )
		{
			$str = $v_schedule->class;
			$str = substr( $str, strpos( $str, " " ) + 1 );
			$str = str_replace( 'T', '_', $str );
			list( $f_id, $c_id, $date, $time ) = explode( '_', $str );
			$title = $v_schedule->find( 'span.summary', 0 )->plaintext;
			_dbg_( "<h4>" . $title . "</h4>" );
			$g_films->add( $title, $f_id );
			if( !array_key_exists( $f_id, $this->films ) )
			{
				$this->films[ $f_id ] = array();
			}
			if( !array_key_exists( $date, $this->films[ $f_id ] ) )
			{
				$this->films[ $f_id ][ $date ] = array();
			}
			$this->films[ $f_id ][ $date ][] = $time;
			unset( $v_schedule );
		}
		$html->clear();
		unset( $html );
	}

	function toXML()
	{
		echo "<cinema id='" . $this->c_id . "'><title>" . $this->c_name . "</title>\r\n";
		foreach( $this->films as $fkey => $farr )
		{
			echo "<schedule film_id='" . $fkey . "'>";
			foreach( $farr as $dkey => $darr )
			{
				$tstr = "";
				echo "<date value='" . $dkey . "'>\r\n";
				foreach( $darr as $time )
				{
					$tstr .= $time.",";
				}
				$tstr = substr( $tstr, 0, strlen( $tstr ) - 1 );
				echo $tstr . "</date>\r\n";
				unset( $tstr );
			}
			echo "</schedule>\r\n";
			_flush();
		}
		echo "</cinema>\r\n";
	}
}

class schedule
{
	public $s_date = null;
	public $s_time = null;
	public $s_film = null;
	public $s_cinema = null;
}

class film
{
	public $f_hash_id = null;
	public $inner_id = 0;
	public $f_event_href = null;
	public $f_title = null;
	public $f_description = null;
	public $f_db_id = null;
	public $f_yandex_id = null;
	public $f_yandex_ids = array();
	
	function __construct( $f_y_id = null, $in_id = 0 )
	{
		if( $f_y_id == null ) return null;
		$this->f_yandex_id = $f_y_id;
		_dbg_( "<h4>".YANDEX_AFISHA_BASE . FILMS . $this->f_yandex_id . "/"."</h4>" );
		$html = load_html( YANDEX_AFISHA_BASE . FILMS . $this->f_yandex_id . "/" );
		if( $html == null ) return null;
		$this->f_title = $html->find( FILM_TITLE, 0 )->plaintext;
		$this->f_hash_id = "a".crc32( $this->f_title );
		_dbg_( "<h4>". $this->f_title ."</h4>" );
		if( ( $fd = $html->find( FILM_DESCRIPTION, 1 ) ) !== null )
		{
			$this->f_description = $fd->plaintext;
		}
		unset( $fd );
		_dbg_( "<h4>". $this->f_description ."</h4>" );
		$this->inner_id = $in_id;
		$html->clear();
		unset( $html );
	}

	public function toXML()
	{
		$yids = "";
		foreach( $this->f_yandex_ids as $yid )
		{
			$yids .= $yid.",";
			unset( $yid );
		}
		$yids = substr( $yids, 0, strlen( $yids ) - 1 );
		echo "<film id='" . $this->inner_id . "'><yandexids>" . $yids . "</yandexids><title>" . $this->f_title . "</title><description><![CDATA[" . $this->f_description . "]]></description></film>\r\n";
		unset( $yids );
	}
}

class films
{
	public $last_gid = 0;
	public $films = array();
	public $yandex_films = array();

	function __construct()
	{
		$this->last_gid = 0;
	}

	public function add( $title, $yid = null )
	{
		if( $this->get( $title, $yid ) == null )
		{
			$nf = new film( $yid, $this->last_gid++ );
			$this->films[ $nf->f_hash_id ] = $nf;
			$this->films[ $nf->f_hash_id ]->f_yandex_ids[] = $yid;
			$this->yandex_films[ $nf->f_yandex_id ] = $nf->f_hash_id;
			unset( $nf );
		}
	}

	public function get( $title, $yid = null )
	{
		$f_hash_id = "a".crc32( $title );
		_dbg_( "<h4>" . $title . " : " . $yid . " : " . $f_hash_id . " : " . array_key_exists( $f_hash_id, $this->films ) . "</h4>" );
		
		if( array_key_exists( $f_hash_id, $this->films ) )
		{
//			_dbg_( "<h4>exists.....</h4>" );
			if( $yid != null )
			{
				if( array_search( $yid,  $this->films[ $f_hash_id ]->f_yandex_ids ) === false )
				{
					$this->films[ $f_hash_id ]->f_yandex_ids[] = $yid;
				}
				else
//					_dbg_( "<h4>KEY $yid exists.....</h4>" );
				if( !array_key_exists( $yid, $this->yandex_films ) )
				{
					$this->yandex_films[ $yid ] = $f_hash_id;
				}
			}
			return $this->films[ $f_hash_id ];
		}
//		_dbg_( "<h4>not exists.....</h4>" );
		return null;
	}

	public function dump()
	{
		print_r( $this->yandex_films );
	}

	public function toXML()
	{
		echo "<films>\r\n";
		//echo "</br>films->toXML before loop ".memory_get_usage()."</br>";
		foreach( $this->films as $f )
		{
			$f->toXML();
			_flush();
			unset( $f );
			//echo "</br>films->toXML inner loop ".memory_get_usage()."</br>";
		}
		//echo "</br>films->toXML after loop ".memory_get_usage()."</br>";
		echo "</films>\r\n";
	}
}
?>