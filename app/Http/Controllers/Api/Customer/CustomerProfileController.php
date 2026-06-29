<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\UpdateCustomerProfileRequest;
use App\Http\Resources\Customer\CustomerProfileResource;
use App\Services\Customer\CustomerProfileService;
use Illuminate\Http\Request;

class CustomerProfileController extends Controller
{
    public function __construct(
        private readonly CustomerProfileService $customerProfileService,
    ) {}

    public function show(Request $request): CustomerProfileResource
    {
        $profile = $request->user()->customerProfile()->with('defaultAddress')->firstOrFail();

        $this->authorize('view', $profile);

        return new CustomerProfileResource($profile->loadMissing('user', 'defaultAddress'));
    }

    public function update(UpdateCustomerProfileRequest $request): CustomerProfileResource
    {
        $profile = $request->user()->customerProfile()->firstOrFail();

        $this->authorize('update', $profile);

        return new CustomerProfileResource(
            $this->customerProfileService->update($request->user(), $profile, $request->validated())
                ->loadMissing('user', 'defaultAddress')
        );
    }
}
