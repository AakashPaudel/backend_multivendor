<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateCommissionSettingsRequest;
use App\Http\Requests\Admin\UpdatePlatformSettingsRequest;
use App\Http\Resources\Admin\AdminCommissionResource;
use App\Http\Resources\Admin\AdminSettingResource;
use App\Services\Admin\AdminInsightsService;
use App\Services\Admin\AdminSettingsService;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminSettingsController extends Controller
{
    public function __construct(
        private readonly AdminInsightsService $adminInsightsService,
        private readonly AdminSettingsService $adminSettingsService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return AdminSettingResource::collection($this->adminInsightsService->settings());
    }

    public function update(UpdatePlatformSettingsRequest $request): AnonymousResourceCollection
    {
        return AdminSettingResource::collection(
            $this->adminSettingsService->updateSettings($request->user(), $request->validated())
        );
    }

    public function commissions(): AdminCommissionResource
    {
        return new AdminCommissionResource($this->adminSettingsService->commissionSnapshot());
    }

    public function updateCommissions(UpdateCommissionSettingsRequest $request): AdminCommissionResource
    {
        return new AdminCommissionResource(
            $this->adminSettingsService->updateCommissions($request->user(), $request->validated())
        );
    }
}
