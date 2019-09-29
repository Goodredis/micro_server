<?php //app/Repositories/Contracts/UserRepository.php

namespace App\Repositories\Contracts;

interface OrgRepository extends BaseRepository
{

	/**
	 * @brief  通过组织编码获取组织信息
	 * @param  string|array  $codes  组织编码
	 * @return array
	 */
	public function getOrgByCodes($codes);

	/**
	 * @brief  通过组织名称获取组织信息
	 * @param  string|array  $names  组织名称
	 * @return array
	 */
	public function getOrgByNames($names);

}