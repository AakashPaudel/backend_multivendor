<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\VendorDecisionRequest;
use App\Http\Resources\Admin\AdminVendorResource;
use App\Models\VendorProfile;
use App\Services\Admin\VendorApprovalService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class AdminVendorController extends Controller
{
    public function __construct(
        private readonly VendorApprovalService $vendorApprovalService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return AdminVendorResource::collection($this->vendorApprovalService->paginate());
    }

    public function approve(Request $request, VendorProfile $vendorProfile): AdminVendorResource
    {
        return new AdminVendorResource(
            $this->vendorApprovalService->approve($request->user(), $vendorProfile)
        );
    }

    public function reject(VendorDecisionRequest $request, VendorProfile $vendorProfile): AdminVendorResource
    {
        return new AdminVendorResource(
            $this->vendorApprovalService->reject($request->user(), $vendorProfile, $request->validated('reason'))
        );
    }

    public function suspend(VendorDecisionRequest $request, VendorProfile $vendorProfile): AdminVendorResource
    {
        return new AdminVendorResource(
            $this->vendorApprovalService->suspend($request->user(), $vendorProfile, $request->validated('reason'))
        );
    }
}
