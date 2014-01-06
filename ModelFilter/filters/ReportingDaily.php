<?php
/**
* @package AskMarket
* @author Konstantin Gritsenko
* @version 0.1.0
*/
class ReportingDaily extends M_ModelFilter
{
	function getDefaults()
	{
		return array_merge( parent::getDefaults(), array(
			'df'         => date( config_item('date_format'), strtotime('-7 days') ),
			'dt'         => date( config_item('date_format') ),
			'display'    => array('date'=>1),
		) );
	}

	function getDisplayColumns()
	{
		return array( 'date'=>"Дата" );
	}

	function getConditions()
	{
		$filter = $this->getFilter();
		$fields = array( "srd.date" );
		foreach( explode( ',', 'hits_raw,hits_uniq,clicks_raw,clicks_uniq,revenue,cost,profit' ) as $key )
			$fields[] = "SUM( srd.$key ) AS $key";

		$this->select( $fields )
			->from( 'sys_reporting_daily srd' )
			->where( array( 'srd.date >=' => "'".date_local_2mysql($filter['df'])."'", 'srd.date <=' => "'".date_local_2mysql($filter['dt'])."'" ) )
			->order( 'srd.date', 'DESC' );
		return $this->_buildFilters();
	}
}
?>
