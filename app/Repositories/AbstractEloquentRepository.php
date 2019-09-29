<?php

namespace App\Repositories;

use App\Models\User;
use Ramsey\Uuid\Uuid;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use App\Repositories\Contracts\BaseRepository;

abstract class AbstractEloquentRepository implements BaseRepository
{
    /**
     * Name of the Model with absolute namespace
     *
     * @var string
     */
    protected $modelName;

    /**
     * Instance that extends Illuminate\Database\Eloquent\Model
     *
     * @var Model
     */
    protected $model;

    /**
     * get logged in user
     *
     * @var User $loggedInUser
     */
    protected $loggedInUser;

    /**
     * Constructor
     */
    public function __construct(Model $model)
    {
        $this->model = $model;
        //$this->loggedInUser = $this->getLoggedInUser();
    }

    /**
     * Get Model instance
     *
     * @return Model
     */
    public function getModel()
    {
        return $this->model;
    }

    /**
     * @inheritdoc
     */
    public function findOne($id)
    {
        return $this->findOneBy(['id' => $id]);
    }

    /**
     * @inheritdoc
     */
    public function findOneBy(array $criteria)
    {
        return $this->model->where($criteria)->first();
    }

    /**
     * @inheritdoc
     */
    public function findBy(array $searchCriteria = [], array $operatorCriteria = [])
    {
        //获取表中所有字段
        $tableColumns = Schema::getColumnListing($this->model->getTable());

        $limit = !empty($searchCriteria['per_page']) ? (int)$searchCriteria['per_page'] : 15; // it's needed for pagination
        $page = !empty($searchCriteria['page']) ? (int)$searchCriteria['page'] : 1; //默认为第一页

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

        //关联查询的条件
        $relation = (isset($searchCriteria['hasrelation']) && !empty($searchCriteria['hasrelation'])) ? $searchCriteria['hasrelation'] : [];

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

        // $relation = [
        //     0 => [
        //         'relation_name' => 'supplierInfo',
        //         'search' => [
        //             'code'=>'wensihaihui'
        //         ],
        //         'operator'=> [
        //             'name' => 'like'
        //         ],
        //         'hasrelation' => [
        //             0 => [
        //                 'relation_name' => 'framework',
        //                 'search' => [
        //                     'id'=>'b54025eb-a9ef-45fa-8add-81065dd44178'
        //                 ],
        //                 'operator'=> [
        //                     'name' => 'like'
        //                 ],
        //                 'hasrelation' => [
        //                     0 => [
        //                        'relation_name' => 'frameworkdetails',
        //                         'search' => [
        //                             'level'=>'1'
        //                         ],
        //                     ]
        //                 ]
        //             ]
        //         ]
        //     ],
        //     1 => [
        //         'relation_name' => 'orderQuotas',
        //         'search' => [
        //             'id'=>68
        //         ],
        //         'operator'=> [
        //             'name' => 'like'
        //         ]
        //     ]
        // ];

        if(!empty($relation)){
            $queryBuilder = $this -> applyHasSearchInQueryBuilder($queryBuilder, $relation);
        }

        $queryBuilder = $this->applyOrderCriteriaInQueryBuilder($queryBuilder, $orderby);

        return $queryBuilder->paginate($limit, $columns, 'page', $page);
    }

    /**
     * @brief 递归构造wherehas
     * @param Object $queryBuilder
     * @param array relations =
    [
    0=>[
    'relation_name' => 'supplierInfo', //model中建立的关系方法名
    'search' => [ //关联表的查询条件
    'code'=>'wensihaihui'
    ],
    'operator'=> [ //给search定义操作符
    'name' => 'like'
    ],
    'hasrelation' =>[ //下一个关联关系
    0 => [
    'relation_name' => 'framework',
    'search' => [
    'id'=>'b54025eb-a9ef-45fa-8add-81065dd44178'
    ]
    ]
    ]
    ]
    1 => [
    'relation_name' => 'orderQuotas',
    'search' => [
    'id'=>68
    ]
    ]
    ]
     * @return mixed
     */
    public function applyHasSearchInQueryBuilder($queryBuilder, array $relations){
        foreach ($relations as $key => $relation) {
            $relation_search = $relation['search'];
            $relation_operator = $relation['operator'];
            $queryBuilder = $queryBuilder->whereHas($relation['relation_name'], function ($query) use ($relation_search, $relation_operator, $relation)
            {
                $query = $this->applySearchCriteriaInQueryBuilder($query, $relation_search, $relation_operator);
                if(isset($relation['hasrelation']) && !empty($relation['hasrelation'])){
                    $query = $this -> applyHasSearchInQueryBuilder($query, $relation['hasrelation']);
                }
            });
        }

        return $queryBuilder;
    }

