<?php
/**
 * Created by PhpStorm.
 * User: w17600101602
 * Date: 2019/9/23
 * Time: 14:36
 */

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Calendar extends Model
{
    // 相关的数据表
    protected $table = 'calendar';
    /**
     * 设置时间格式
     */
    protected $casts = [
        'created_at' => 'timestamps',
        'updated_at' => 'timestamps',
        'lunar'      => 'array'
    ];

    /**
     * 模型的日期字段保存格式，时间戳
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * 定义不可操作的字段
     *
     * @var array
     */
    protected $fillable   = [
        'id',
        'obj_id',
        'type',
        'year',
        'month',
        'day',
        'lunar',
        'week'
    ];

    /**
     * 字段默认值
     *
     * @var array
     */
    protected $attributes = [
        'obj_id'   => 'public',
    ];
}