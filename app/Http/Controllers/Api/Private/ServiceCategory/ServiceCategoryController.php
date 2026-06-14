<?php

namespace App\Http\Controllers\Api\Private\ServiceCategory;

use App\Http\Controllers\Controller;
use App\Http\Requests\ServiceCategory\CreateServiceCategoryRequest;
use App\Http\Requests\ServiceCategory\UpdateServiceCategoryRequest;
use App\Http\Resources\ServiceCategory\AllServiceCategoryCollection;
use App\Http\Resources\ServiceCategory\ServiceCategoryResource;
use App\Services\ServiceCategory\ServiceCategoryService;
use Illuminate\Http\Request;
use App\Utils\PaginateCollection;
use Illuminate\Support\Facades\DB;


class ServiceCategoryController extends Controller
{
    protected $serviceCategoryService;

    public function __construct(ServiceCategoryService $serviceCategoryService)
    {
        $this->middleware('auth:api');
        $this->middleware('permission:all_service_categories', ['only' => ['allServiceCategorys']]);
        $this->middleware('permission:create_service_category', ['only' => ['create']]);
        $this->middleware('permission:edit_service_category', ['only' => ['edit']]);
        $this->middleware('permission:update_service_category', ['only' => ['update']]);
        $this->middleware('permission:delete_service_category', ['only' => ['delete']]);
        $this->serviceCategoryService = $serviceCategoryService;
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $allServiceCategorys = $this->serviceCategoryService->allServiceCategories();

        return response()->json(
            new AllServiceCategoryCollection(PaginateCollection::paginate($allServiceCategorys, $request->pageSize?$request->pageSize:10))
        );

    }

    /**
     * Show the form for creating a new resource.
     */

    public function create(CreateServiceCategoryRequest $createServiceCategoryRequest)
    {

        try {
            DB::beginTransaction();

            $data = $createServiceCategoryRequest->validated();
            $serviceCategory = $this->serviceCategoryService->createServiceCategory($data);

            DB::commit();

            return response()->json([
                'message' => __('messages.success.created')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Show the form for editing the specified resource.
     */

    public function edit(Request $request)
    {
        $serviceCategory  =  $this->serviceCategoryService->editServiceCategory($request->serviceCategoryId);

        return new ServiceCategoryResource($serviceCategory);//new ServiceCategoryResource($serviceCategory)


    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateServiceCategoryRequest $updateServiceCategoryRequest)
    {

        try {
            DB::beginTransaction();
            $this->serviceCategoryService->updateServiceCategory($updateServiceCategoryRequest->validated());
            DB::commit();
            return response()->json([
                 'message' => __('messages.success.updated')
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

    /**
     * Remove the specified resource from storage.
     */
    public function delete(Request $request)
    {

        try {
            DB::beginTransaction();
            $this->serviceCategoryService->deleteServiceCategory($request->serviceCategoryId);
            DB::commit();
            return response()->json([
                'message' => __('messages.success.deleted')
            ], 200);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }


    }

}
