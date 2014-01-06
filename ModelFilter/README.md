codeExamples
============

code examples

Конструктор запросов / фильтров для удобного создания отчётов.

Формирует форму ввода фильтров по заданным значениям.
Формирует вывод данных на основании заданного шаблона для вывода данных.

Выдержка из описания

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
