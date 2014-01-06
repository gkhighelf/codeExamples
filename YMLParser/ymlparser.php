<?php
/**
 * YML XML file parsers helper.
 *
 * @category   Library
 * @author     HighElf
 * @copyright  (c) 2013 HighElf
 */
class YMLParser
{
	private $tfv = array(
		"true" => 1,
		"false" => 0
	);
	private $_bulkInsertProcessor = null;

	private $local_yml_file	= null;
	private $remote_yml_url = null;

	/** @var XMLReader */
	private $reader			= null;

	private $currencies		= null;
	private $categories		= null;
	private $offers			= null;

	private $unique_shop_id	= null;

	private $shop_bindings		= array( 'name','company','url','platform','version','agency','email' );
	private $offers_bindings	= array( 'offer' );
	private $offer_bindings		= array( 'url','price','picture','name','model','description' );
	private $category_bindings	= array( 'category' );
	private $currency_bindings	= array( 'currency' );

	private $current_offers_index = array();
	private $current_offers_pictures_index = array();

	private $_campaignId = null;
	private $_db = null;
	private $_downloaded = false;

	private $_offer_fields_index = array(
		'campaign_id'		=> 0,
		'offer_id'			=> 1,
		'offer_url'			=> 2,
		'offer_picture'		=> 3,
		'offer_name'		=> 4,
		'offer_model'		=> 5,
		'offer_description'	=> 6,
		'offer_price'		=> 7
	);

	private function useDatabase()
	{
		return $this->_db !== null;
	}

	public function __construct( $campaignId, $db )
	{
		$this->_downloaded = false;
		$this->_campaignId = $campaignId;
		$this->_db = &$db;
		if( $this->useDatabase() )
		{
			$this->_bulkInsertProcessor = new BulkDataProcessing( $db, 1024 * 500 );
		    $this->_bulkInsertProcessor->createSet(
    			'yml_offers',
    			'sys_yml_offers',
    			array_keys( $this->_offer_fields_index ),
    			array_keys( $this->_offer_fields_index )
		    );
		}
	}

	/**
	 * Указываем полный путь к локальному файлу.
	 * @param $file - полный путь к локальному файлу.
	 */
	public function setLocalSource( $file )
	{
		$this->local_yml_file = $file;
		return $this;
	}

	/**
	 * Указываем ссылку на удалённый файл для загрузки
	 * @param $file - ссылка на удалённый файл для загрузки
	 */
	public function setRemoteURL( $file )
	{
		$this->remote_yml_url = $file;
		return $this;
	}

    private $_preloadData = null;
    private function _curlReader( $curl, $chunk )
    {
        $limit = 500;

        $len = strlen( $this->_preloadData ) + strlen( $chunk );
        if( $len >= $limit )
        {
            $this->_preloadData .= substr( $chunk, 0, $limit-strlen( $this->_preloadData ) );
            return -1;
        }

        $this->_preloadData .= $chunk;
        return strlen($chunk);
    }

    public function getCatalogDate()
    {
        if( !empty( $this->_catalogDate ))
            return $this->_catalogDate;
        return false;
    }

