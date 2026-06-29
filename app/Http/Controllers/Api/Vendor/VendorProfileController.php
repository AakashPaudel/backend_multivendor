<?php

namespace App\Http\Controllers\Api\Vendor;

use App\Http\Controllers\Controller;
use App\Http\Requests\Vendor\UpdateVendorProfileRequest;
use App\Http\Resources\Vendor\VendorProfileResource;
use App\Services\Vendor\VendorProfileService;
use Illuminate\Http\Request;

class VendorProfileController extends Controller
{
    public function __construct(
        private readonly VendorProfileService $vendorProfileService,
    ) {}

    public function show(Request $request): VendorProfileResource
    {
        $profile = $request->user()->vendorProfile()->firstOrFail();
        $this->authorize('view', $profile);

        return new VendorProfileResource($profile);
    }

    public function update(UpdateVendorProfileRequest $request): VendorProfileResource
    {
        $profile = $request->user()->vendorProfile()->firstOrFail();
        $this->authorize('update', $profile);

        return new VendorProfileResource(
            $this->vendorProfileService->update($request->user(), $profile, $request->validated())
        );
    }
}
