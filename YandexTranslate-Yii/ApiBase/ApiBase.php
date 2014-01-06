<?php
/**
 * API Base class
 *
 * @author Konstantin Gritsenko <gkhighelf@gmail.com>
 * @version 1.0
 */
class ApiBase {
    const RESULT_SUCCESS = "200";

    /** @var string */
    protected $_api_version = "0";

    /** @var string */
    protected $_api_key;

    /** @var string */
    protected $_api_key_param_name = "key";

    /** @var int */
    protected $_retryCount = 0;

    /** @var int */
    protected $_timeout;

    /** @var string */
    protected $_method = "POST";

    /**
    * put your comment there...
    * 
    * @var mixed
    */
    protected $_api_last_error_code = self::RESULT_SUCCESS;

    /**
    * @return string Server url where API is located
    */
    protected function getBaseAPIUrl() {
        return "http://dummy.com";
    }

    /**
    * @return string Api data result format
    */
    protected function getResultDataFormat() {
        return "tr.json";
    }

    /**
    * Building server api url
    * 
    * @example http://dummy.com/api/{$api_version}/${api_data_format}
    * @return string
    */
    protected function getApiHost() {
        return $this->getBaseAPIUrl() . "/{$this->_api_version}/".$this->getResultDataFormat();
    }

    /**
    * Constructor
    * 
    * @param string $apiKey
    * @param string $encoding
    * @param int $retryCount
    * @param int $timeout
    * @return ApiBase
    */
    function __construct( $apiKey, $apiKeyParamName = "key", $encoding = 'UTF8', $retryCount = 4, $timeout = null ) {
        $this->_api_key = $apiKey;
        $this->_api_key_param_name = $apiKeyParamName;

        if (!empty($encoding)) {
            $this->_encoding = $encoding;
        }

        if (!empty($retryCount)) {
            $this->_retryCount = $retryCount;
        }

        if (!empty($timeout)) {
            $this->_timeout = $timeout;
        }
    }

    /**
    * Parsing results after api called
    * 
    * @param mixed $res
    * @return array
    */
    protected function parseResult( $data )
    {
        $res = json_decode( $data );
        $this->_api_last_error_code = $res->code;
        return $res->text[0];
    }

    /**
     * @param string $Name
     * @param array $Arguments
     * @return string
     */
    function __call( $fn, $args ) {
        if( isset( $args[0] ) )
        {
            $args = $args[0];
        }
        return $this->parseResult( $this->callMethod( $fn, $args ) );
    }

    /**
     * @param string $MethodName
     * @param array $Params
     * @return array
     */
    protected function callMethod( $fn, $args = array() ) {
        $args = array_merge( (array)$args, array( $this->_api_key_param_name => $this->_api_key ));

        $contextOptions = array(
            'http' => array(
                'method'  => 'POST',
                'header'  => 'Content-type: application/x-www-form-urlencoded',
                'content' => http_build_query( $args ),
            )
        );

        if ( $this->_timeout ) {
            $contextOptions['http']['timeout'] = $this->Timeout;
        }

        $retries = 0;
        $context = stream_context_create( $contextOptions );
        do {
            $host = $this->getApiHost() . "/" . $fn;
            $res = file_get_contents( $host, FALSE, $context );
            $retries++;
        } while ( $res === false && $retries < $this->_retryCount );

        return $res;
    }
}
?>
