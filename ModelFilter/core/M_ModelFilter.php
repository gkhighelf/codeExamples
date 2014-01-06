<?php
/**
* @package AskMarket
* @author Konstantin Gritsenko
* @version 0.2.0
*/
class M_ModelFilter
{
    /**
    * Данные фильтрации/группировок
    * 
    * @var array
    */
	private $_filters_data = null;
    /**
    * Данные исключений фильтраций/группировок
    * 
    * @var array
    */
	private $_filters_exclude = null;
    /**
    * Шаблон формы для рендеринга по умолчанию, задаётся в детях в это переменной
    * 
    * @var string
    */
	protected $_template = 'mfilters/default';
    /**
    * Constructor
    * 
    * @param array $filter_input Массив входных фильтров/группировок
    * @param array $filters_exclude Массив исключений фильтров/группировок
    * @return M_ModelFilter
    */
	function __construct( $filter_input = array(), $filters_exclude = array() ) {
		$this->_filters_data = $filter_input;
		$this->_filters_exclude = $filters_exclude;
	}
    /**
    * Функция заглушка.
    * Возвращаем массив соответствий названий и значений по умолчанию
    * @example
    *   return array(
    *       'field1' => 'default_value_1',
    *       'field2' => 'default_value_2',
    *       ...
    *       'fieldN' => 'default_value_N',
    *   )
    * @return array
    */
	protected function getDefaults() {
		return array();
	}
    /**
    * Возвращаем название шаблона
    * 
    * @return string
    */
	private function getTemplate() {
		return $this->_template;
	}
    /**
    * Возвращаем обработаный шаблон формы настроек фильтров/группировок.
    * 
    * @param mixed $controller
    * @return string
    */
	public function render( $controller ) {
		$_data = array_merge( $this->getData(), array(
			'columns'	=> $this->getDisplayColumns()
		) );
		return $controller->load->view( $this->getTemplate(), $_data, TRUE );
	}
    /**
    * заглушка для дочерних класов.
    */
	protected function getDisplayColumns() {
		return array();
	}
	/**
	* Возвращаем массив соответствий имени поля группмровки к полю в таблице
	* 
	* @return array
	*/
	protected function getGroupAlias() {
		return array();
	}
	/**
	* Возвращаем массив соответствий имени поля группмровки к данным описывающим группировку
	* поля для выборки, join
	* 
	* @example
	* return array(
	* 	'somefield' => array(
	* 		'select' => 'table_alias.username',
	* 		'join' => array( 'atble1 table_alias', 'table_alias.id = some_id', 'JOIN' )
	* 	)
	* )
	* 
	* @return array
	*/
	protected function getGroupData() {
		return array();
	}
    /**
    * Формируем массив дополнительных параметров которые передаём в обработчик рендера формы.
    * заглушка для дочерних класов.
    */
	protected function getData() {
		return array();
	}
    /**
    * заглушка для дочерних класов.
    */
	protected function getFilterBindings() {
		return array();
	}
    /**
    * Возвращаем статус группировки, фильтрации по им ени поля.
    * 
    * @param string $name Имя индекса настроек фильтрации/группировки
    * @return boolean
    */
	protected function getDisplay( $name ) {
		$filter = $this->getFilter();
		if( isset( $filter['display'] ) ) {
			if( isset( $filter['display'][ $name ] ) && $filter['display'][ $name ] == 1 ) {
                return true;
            }
		}
		return false;
	}
    /**
    * Берём значение фильтра по имени, делаем все необходимые проверки и конвертации, если нужно
    * 
    * @param string $name
    * @return mixed|false
    */
	protected function getFilterData( $name ) {
		$filter = $this->getFilter();
		if( isset( $filter[$name] ) && !empty( $filter[$name] ) && $filter[$name] !== 'all' ) {
			if( is_array( $filter[$name] ) && count( $filter[$name] ) == 1 && $filter[$name][0] == 0 ) {
				return false;
			}
			return $filter[$name];
		}
        return false;
	}
	private $_filterStorage = null;
    /**
    * Возвращаем сформированные фильтры
    * @return array
    */
	function getFilter() {
		if( $this->_filterStorage === null ) {
            $this->_filterStorage = $this->form_build_filterset( $this->_filters_data ); 
        }
		return $this->_filterStorage;
	}
    /**
    * Возвращаем массив условий фильтрации.
    */
	protected function getConditions() {
		return array();
	}
	/**
    * Формируем данные фильтрации, обрабатываем поля только указанные как значения по умолчанию.
    * Добавлен функционал исключающего списка, используется в случае наследования готового фильтра,
    * и когда в нём есть не нужные нам параметры, мы их просто исключаем.
	*
	* @param array $scheme - filter structure containing default values
	* @param mixed $input - input data
	* @return array
	*/
	function form_build_filterset( $input ) {
		$out = array();
		if( $input === false ) {
			$input = array();
		}
		foreach( $this->getDefaults() as $key => $default ) {
			if( !in_array( $key, $this->_filters_exclude ) ) {
				$out[$key] = isset( $input[$key] ) ? $input[$key] : $default;
			}
		}
		return $out;
	}
    /**
    * Обработка полей фильтров.
    * Обрабатываем полученные данные через GET или POST запросы.
    * Формируем условия фильтрации по полученым данным.
    */
	private function applyFilterBindings() {
		$key2field = $this->getFilterBindings(); 
		foreach( $key2field as $key => $field ) {
			if( $fdata = $this->getFilterData( $key ) ) {
				if( is_array( $fdata ) ) {
                    $this->where_in( $field, $fdata );
                } else if( is_numeric( $fdata ) ) {
                    $this->where( $field, $fdata );
                } else if( is_string( $fdata ) ) {
                    $this->like( $field, $fdata );
                }
			}
		}
	}
    /**
    * На основании данных фильтра, обрабатываем поступившую информацию,
    * добавляем дополнительные данные ( группировки, фильтрации ), в запрос
    * 
    * Алгоритм следующий : перебираем все поля по которым возможна группировка или фильтрация.
    * Если флаг вывода этого поля получен, берём настройки этого поля, формируем запрос на основании настроек.
    * Индекс массива соответствует функции базового класа из следующего списка
    * select, from, join, like, where_in, where, order, group
    */
	private function applyFilterGrouping() {
		$_aliases = $this->getGroupAlias();
		$_join_data = $this->getGroupData();
		foreach( $this->getDisplayColumns() as $field => $v ) {
			if( $this->getDisplay( $field ) ) {
				if( array_key_exists( $field, $_join_data ) ) {
					/**
					* По заданным алиасам группировок добавляем в запрос JOINы если данные присутствуют.
					*/
					foreach( $_join_data[ $field ] as $key => $value ) {
						if( ! is_array( $value ) ) {
							$value = array( $value );
						}
						if( method_exists( $this, $key ) ) {
							call_user_func_array( array( $this, $key ), $value );
						}
					}
				}
				/**
				* По заданным алиасам группировок добавляем в запрос группировки по нужным полям.
				*/
				if( array_key_exists( $field, $_aliases ) ) {
					$field = $_aliases[ $field ]; 
				}
				$this->group( $field );
			}
		}
	}
	private $_conditions = array();
	/**
	* @param string|array $v
	* @return M_ModelFilter
	*/
	function select( $v ) {
		if( is_array( $v ) ) {
			foreach( $v as $f ) {
				$this->_conditions[ 'select' ][] = $f;
			}
		} else {
			$this->_conditions[ 'select' ][] = $v;
		}
		return $this;
	}
	/**
	* @param string $table
	* @return M_ModelFilter
	*/
	function from( $table ) {
		$this->_conditions[ 'from' ][] = $table;
		return $this;
	}
	/**
	* @param string $table
	* @param string $condition
	* @param string $type
	* @return M_ModelFilter
	*/
	function join( $table, $condition, $type = 'LEFT' ) {
		$this->_conditions[ 'join' ][] = array(
			'table'		=> $table,
			'condition'	=> $condition,
			'type'		=> $type
		);
		return $this;
	}
	/**
	* @param string $field
	* @param string $val
	* @return M_ModelFilter
	*/
	function like( $field, $val ) {
		$this->_conditions['like'][ $field ] = $val;
		return $this;
	}
	/**
	* @param string $field
	* @param string|array $data
	* @return M_ModelFilter
	*/
	function where_in( $field, $data ) {
		if( is_array( $data ) ) $data = "(".implode( ',', $category ).")";
		$this->_conditions['where'][ $field ] = array( 'IN', $data );
		return $this;
	}
	/**
	* @param string $where
	* @param mixed $val
	* @return M_ModelFilter
	*/
	function where( $where, $val = null ) {
		if( is_array( $where ) ) {
			foreach( $where as $k => $v ) {
				$this->_conditions['where'][$k] = $v;
			}
		}
		if( !empty( $val ) ) {
			$this->_conditions['where'][ $where ] = $val;
		}
		return $this;
	}
	/**
	* @param string $field
	* @param string $type
	* @return M_ModelFilter
	*/
	function order( $field, $type = 'ASC' ) {
		$this->_conditions[ 'order' ][] = array( $field, $type );
		return $this;
	}
	/**
	* @param string $field
	* @return M_ModelFilter
	*/
	function group( $field ) {
		if( !isset( $this->_conditions[ 'group' ] ) ) $this->_conditions[ 'group' ] = array();
		if( !in_array( $field, $this->_conditions[ 'group' ] ) ) {
			$this->_conditions[ 'group' ][] = $field;
		}
		return $this;
	}
    /**
    * @return array
    */
	function _buildFilters() {
		$this->applyFilterBindings();
		$this->applyFilterGrouping();
		return $this->_conditions;
	}
}
?>
