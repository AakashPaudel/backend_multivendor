<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Http\Resources\Admin\AdminDashboardResource;
use App\Http\Resources\Admin\AdminReportResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Admin\AdminInsightsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminInsightsController extends Controller
{
    public function __construct(
        private readonly AdminInsightsService $adminInsightsService,
    ) {}

    public function dashboard(): AdminDashboardResource
    {
        return new AdminDashboardResource($this->adminInsightsService->dashboard());
    }

    public function reports(ReportFilterRequest $request): AdminReportResource
    {
        return new AdminReportResource($this->adminInsightsService->reports($request->validated()));
    }

    public function users(ReportFilterRequest $request): AnonymousResourceCollection
    {
        return UserResource::collection($this->adminInsightsService->users($request->validated()));
    }
}