    /**
     * Apply condition on query builder based on search criteria
     *
     * @param Object $queryBuilder
     * @param array $searchCriteria
     * @return mixed
     */
    protected function applySearchCriteriaInQueryBuilder($queryBuilder, array $searchCriteria = [], array $operatorCriteria = [])
    {

        foreach ($searchCriteria as $key => $value) {

            //skip pagination related query params
            if (in_array($key, ['page', 'per_page'])) {
                continue;
            }

            //we can pass multiple params for a filter with commas
            $allValues = explode(',', $value);
            $betValues = explode('~', $value);

            if (!empty($operatorCriteria[$key]) && $operatorCriteria[$key] == 'raw') {
                $queryBuilder->whereRaw($value);
            } elseif (count($allValues) > 1) {
                $queryBuilder->whereIn($key, $allValues);
            } elseif (count($betValues) > 1 && $operatorCriteria[$key] == 'between') {
                $first = current($betValues);
                $second = end($betValues);
                if (empty($first)) {
                    $queryBuilder->where($key, '<=', $second);
                } elseif (empty($second)) {
                    $queryBuilder->where($key, '>=', $first);
                } else {
                    $queryBuilder->whereBetween($key, $betValues);
                }
            } else {
                $operator = array_key_exists($key, $operatorCriteria) ? $operatorCriteria[$key] : '=';
                $queryBuilder->where($key, $operator, $value);
            }
        }

        return $queryBuilder;
    }

    /**
     * @inheritdoc
     */
    protected function applyOrderCriteriaInQueryBuilder($queryBuilder, $orderby) 
    {
        if (empty($orderby)) {
            return $queryBuilder;
        }

        $sections = explode(',', trim($orderby));
        foreach ($sections as $section) {
            $section = trim($section);
            if ($section) {
                $tmp = explode(' ', $section);
                $queryBuilder = $queryBuilder->orderBy(trim($tmp[0]), isset($tmp[1]) ? $tmp[1] : 'ASC');
            }
        }
        return $queryBuilder;
    }

    /**
     * @inheritdoc
     */
    public function save(array $data)
    {
        // generate uid
        if(!$this->model->incrementing) $data['id'] = Uuid::uuid4();
        return $this->model->create($data);
    }

    /**
     * @inheritdoc
     */
    public function update(Model $model, array $data)
    {
        $fillAbleProperties = $this->model->getFillable();
        $guardedAbleProperties = $this->model->getGuarded();
        $tableColumns = empty($fillAbleProperties) && empty($guardedAbleProperties) ?  : $fillAbleProperties;
        // 判断生成 可操控属性列
        if (empty($fillAbleProperties) && empty($guardedAbleProperties)) {
            $tableColumns = Schema::getColumnListing($this->model->getTable());
        } elseif (empty($fillAbleProperties)) {
            foreach ($guardedAbleProperties as $k => $v) {
                if(in_array($v, $tableColumns)){
                    unset($tableColumns[$k]);
                }
            }
        } else {
            $tableColumns = $fillAbleProperties;
        }

        foreach ($data as $key => $value) {
            // update only fillAble properties
            if (in_array($key, $tableColumns)) {
                $model->$key = $value;
            }
        }
        // update the model
        $model->save();

        // get updated model from database
        $model = $this->findOne($model->id);

        return $model;
    }

    /**
     * @inheritdoc
     */
    public function findIn($key, array $values)
    {
        return $this->model->whereIn($key, $values)->get();
    }

    /**
     * @inheritdoc
     */
    public function delete(Model $model){
        return $model->delete();
    }

    /**
     * 批量删除
     * @param array $ids，注意id必须是数组，即使只有一个元素也得是数组格式
    */
    public function destroy($ids){
        return $this->model->destroy($ids);
    }

    /**
     * get loggedIn user
     *
     * @return User
     */
    protected function getLoggedInUser()
    {
        $user = \Auth::user();

        if ($user instanceof User) {
            return $user;
        } else {
            return new User();
        }
    }

}
