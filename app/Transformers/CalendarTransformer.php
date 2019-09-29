<?php
/**
 * Created by PhpStorm.
 * User: w17600101602
 * Date: 2019/9/23
 * Time: 14:34
 */

namespace App\Transformers;

use App\Models\Calendar;
use League\Fractal\TransformerAbstract;

class CalendarTransformer extends TransformerAbstract
{
    public function transform(Calendar $calendar) {
        $calendarInfo = [
            'id'             => $calendar->id,
            'obj_id'         => $calendar->obj_id,
            'type'           => $calendar->type,
            'year'           => $calendar->year,
            'month'          => $calendar->month,
            'day'            => $calendar->day,
            'lunar'          => $calendar->lunar,
            'week'           => $calendar->week,
            'solar_text'     => $calendar->solar_text,
            'lunar_text'     => $calendar->lunar_text,
            'other_text'     => $calendar->other_text,
            'created_at'     => $calendar->created_at,
            'updated_at'     => $calendar->updated_at,
        ];

        return $calendarInfo;
    }


}