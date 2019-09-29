<?php
/**
 * Created by PhpStorm.
 * User: w17600101602
 * Date: 2019/9/23
 * Time: 14:19
 */

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Calendar;
use App\Repositories\Contracts\CalendarRepository;
use App\Transformers\CalendarTransformer;
use Illuminate\Http\Request;
use Ixudra\Curl\Facades\Curl;
use League\Fractal\Resource\Collection;

class CalendarController extends Controller
{
    private $calendarRepository;
    private $calendarTransform;

    public function __construct(CalendarRepository $calendarRepository, CalendarTransformer $calendarTransform)
    {
        $this->calendarRepository = $calendarRepository;
        $this->calendarTransform = $calendarTransform;
        parent::__construct();
    }

    // 获取年份的日历
    public function index(Request $request)
    {
        $res = $this->calendarRepository->findBy($request->all());

        return $this->respondWithCollection($res, $this->calendarTransform);
    }

    // 单年日历生成
    public function store(Request $request)
    {
        // 检查参数是否合法
        $validatorResponse = $this->validateRequest($request, $this->storeRequestValidationRules());
        // 返回参数不合法错误
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $calendar = $this->calendarRepository->initOneYearDate($request->input('year'));

        return isset($calendar['err_code']) ? $this->setStatusCode(409)->respondWithArray($calendar) : $this->setStatusCode(201)->respondWithCollection($calendar, $this->calendarTransform);
    }

    // 修改日期类型（单日）
    public function update(Request $request, $dayId)
    {
        // 检查参数是否合法
        $validatorResponse = $this->validateRequest($request, $this->updateRequestValidationRules());

        // 返回参数不合法错误
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }
        $day = $this->calendarRepository->findOne($dayId);
        if (!$day instanceof Calendar) {
            return $this->sendNotFoundResponse("The calendar with id {$dayId} doesn't exist");
        }

        $day = $this->calendarRepository->update($day, $request->all());

        return $this->respondWithItem($day, $this->calendarTransform);
    }

    // 批量 新建 年份日历 更改日期类型
    public function batch(Request $request)
    {
        // 检查参数是否合法
        $validatorResponse = $this->validateRequest($request, $this->batchRequestValidationRules());
        // 返回参数不合法错误
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }
        switch ($request->input('method')) {
            case 'create':
                foreach ($request->input('data') as $key => $val) {
                    $calendar = $this->calendarRepository->initOneYearDate($val);
                }
                break;
            case 'update':
                // 检查参数是否合法
                $validatorResponse = $this->batchUpdateValidation($request->input('data'));
                // 返回参数不合法错误
                if (isset($validatorResponse['err_code'])) {
                    return $this->setStatusCode(400)->respondWithArray($validatorResponse);
                }
                $calendar = $this->calendarRepository->batchUpdate($request->input('data'));
                break;
        }
        return isset($calendar['err_code']) ? $this->setStatusCode(409)->respondWithArray($calendar) : $this->setStatusCode(201)->respondWithCollection($calendar, $this->calendarTransform);
    }

    // 调用api生成日历 （目前只支持单年）
    public function createapi(Request $request)
    {
        // 检查参数是否合法
        $validatorResponse = $this->validateRequest($request, $this->createRequestValidationRules());
        // 返回参数不合法错误
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }
        // 先自动生成
        if (empty($request->input('holiday'))) {
            $this->calendarRepository->initOneYearDate($request->input('year'));
        }
        $url = config('calendar.apiUrl').$request->input('year');

        $response = Curl::to($url)
            ->withOption('RETURNTRANSFER', 1)
            ->withOption('PROXY', config('system.proxy'))
            ->withOption('PROXYPORT', config('system.proxyPort'))
            ->withData('')
            ->withHeaders([])
            ->get();
        $response = json_decode($response, true);
        if ($response['code'] != 0 || empty($response['holiday'])) {
            $this->setStatusCode(500)->respondWithArray(array('err_code' => 1001, 'err_memo' => 'api返回错误'));
        }
        $response['year'] = $request->input('year');
        $calendar =  $this->calendarRepository->adjustCalendar($response); // 替换假期
        return isset($calendar['err_code']) ? $this->setStatusCode(409)->respondWithArray($calendar) : $this->setStatusCode(201)->respondWithCollection($calendar, $this->calendarTransform);
    }

    // 单年日历新建规则
    private function storeRequestValidationRules()
    {
        $rules = [
            'year'                  => 'required'
        ];

        return $rules;
    }

    // 单年日历新建规则
    private function batchUpdateValidation($data)
    {
        $res = empty($data['end']) || empty($data['start']) || empty($data['type'])   ? array('err_code' => 1000, 'err_memo' => '参数错误') : true;

        return $res;
    }

    // api 生成日历参数规则
    private function createRequestValidationRules()
    {
        $rules = [
            'year'                  => 'required|digits:4'
        ];

        return $rules;
    }

    // 更新规则
    private function updateRequestValidationRules()
    {
        $rules = [
            'id'                  => 'required',
            'comment'             => 'required',
            'day'                 => 'numeric|required|between:1,31',
            'lunar'               => 'required',
            'lunar_text'          => 'required',
            'month'               => 'numeric|required|between:1,12',
            'other_text'          => 'required',
            'solar_text'          => 'required',
            'type'                => 'required|in:workday,festival,holiday',
            'year'                => 'required',
            'week'                => 'numeric|required|between:1,7',
        ];

        return $rules;
    }

    // 批量数据校验
    private function batchRequestValidationRules()
    {
        $rules = [
            'method'                  => 'required|string',
            'data'                    => 'required|array'
           ];

        return $rules;
    }

    // 集合数据返回整理
    public function respondWithCollection($collection, $callback)
    {
        $collection = collect($collection);
        $resource = new Collection($collection, $callback);
        $rootScope = $this->fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray());
    }
}