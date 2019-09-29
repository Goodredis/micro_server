<?php //app/Repositories/Contracts/UserRepository.php

namespace App\Repositories\Contracts;

interface UserRepository extends BaseRepository
{
    /**
     * @brief  通过名字获取用户信息
     * @param  string | array
     * @return array
     */
    public function getUserInfoByNamesOrEmails($contents);

    /**
     * @brief  通过ids获取用户信息
     * @param  string | array
     * @return array
     */
    public function getUserInfoByUids($ids);

    /**
     * @brief  通过org_code获取用户信息
     * @param  string org_code
     * @return array
     */
    public function getOrgUsers($org_code);
}