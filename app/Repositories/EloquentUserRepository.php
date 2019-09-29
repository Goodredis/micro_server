<?php

namespace App\Repositories;

use App\Repositories\Contracts\UserRepository;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Eloquent\Model;

class EloquentUserRepository extends AbstractEloquentRepository implements UserRepository
{
    /*
     * @inheritdoc
     */
    public function save(array $data)
    {
        // update password
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $user = parent::save($data);

        return $user;
    }

    /**
     * @inheritdoc
     */
    public function update(Model $model, array $data)
    {
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $updatedUser = parent::update($model, $data);

        return $updatedUser;
    }

    /**
     * @inheritdoc
     */
    public function findBy(array $searchCriteria = [], array $operatorCriteria = [])
    {
        // 支持姓名模糊匹配
        if (isset($searchCriteria['name'])) {
            $searchCriteria['name'] = '%' . $searchCriteria['name'] . '%';
            $operatorCriteria['name'] = 'like';
        }
        // 支持邮箱模糊匹配
        if (isset($searchCriteria['email'])) {
            $searchCriteria['email'] = '%' . $searchCriteria['email'] . '%';
            $operatorCriteria['email'] = 'like';
        }
        // 默认匹配非离职人员
        $searchCriteria['status'] = 2;
        $operatorCriteria['status'] = '!=';
        return parent::findBy($searchCriteria, $operatorCriteria);
    }

    /**
     * @inheritdoc
     */
    public function findOne($id)
    {
        if ($id === 'me') {
            return $this->getLoggedInUser();
        }

        return parent::findOne($id);
    }

    /**
     * @brief  通过名字获取用户信息
     * @param  string | array
     * @return array
     */
    public function getUserInfoByNamesOrEmails($contents) {
        if (is_array($contents)) {
            if (count($contents) > 1) {
                $contents = implode(",", $contents);
            } else {
                $contents = array_pop($contents);
            }
        }
        $users = User::select('*')
                        ->orWhere(function ($query) {
                            $query->where('name', 'like', $contents)
                                  ->where('email', 'like', $contents);})
                        ->where('status', '!=', 2)
                        ->orderBy('order')
                        ->get()
                        ->toArray();
        return count($users['data'])==1 ? array_pop($users['data']) : $users['data'];
    }

    /**
     * @brief  通过ids获取用户信息
     * @param  string | array
     * @return array
     */
    public function getUserInfoByUids($ids) {
        if (is_array($ids)) {
            if (count($ids) > 1) {
                $ids = implode(",", $ids);
            } else {
                $ids = array_pop($ids);
            }
        }
        $users = parent::findBy(array('id' => $ids, 'del_flag' => 2), array('del_flag' => '!='))->toArray();
        return count($users['data'])==1 ? array_pop($users['data']) : $users['data'];
    }

    /**
     * @brief  通过org_code获取用户信息
     * @param  string org_code
     * @return array
     */
    public function getOrgUsers($org_code) {
        $users = $this->model->select("id","name")
                             ->where('status', '!=', 2)
                             ->where('org_code', '=', $org_code)
                             ->get()
                             ->toArray();
        return $users;
    }
}
