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

    public function index(Request $request)
    {
        $res = $this->calendarRepository->findBy($request->all());

        return $this->respondWithCollection($res, $this->calendarTransform);
    }

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
                $calendar = $this->calendarRepository->batchUpdate($request->input('data'));
                break;
        }
        return isset($calendar['err_code']) ? $this->setStatusCode(409)->respondWithArray($calendar) : $this->setStatusCode(201)->respondWithCollection($calendar, $this->calendarTransform);
    }

    private function storeRequestValidationRules()
    {
        $rules = [
            'year'                  => 'required'
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

    public function respondWithCollection($collection, $callback)
    {
        $collection = collect($collection);
        $resource = new Collection($collection, $callback);
        $rootScope = $this->fractal->createData($resource);

        return $this->respondWithArray($rootScope->toArray());
    }
}