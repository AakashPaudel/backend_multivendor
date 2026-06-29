<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReportFilterRequest;
use App\Http\Resources\Vendor\VendorDashboardResource;
use App\Http\Resources\Vendor\VendorSalesReportResource;
use App\Services\Vendor\VendorInsightsService;

class VendorInsightsController extends Controller
{
    public function __construct(
        private readonly VendorInsightsService $vendorInsightsService,
    ) {}

    public function dashboard(): VendorDashboardResource
    {
        return new VendorDashboardResource(
            $this->vendorInsightsService->dashboard(request()->user())
        );
    }

    public function sales(ReportFilterRequest $request): VendorSalesReportResource
    {
        return new VendorSalesReportResource(
            $this->vendorInsightsService->salesReport($request->user(), $request->validated())
        );
    }
}
