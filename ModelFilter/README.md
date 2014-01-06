codeExamples
============

code examples

����������� �������� / �������� ��� �������� �������� �������.

��������� ����� ����� �������� �� �������� ���������.
��������� ����� ������ �� ��������� ��������� ������� ��� ������ ������.

�������� �� ��������

/**
* ������� ������ � �������� M_ModelFilter
* 
* �� ������ ������������ class M_ModelFilter ��� �������� �������� ���������� ���������� ��������
* ��� ���������� ������ �������� � ��������� ����.
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
* ������������ ����������� � ������������� ����� ����� �������� ������ ������������� ������� ���������� ������ � �������.
* 
* @example
* $filterLibrary->ReportingDaily_Form() - ����� ����������� HTML ������ ����� ���������, ��� ����������� ������������.
* $filterLibrary->ReportingDaily_Query() - ����� ������� ������ �� ���� ��� ����������, �� ��������� ������� ����� �����.
* $filterLibrary->ReportingDaily_Filter() - ����� ������ ��������/����������� ����� �����.
* 
* �����
*   $filterLibrary->ReportingDaily( $params ) - ����� �������� �������������� ��������� �������. �������� �������� �������� ������� � ����� ���� ����������.
* @example
*   ����� ������� �������� ������������ ���� AdvertiserFilter �� ����� ����������� ��� ������������ ��������
*   ��� ��������� ��� ���������� �� ������� ���� �������� ������� ��� ��� ����� ������� � ����� Advertisers
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
