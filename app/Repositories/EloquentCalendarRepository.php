<?php
/**
 * Created by PhpStorm.
 * User: w17600101602
 * Date: 2019/9/23
 * Time: 14:32
 */

namespace App\Repositories;

use App\Repositories\Contracts\CalendarRepository;
use App\Utils\Facade\Lunar;
use Illuminate\Support\Facades\Schema;

class EloquentCalendarRepository extends AbstractEloquentRepository implements CalendarRepository
{
    public function findBy(array $searchCriteria = [], array $operatorCriteria = [])
    {
        //获取表中所有字段
        $tableColumns = Schema::getColumnListing($this->model->getTable());
        $columns = ['*'];
        if(!empty($searchCriteria['columns'])) {
            $columns = explode(',', $searchCriteria['columns']);
            foreach ($columns as $key => $value) {
                if($value != '*' && !in_array($value, $tableColumns)){
                    unset($columns[$key]);
                }
            }
        }

        $orderby = '';
        if(!empty($searchCriteria['orderby'])) {
            $orderby = trim($searchCriteria['orderby']);
        }

        //获取搜索条件中的字段,过滤到表中不存在的字段
        $searchCriteriaColumns = array_keys($searchCriteria);
        $diffColumns = array_diff($searchCriteriaColumns, $tableColumns);
        if(!empty($diffColumns)){
            foreach ($diffColumns as $key => $value) {
                unset($searchCriteria[$value]);
            }
        }

        $queryBuilder = $this->model->where(function ($query) use ($searchCriteria, $operatorCriteria) {

            $this->applySearchCriteriaInQueryBuilder($query, $searchCriteria, $operatorCriteria);
        });

        $queryBuilder = $this->applyOrderCriteriaInQueryBuilder($queryBuilder, $orderby);

        return $queryBuilder->get($columns);
    }

    /**
     * @brief 批量操作更新数据
     *
     * @param end start (time), comment, type
     * @retval array
     */
    public function batchUpdate($data)
    {
        $endTime        = $data['end'];
        $startTime      = $data['start'];
        $type           = $data['type'];
        $comment        = $data['comment'];
        $this->model->whereBetween('time', [$startTime, $endTime])->update(['type' => $type, 'comment' => $comment]);
        $calendars = $this->model->whereBetween('time', [$startTime, $endTime])->get();
        return $calendars;
    }

    /**
     * @brief 调用api更新假期、调休数据
     *
     * @param end start (time), comment, type
     * @retval array
     */
    public function adjustCalendar($data)
    {
        // 先把国家假期全部清空
        $this->model->where([['type', '=', 'festival'], ['day', '<>', 6], ['day', '<>', 7]])->update(['type' => 'workday']);
        foreach ($data['holiday'] as $key => $val) {
            $time = strtotime($val['date']);
            if (mb_substr($val['name'], -2, 2, 'UTF-8') == '调休') {
                $type = 'specialDay';
                $comment = $val['name'];
            } else {
                $type = 'festival';
                $comment='';
            }
            $this->model->where('time', '=', $time)->update(['type' => $type, 'comment' => $comment]);
        }

        return $this->getObjectCalendar('public', $data['year']);
    }

    /**
     * @brief 初始化一年的日期到数据库
     *
     * @param $year int 年份1980到2038
     * @retval array
     */
    public function initOneYearDate ($year)
    {
        if($year < 1980 or $year > 2038)
            return array('err_code'=>'20014' , 'err_memo'=>'能处理的年份在1980年到2038年之间');

        //logs('初始化'.$year.'年日历 start');
        set_time_limit(300);
        //logs($year.'年已初始天数$rows = '.$rows);
        $rows = $this->model->where([['year', '=', $year], ['obj_id', '=', 'public']])->count();//查询已经初始化的条数
        //logs($year.'年实际天数$year_rows = '.$year_rows);
        $year_rows = $this->isRunNian($year) ? 366 : 365;
        if($rows != $year_rows)
        {
            //如果公用日历天数不等于该年的全天数，先删除
            $this->model->where([['year', '=', $year], ['obj_id', '=', 'public']])->delete();
            //在初始化
            $date          = $this -> initYearDate($year);
            $holiday_list  = config('calendar.getHolidayList');
            $solar_holiday = array_keys($holiday_list['solar']);
            $lunar_holiday = array_keys($holiday_list['lunar']);
            $lunar_other   = array_keys($holiday_list['other']);
            foreach ($date as $key => $val)
            {
                ($val['m'] < 10) && ($val['m'] = '0'.$val['m']);
                ($val['d'] < 10) && ($val['d'] = '0'.$val['d']);
                $date = $val['m'].$val['d'];

                //判断假日
                $lunar_date = Lunar::convertSolarToLunar($val['y'] , $val['m'] , $val['d']);//查出农历
                ($lunar_date['4'] < 10) && ($lunar_date['4'] = '0'.$lunar_date['4']);
                ($lunar_date['5'] < 10) && ($lunar_date['5'] = '0'.$lunar_date['5']);
                $_lunar_date = $lunar_date['4'].$lunar_date['5'];//农历
                $type = 'workday';
                $info['solar_text']  = '';//阳历文本
                $info['lunar_text']  = '';//阴历文本
                $info['other_text']  = '';//其它节日文本

                //周六日
                if(in_array($val['w'] , array(6 , 7)))
                {
                    $type    = 'holiday';
                }

                //阳历
                if(in_array($date , $solar_holiday))
                {
                    $type               = 'festival';
                    $info['solar_text'] = $holiday_list['solar'][$date];
                }

                //农历
                if(in_array($_lunar_date , $lunar_holiday))
                {
                    $type               = 'festival';
                    $info['lunar_text'] = $holiday_list['lunar'][$_lunar_date];
                }

                //其它节日
                if(in_array($date , $lunar_other))
                {
                    $info['other_text'] = $holiday_list['other'][$date];
                }

                $info['obj_id']   = 'public';
                $info['type']     = $type;
                $info['year']     = intval($year);
                $info['month']    = intval($val['m']);
                $info['day']      = intval($val['d']);
                $info['lunar']    = $lunar_date;
                $info['time']     = strtotime($year.'-'.$val['m'].'-'.$val['d']);
                $info['week']     = intval($val['w']);
                $info['comment']  = '';

                $this->model->create($info);
            }

            return $this->getObjectCalendar('public', $year);
        }

        return array('err_code'=>'20013' , 'err_memo'=>$year.'年时间已经初始化过');
    }

