<?php

namespace App\Transformers;

use App\Models\User;
use League\Fractal\TransformerAbstract;

class UserTransformer extends TransformerAbstract
{
    public function transform(User $user)
    {
        $formattedUser = [
            'id'                    => $user->id,
            'name'                  => $user->name,
            'gender'                => $user->gender,
            'mobile'                => $user->mobile,
            'email'                 => $user->email,
            'avatar'                => $user->avatar,
            'employee_number'       => $user->employee_number,
            'title'                 => $user->title,
            'order'                 => $user->order,
            'org_code'              => $user->org_code,
            'status'                => $user->status,
        ];

        return $formattedUser;
    }
    /**
     * 获取所属组织
     * @param model quota
     * @return \League\Fractal\Resource\Item
     */
    public function includeOrg(User $user) {
        $orgTransformer = new OrgTransformer();
        $orgTransformer = $orgTransformer->setDefaultIncludes([]);
        return $this->item($user->org, $orgTransformer, 'include');
    }
}
