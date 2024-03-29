<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Org extends Model
{
    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'orgs';

    /**
     * Storage format of date field
     *
     * @var string
     */
    protected $dateFormat = 'U';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'code',
        'name',
        'ldap_id',
        'order',
        'status',
        'flag',
    ];

    /**
     * 设置时间格式
     */
    protected $casts = [
        'created_at' => 'timestamps',
        'updated_at' => 'timestamps'
    ];

    /**
     * set default value of column
     *
     * @var array
     */
    protected $attributes = [
        'status'   => 0,
    ];
}