    /**
     * @brief 获取object日历
     *
     * @param $object_id string
     * @param $year      string
     * @param $field     string
     * @retval array
     */
    public function getObjectCalendar($obj_id='public' , $year=null , $field=[])
    {
        //如果是取公共日历，直接返回结果
        if($obj_id == 'public')
            return $this -> findBy(array('obj_id' => $obj_id , 'year'=>$year , 'columns' => $field, 'orderby' => 'time asc'));

        //取对象日历
        $list = $this -> findBy(array('obj_id' => $obj_id , 'year'=>$year , 'columns' => $field, 'orderby' => 'time asc'));

        //如果取的是某个对象的日历，则需要这个对象的日历去合并public日历后返回
        $pub_list = $this -> findBy(array('obj_id' => 'public' , 'year'=>$year , 'columns' => $field, 'orderby' => 'time asc'));

        //用对象的假期去合并覆盖公共假期
        return array_merge($pub_list , $list);
    }

    /**
     * @brief 判断是否为闰年
     *
     * @param $year int 年份1980到2038
     * @retval bool
     */
    public function isRunNian($year)
    {
        //1、  如果年份是4的倍数，且不是100的倍数，则是闰年；
        //2、  如果年份是400的倍数，则是闰年；
        //3、  不满足1、2条件的就是平常年。
        $four_mod         = $year % 4;
        $hundred_mod      = $year % 100;
        $four_hundred_mod = $year % 400;
        if((($four_mod == 0) && ($hundred_mod > 0)) || ($four_hundred_mod == 0))
            return true;
        return false;
    }

    //初始化一年的日历
    private function initYearDate($year)
    {
        $dates = array();
        for ($i=1;$i<=12;$i++)
        {
            $month_dates = $this -> getMonthDates($year , $i);
            for ($j=1;$j<=$month_dates;$j++)
            {
                $dates[] = array(
                    'y'=>$year ,
                    'm'=>$i ,
                    'd'=>$j ,
                    'w'=>$this -> zellerWeek($year,$i,$j)
                );
            }
        }
        return $dates;
    }

    /**
     * @brief 获取某年的某个月有多少天
     *
     * @param $year   int
     * @param $month  int
     * @retval int
     */
    public function getMonthDates ($year,$month)
    {
        if(in_array($month , config('calendar.month_31day')))
            return 31;

        if(in_array($month , config('calendar.month_30day')))
            return 30;

        if($this -> isRunNian($year))
            return 29;
        else
            return 28;
    }

    /**
     * @brief 根据德国数学家克里斯蒂安·蔡勒（Christian Zeller, 1822- 1899）在1886年推导出了著名的为蔡勒（Zeller）公式推算任何一天是星期几
     *
     * @param $year  int
     * @param $month int
     * @param $date  int
     * @retval int
     */
    function zellerWeek ($year , $month , $date)
    {
        $year  = intval($year);
        $month = intval($month);
        $date  = intval($date);

        if($month <= 2)
        {
            $month = $month + 12;
            $year  = $year - 1;
        }

        $y = $year % 100;
        $c = floor($year / 100);
        $m = $month;
        $d = $date;
        $w = ($y + floor($y/4) + floor($c/4) - 2*$c + floor((26*($m+1))/10) + $d - 1);
        $day = ($w >= 0) ? ($w % 7) : (( $w % 7 + 7 ) % 7);
        ($day == 0) && ($day = 7);
        return $day;
    }
}