    private $_catalogDate = null;
    public function preload()
    {
        if( !empty( $this->remote_yml_url ) )
        {
            $this->_preloadData = "";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $this->remote_yml_url );
            curl_setopt($ch, CURLOPT_RANGE, '0-500');
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            curl_setopt($ch, CURLOPT_WRITEFUNCTION, array( $this, '_curlReader' ) );
            $result = curl_exec($ch);
            curl_close($ch);
            preg_match( "/<yml_catalog date=\"([^\"]*)\">/i", $this->_preloadData, $res );
            if( $res )
            {
                $this->_catalogDate = $res[1];
                echo( $this->_catalogDate . "\r\n" );
            }
        }
        return !empty( $this->_catalogDate );
    }

    public function remoteFileExists()
    {
        if( !empty( $this->remote_yml_url ) )
        {
            $ch = curl_init( $this->remote_yml_url );
            curl_setopt($ch, CURLOPT_NOBODY, true);
            curl_exec($ch);
            $retcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return 400 > $retcode;
        }
        return false;
    }

	public function download()
	{
		if( $this->is_downloaded() ) return true;
        if( $this->remoteFileExists() )
        {
            if( $fp = fopen( $this->local_yml_file, 'w') )
            {
                $ch = curl_init( $this->remote_yml_url );
                curl_setopt( $ch, CURLOPT_FILE, $fp );
                $data = curl_exec($ch);
                curl_close($ch);
                fclose($fp);
                return $this->_downloaded = true;
            }
        }
		return false;
 	}

 	public function is_downloaded()
 	{
		return $this->_downloaded && file_exists( $this->local_yml_file );
 	}

	/**
	 * Блок парсинга каталога в текущем YML файле, берём дату изменения каталога.
	 */
	public function parse_catalog()
	{
		$this->catalog_date = $this->reader->getAttribute('date');
	}

	private function end_element( $el )
	{
		return ( $this->reader->nodeType === XMLREADER::END_ELEMENT ) && ( strtolower( $this->reader->localName ) === strtolower( $el ) );
	}

	private function start_element( $el )
	{
		return ( $this->reader->nodeType === XMLREADER::ELEMENT ) && ( strtolower( $this->reader->localName ) === strtolower( $el ) );
	}

	private function check_element( $el )
	{
		if( $this->reader->nodeType === XMLREADER::ELEMENT )
			return in_array( strtolower( $this->reader->localName ), $el );
		else
			return false;
	}

	private function log( $message )
	{
		//echo $message . "\r\n";
	}

	/**
	 * Блок парсинга магазина в текущем YML файле
	 */
	public function parse_shop()
	{
		$this->log( "parse_shop() : START" );
		while( $this->next_node() )
		{
			if( $this->end_element( "shop" ) )
			{
				$this->log( "parse_shop() : END" );
				return;
			}

			if( $this->check_element( $this->shop_bindings ) )
			{
				$tn = $this->reader->localName;
				$this->reader->read();
				$this->log( "parse_shop() : " . $tn . " - " . $this->reader->value );
			}
		}
	}

	/**
	 * Блок парсинга категорий в текущем YML файле
	 */
	public function parse_categories()
	{
		$this->log( "parse_categories() : START" );
		while( $this->next_node() )
		{
			if( $this->end_element( "categories" ) )
			{
				$this->log( "parse_categories() : END" );
				return;
			}

			if( $this->check_element( $this->category_bindings ) )
			{
				$tn = $this->reader->localName;
				$cid = $this->reader->getAttribute("id");
				$cpid = $this->reader->getAttribute("parentId");
				$this->reader->read();
			}
		}
		$this->log( "parse_categories() : STRANGE" );
	}

	/**
	 * простейший способ замены true / false на 1 / 0
	 */
	function check_tf( $v )
	{
		if( key_exists( $v, $this->tfv ) )
			return $this->tfv[$v];
		else
			return $v;
	}

	/**
	 * Блок парсинга предложений в текущем YML файле
	 */
	public function parse_offers()
	{
		$cnt = 0;
		$modified = 0;
		$fields_modified = false;
		$this->log( "parse_offers() : START" );

		$current_offer	= NULL;
		while( $this->next_node() )
		{
			if( $this->end_element( "offers" ) )
			{
				$this->log( "parse_offers() : END" );
				return;
			}

			if( $this->start_element( "offer" ) )
			{
				$co = array();
				$this->log( "NAME[ offer_id ] = " . $this->reader->getAttribute("id") ); 
				$co[ 'offer_id' ]		= $this->reader->getAttribute("id");
				$this->log( "NAME[ campaign_id ] = " . $this->_campaignId ); 
				$co[ 'campaign_id' ]	= $this->_campaignId; 
				continue;
			}
			else
			if( $this->end_element( "offer" ) )
			{
				$ta = array();
				foreach( $this->_offer_fields_index as $k => $v )
                {
                    if( isset( $co[ $k ] ) )
                    {
                    	$v = trim( $co[ $k ] );
                    	if( $k != "offer_picture" )
                    	{
                    		$v = preg_replace( '/\s+/', ' ', $v );
                    		if( mb_strlen( $v, "UTF-8" ) > 64 ) $v = mb_substr( $v, 0, 64, "UTF-8" ); 
                    	}
                        $ta[] = $v;
                    }
                    else
                        $ta[] = '';
                }
				if( $this->useDatabase() )
				{
					$this->_bulkInsertProcessor->addValues( "yml_offers", $ta );
				}
				continue;
			}

			if( $this->check_element( $this->offer_bindings ) )
			{
				$tn = $this->reader->localName;
				$this->reader->read();
				$this->log( "NAME[ {$tn} ] = " . $this->check_tf( $this->reader->value ) ); 
				$co[ 'offer_'.$tn ] = $this->check_tf( $this->reader->value );
				//echo "{$tn} - {$this->reader->nodeType} - {$this->reader->value}" . "\r\n";
			}
		}
	}

	public function parse_currencies()
	{
		$this->log( "parse_currencies() : START" );
		while( $this->next_node() )
		{
			if( $this->end_element( "currencies" ) )
			{
				$this->log( "parse_currencies() : END" );
				return;
			}

			if( $this->check_element( $this->currency_bindings ) )
			{
				$tn = $this->reader->localName;
				$id = $this->reader->getAttribute("id");
				$rate = $this->reader->getAttribute("rate");
				$this->log( "parse_currencies() : " . $tn . " - " . $this->reader->value );
			}
		}
	}

	public function next_node()
	{
		while( $this->reader->read() )
		{
			switch( $this->reader->nodeType )
			{
				case XMLREADER::ELEMENT:
					switch( strtolower( $this->reader->localName ) )
					{
						case "yml_catalog":
							$this->parse_catalog();
							break;
						case "shop":
							$this->parse_shop();
							break;
						case "categories":
							$this->parse_categories();
							break;
						case "currencies":
							$this->parse_currencies();
							break;
						case "offers":
							$this->parse_offers();
							break;
						default:
							return true;
					}
					break;
				case XMLREADER::END_ELEMENT:
					return true;
			}
		}
	}

	public function parse()
	{
		gc_enable();
		if( $this->local_yml_file !== null )
		{
			if( $this->download() )
			{
				/**
				 * Парсинг происходит тут :)
				 */
				$this->reader = new XMLReader();
				$this->reader->open( $this->local_yml_file );
				$this->next_node();
				if( $this->useDatabase() )
				{
					$this->_bulkInsertProcessor->flush();
				}
			}
			else {
				throw new Exception( "Невозможно загрузить данные YML." );
			}
		}
		else
			throw new Exception("Не указан путь к загруженному файлу.", 1);
	}
}
?>
