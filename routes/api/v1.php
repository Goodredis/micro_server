<?php
/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| Here is where you can register all of the routes for an application.
| It is a breeze. Simply tell Lumen the URIs it should respond to
| and give it the Closure to call when that URI is requested.
|
 */
$api = app('Dingo\Api\Routing\Router');
// v1 version API
// add in header    Accept:application/vnd.lumen.v1+json
$api->version('v1', [
    'namespace' => 'App\Http\Controllers\Api\V1',
    // 'middleware' => [
    //     'cors',
    //     'serializer',
    //     'api.throttle',
    // ],
    // each route have a limit of 20 of 1 minutes
    'limit' => 20, 'expires' => 1,
], function ($api) {
    // 测试的增删改查
    $api->resources([ 'test' => 'TestController' ]);
    // 组织的增删改查
    $api->resources([ 'orgs' => 'OrgController' ]);
    // 用户的增删改查
    $api->resources([ 'users' => 'UserController' ]);

    // 登录认证，创建token 
    $api->post('authorizations', 'AuthController@store');
    // 用户登出，销毁token 
    $api->delete('authorizations/current', 'AuthController@delete');
    // 刷新token 
    $api->put('authorizations/current', 'AuthController@update');

    // 批量 操作
    $api->post('calendar/batch', 'CalendarController@batch');
    $api->resources(['calendar' => 'CalendarController']);
    // 调用api生成日历
    $api->post('calendar/createapi', 'CalendarController@createapi');

});
