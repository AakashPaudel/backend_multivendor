<?php

namespace App\Http\Controllers\Api\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\StoreCustomerAddressRequest;
use App\Http\Requests\Customer\UpdateCustomerAddressRequest;
use App\Http\Resources\Customer\AddressResource;
use App\Models\Address;
use App\Services\Customer\CustomerAddressService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Symfony\Component\HttpFoundation\Response;

class CustomerAddressController extends Controller
{
    public function __construct(
        private readonly CustomerAddressService $customerAddressService,
    ) {}

    public function index(Request $request): AnonymousResourceCollection
    {
        $this->authorize('viewAny', Address::class);

        return AddressResource::collection(
            $this->customerAddressService->listForUser($request->user())
        );
    }

    public function store(StoreCustomerAddressRequest $request): AddressResource
    {
        $this->authorize('create', Address::class);

        return new AddressResource(
            $this->customerAddressService->create($request->user(), $request->validated())
        );
    }

    public function update(UpdateCustomerAddressRequest $request, Address $address): AddressResource
    {
        $this->authorize('update', $address);

        return new AddressResource(
            $this->customerAddressService->update($request->user(), $address, $request->validated())
        );
    }

    public function destroy(Request $request, Address $address): Response
    {
        $this->authorize('delete', $address);

        $this->customerAddressService->delete($request->user(), $address);

        return response()->noContent();
    }
}
