<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Test extends Model
{

    /*
        表里没有created_at,和updated_at字段时，此属性要设为false,否则报错
    */
    public $timestamps = false;

    /**
     * The database table used by the model.
     *
     * @var string
     */
    protected $table = 'test';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'name',
        'tag'
    ];
}
