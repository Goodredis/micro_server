<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Org;
use App\Repositories\Contracts\OrgRepository;
use App\Repositories\Contracts\UserRepository;
use App\Transformers\OrgTransformer;

class OrgController extends Controller
{
    /**
     * Instance of OrgRepository
     *
     * @var OrgRepository
     */
    private $orgRepository;

    /**
     * Instanceof OrgTransformer
     *
     * @var OrgTransformer
     */
    private $orgTransformer;

    /**
     * Instance of UserRepository
     *
     * @var UserRepository
     */
    private $userRepository;


    /**
     * Constructor
     *
     * @param OrgRepository $orgRepository
     * @param OrgTransformer $orgTransformer
     */
    public function __construct(OrgRepository $orgRepository, OrgTransformer $orgTransformer, UserRepository $userRepository){
        $this->orgRepository = $orgRepository;
        $this->orgTransformer = $orgTransformer;
        $this->userRepository = $userRepository;
        parent::__construct();
    }

    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request){
        $orgs = $this->orgRepository->findBy($request->all());
        return $this->respondWithCollection($orgs, $this->orgTransformer);
    }

    /**
     * Display the specified rpesource.
     *
     * @param $code
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function show($code) {
        $org = $this->orgRepository->findOneBy(array('code' => $code));
        if (!$org instanceof Org) {
            return $this->sendNotFoundResponse("The organization with code {$code} doesn't exist");
        }
        return $this->respondWithItem($org, $this->orgTransformer);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function store(Request $request) {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->storeRequestValidationRules($request));
        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }

        $org = $this->orgRepository->save($request->all());

        if (!$org instanceof Org) {
            return $this->sendCustomResponse(500, 'Error occurred on creating organization');
        }

        return $this->setStatusCode(201)->respondWithItem($org, $this->orgTransformer);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param $code
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $code) {
        // Validation
        $validatorResponse = $this->validateRequest($request, $this->updateRequestValidationRules($request));
        // Send failed response if validation fails
        if ($validatorResponse !== true) {
            return $this->sendInvalidFieldResponse($validatorResponse);
        }
        $org = $this->orgRepository->findOneBy(array('code' => $code));
        if (!$org instanceof Org) {
            return $this->sendNotFoundResponse("The organization with code {$code} doesn't exist");
        }

        $org = $this->orgRepository->update($org, $request->all());

        return $this->respondWithItem($org, $this->orgTransformer);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param $code
     * @return \Illuminate\Http\JsonResponse|string
     */
    public function destroy($code) {
        $org = $this->orgRepository->findOneBy(array('code' => $code));
        if (!$org instanceof Org) {
            return $this->sendNotFoundResponse("The organization with code {$code} doesn't exist");
        }
        $this->orgRepository->delete($org);
        return response(null, 204);
    }

    /**
     * Store Request Validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function storeRequestValidationRules(Request $request) {
        $rules = [
            'code'                  => 'string|required|max:64',
            'name'                  => 'string|required|max:255',
            'order'                 => 'string|required|max:64',
            'ldap_id'               => 'string|required|max:255',
        ];
        return $rules;
    }

    /**
     * Update Request validation Rules
     *
     * @param Request $request
     * @return array
     */
    private function updateRequestValidationRules(Request $request) {
        $rules = [
            'code'                  => 'string|max:64',
            'name'                  => 'string|max:255',
            'order'                 => 'string|max:64',
            'ldap_id'               => 'string|max:255',
        ];
        return $rules;
    }

    /**
     * @brief 获取某一部所人员的接口
     * @param str code，osms_org表的code
     * @return array
     */
    public function getOrgUsers($code){
        $user_lists = $this->userRepository->getOrgUsers($code);
        return $this->respondWithArray(['data' => $user_lists]);
    }

}