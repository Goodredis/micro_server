<?php

namespace App\Repositories;

use Exception;
use App\Models\Org;
use Illuminate\Database\Eloquent\Model;
use App\Repositories\Contracts\OrgRepository;

class EloquentOrgRepository extends AbstractEloquentRepository implements OrgRepository
{

    /**
     * @brief  创建组织信息
     * @param  array  $data
     * @param  item
     */
    public function save(array $data) {
        return parent::save($data);
    }

    /**
     * @brief  更新组织信息
     * @param  model  $model
     * @param  array  $data
     * @param  item
     */
    public function update(Model $model, array $data) {
        return parent::update($model, $data);
    }

    /**
     * @brief  获取院内组织列表
     * @param  string  name  名称
     * @return collection
     */
    public function findBy(array $searchCriteria = [], array $operatorCriteria = []){
    	// 支持检索部门名称
    	$searchCriteria['name'] = isset($searchCriteria['name']) ? '%'.$searchCriteria['name'].'%' : '%%';
    	$operatorCriteria['name'] = 'like';
    	// 默认按组织顺序显示
        $searchCriteria['orderby'] = (isset($searchCriteria['orderby']) && !empty($searchCriteria['orderby'])) ? $searchCriteria['orderby'] : 'order';
        // 默认全部显示
        $searchCriteria['per_page'] = isset($searchCriteria['per_page']) ? $searchCriteria['per_page'] : 50;
        // 默认检索有效数据
        $searchCriteria['status'] = 1;
        $operatorCriteria['status'] = '!=';
        return parent::findBy($searchCriteria, $operatorCriteria);
    }

    /**
     * @brief  获取院内单条组织
     * @param  string  code  组织编码
     * @return item
     */
    public function findOneBy(array $criteria) {
        // if (!isset($criteria['code']) || empty($criteria['code'])) {
        //     throw new Exception(trans('errorCode.110006'), 110006);
        // }
        // 单条组织详情 不过滤状态位
        return parent::findOneBy($criteria);
    }

    /**
     * @brief  删除组织信息--逻辑删除
     * @param  model $model
     * @return item
     */
    public function delete(Model $model) {
        return $this->update($model, array('status' => 1));
    }

    /**
     * @brief  通过组织编码获取组织信息
     * @param  string|array  $codes  组织编码
     * @return array
     */
    public function getOrgByCodes($codes) {
        if (is_array($codes)) {
            if (count($codes) > 1) {
                $codes = implode(",", $codes);
            } else {
                $codes = array_pop($codes);
            }
        }
        // 默认检索有效数据
        $orgs = parent::findBy(array('columns' => 'code,name,order', 'code' => $codes, 'status' => 1), array('status' => '!='))->toArray();
        return count($orgs['data'])==1 ? array_pop($orgs['data']) : $orgs['data'];
    }

    /**
     * @brief  通过组织名称获取组织信息
     * @param  string|array  $names  组织名称
     * @return array
     */
    public function getOrgByNames($names) {
        if (is_array($names)) {
            if (count($names) > 1) {
                $names = implode(",", $names);
            } else {
                $names = array_pop($names);
            }
        }
        // 默认检索有效数据
        $orgs = parent::findBy(array('columns' => 'code,name,order', 'name' => $names, 'status' => 1), array('status' => '!='))->toArray();
        return count($orgs['data'])==1 ? array_pop($orgs['data']) : $orgs['data'];
    }
   
}