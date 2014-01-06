<?php
/**
* @package AskMarket
* @author Konstantin Gritsenko
* @version 0.1.0
*/
class ReportingDailyAdministrator extends ReportingDaily
{
	function getDefaults()
	{
		return array_merge( parent::getDefaults(), array(
			'category'   => array(0),
			'advertiser' => '',
			'campaign'   => '',
			'webmaster'  => '',
			'block'      => '',
			'site'       => '',
			'category'   => '',
		) );
	}

	function getDisplayColumns()
	{
		return array_merge( parent::getDisplayColumns(), array( 'advertiser' => "Рекламодатель", 'campaign' => "Кампания", 'promo' => "Объявление", 'webmaster' => "Вебмастер", 'block' => "Рекл. блок", 'site' => "Сайт", 'category' => "Категория" ) );
	}

	protected function getFilterBindings()
	{
		return array(
			'advertiser'	=> "adv.id",
			'campaign'		=> "c.id",
			'promo'			=> "p.id",
			'webmaster'		=> "wm.id",
			'site'			=> "s.url",
			'block'			=> "b.name",
			'category'		=> "r.category_id",
		);
	}

	function getConditions()
	{
		parent::getConditions();

		$this->_conditions[ 'select' ][]=array();

		$fields = array();
		foreach( explode( ',', 'hits_raw,hits_uniq,clicks_raw,clicks_uniq,revenue' ) as $key )
		{
			$fields[] = "SUM( srd.$key ) AS $key";
		}
		$fields[] = "SUM( IF( s.own = 1, 0, srd.cost ) ) AS cost";
		$fields[] = "SUM( IF( s.own = 1, srd.revenue, srd.profit ) ) AS profit";

		$this->select( $fields )
			->select('wm.id AS webmaster_id, wm.username AS webmaster')
			->select('adv.id AS advertiser_id, adv.username AS advertiser')
			->select('c.id AS campaign_id, c.name AS campaign')
			->select('s.id AS site_id, s.url AS site, s.own AS own_site')
			->select('p.id AS promo_id, p.id AS promo')
			->select('b.id AS block_id, b.name AS block')
			->select('cat.id AS category_id, cat.value AS category')
			->join('sys_block b',    "b.id=srd.block_id",    'LEFT')
			->join('sys_site s',     "s.id=b.site_id",     'LEFT')
			->join('sys_user wm',    "wm.id=s.user_id",    'LEFT')
			->join('sys_promo p',    "p.id=srd.promo_id",    'LEFT')
			->join('sys_campaign c', "c.id=p.campaign_id", 'LEFT')
			->join('sys_user adv',   "adv.id=c.user_id",   'LEFT')
			->join('sys_category cat', "cat.id=srd.category_id AND cat.taxonomy='category'", 'LEFT');

		return $this->_buildFilters();
	}
}
?>
