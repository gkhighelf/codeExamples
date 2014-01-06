<?php
require_once(FCPATH.'/'.APPPATH.'/core/M_ModelFilter.php');
/**
* Враппер работы с классами M_ModelFilter
* 
* Вы можете использовать class M_ModelFilter для создания иерархии параметров фильтрации запросов
* для построения гибких запросов в следующем виде.
* 
* @example
* class AdvertiserFilter extends M_ModelFilter {
*     function getConditions() {
*         $criteria = parent::getCondition();
*
*         $criteria['select'][] = "sys_user.*";
*         $criteria['join'][] = array(
*             'table'        => 'sys_campaign sc',
*             'condition'    => "sc.user_id=sys_user.id"  
*         );
*         return $criteria;
*     }
* }
* 
* Единственным неудобством в использовании этого класа является точное использование алиасов именования таблиц в запросе.
* 
* @example
* $filterLibrary->ReportingDaily_Form() - вернёт обработаный HTML вывода формы настройки, для отображения пользователю.
* $filterLibrary->ReportingDaily_Query() - вернёт выборку данных из базы БЕЗ ПАРАМЕТРОВ, на основании условий этого класа.
* $filterLibrary->ReportingDaily_Filter() - вернёт массив фильтров/группировок этого класа.
* 
* Вызов
*   $filterLibrary->ReportingDaily( $params ) - Можно передать дополнительные параметры выборки. Например указание внешнего фактора ф какой либо переменной.
* @example
*   Таким образом наследуя существующий клас AdvertiserFilter мы имеем возможность его использовать напрямую
*   или расширить его функционал до выборки всех объектов которые так или иначе связаны с этими Advertisers
    class AdvertiserFilterByWebmaster extends AdvertiserFilter
    {
        function getConditions( $webmaster_id )
        {
            $criteria = parent::getConditions();

            $criteria['join'][] = array(
                'table'        => 'sys_campaign_category scc',
                'condition'    => "scc.campaign_id = sc.id"  
            );
            $criteria['join'][] = array(
                'table'        => 'sys_site_category ssc',
                'condition'    => "ssc.category_id = scc.category_id"  
            );
            $criteria['join'][] = array(
                'table'        => 'sys_site ss',
                'condition'    => "ss.id = ssc.site_id and ss.status='active' and ss.user_id = {$webmaster_id}"  
            );
            return $criteria;
        }
    }
* 
* @package AskMarket
* @author Konstantin Gritsenko
* @version 0.1.0
*/
class ModelFilters
{
	private $_ci = null;
	private $db = null;
	private $_registered_filters = array(
        'DataChanges'                   => 'DataChanges',
        'Users'                         => 'UserFilter',
        'Managers'                      => 'ManagerFilter',
		'Advertisers'					=> 'AdvertiserFilter',
		'AdvertisersByWebmaster'		=> 'AdvertiserFilterByWebmaster',
		'CampaignsByWebmaster'			=> 'CampaignsFilterByWebmaster',
		'Promos'						=> 'PromoFilter',
		'UserPromos'					=> 'UserPromoFilter',
		'Webmasters'					=> 'WebmasterFilter',
		'WebmastersByAdvertiser'		=> 'WebmasterFilterByAdvertiser',
		'ReportingDaily'				=> 'ReportingDaily',
		'ReportingDailyAdvertiser'		=> 'ReportingDailyAdvertiser',
		'ReportingDailyAdministrator'	=> 'ReportingDailyAdministrator',
		'ManagerFeeReport'				=> 'ManagerFeeReport',
		'UserPaymentsReport'			=> 'UserPaymentsReport',
	);

	private $_instantiated_classes = null;

    /**
    * Constructor
    */
	function __construct() {
        $this->_ci =& get_instance();
        $this->db = $this->_ci->db;
		$this->_instantiated_classes = array();
		foreach( $this->_registered_filters as $k => $f ) {
			require_once(FCPATH."/".APPPATH."/classes/filters/{$f}.php");
		}
	}

    /**
    * Обработчик вызова фильтров через библиотеку враппер.
    * Получаем вызов в следующем формате
    * FilterName_SubFunction
    * 
    * SubFunction - enum( from, query, filter )
    * 
    * @param mixed $name
    * @param mixed $params
    * @return mixed|FALSE
    */
	function __call( $name, $params )
	{
		$ps = explode( '_', $name );
		$name = $ps[0];
		if( array_key_exists( $name, $this->_registered_filters ) )
		{
			$class = $this->_registered_filters[ $name ];
			if( !isset( $this->_instantiated_classes[ $name ] ) ) {
				$this->_instantiated_classes[ $name ] = new $class( $this->_ci->input->get() );
			}
			if( isset( $ps[1] ) ) {
				$subFunc = strtolower($ps[1]); 
                switch( $subFunc ) {
                    case 'form':return $this->_instantiated_classes[ $name ];
                    case 'query':return $this->_prepareDbSQL( call_user_func_array( array( $this->_instantiated_classes[ $name ], 'getConditions' ), $params ) );
                    case 'filter':return call_user_func_array( array( $this->_instantiated_classes[ $name ], 'getFilter' ), $params );
                }
			}
			return call_user_func_array( array( $this->_instantiated_classes[ $name ], 'getConditions' ), $params );
		}
		return FALSE;
	}

	function _prepareDbSQL( $condition )
	{
		if (!array_key_exists('where', $condition)) {
			$found = false;
			foreach (array('select', 'order', 'limit', 'like') as $key) {
				$found = (array_key_exists($key, $condition) && is_array($condition[$key]));
				if ($found) {
					break;
				}
			}

			if ($found == false) {
				$condition = array('where' => $condition);
			}
		}

		$select = empty($condition['select']) ? '*' : $condition['select'];
		$this->db->select($select);
		if( !empty($condition['from']) )
		{
			$this->db->from( $condition['from'] );
		}

		if (!empty($condition['where'])) {
			$where = array();
            if( is_array( $condition['where'] ) )
            {
                foreach ($condition['where'] as $key => $val) {
                    if (is_array($val)) {
                        list($operator, $value) = $val;
                        $where[$key . ' '.$operator.' '] = $value;
                    } else {
                        $where[$key] = $val;
                    }
                }
            }
            else
            {
                $where = $condition['where'];
            }
			$this->db->where( $where, null, false );
		}

		if( isset( $condition['join'] ) && is_array( $condition['join'] ) )
		{
			foreach( $condition['join'] as $join )
			{
				$this->db->join( $join['table'], $join['condition'], isset($join['type'])?$join['type']:"" );
			}
		}

		if (!empty($condition['like'])) $this->db->like($condition['like']);

		if (!empty($condition['group'])) $this->db->group_by($condition['group']);

		if (!empty($condition['order']))
		{
			if( !is_array( $condition['order'] ) ) $condition['order'] = array( $condition['order'] );
			foreach( $condition['order'] as $orderBy ) $this->db->order_by( $orderBy[0], isset($orderBy[1]) ? $orderBy[1] : 'ASC');
		}

		if (!empty($condition['limit'])) {
			@list($limit, $offset) = explode(",", $condition['limit']);
			$this->db->limit((int)$limit, (int)$offset);
		}

		return $this->db->get()->result();
	}
}
?>
