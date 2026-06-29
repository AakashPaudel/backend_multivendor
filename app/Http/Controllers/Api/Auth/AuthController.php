<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\VendorRegisterRequest;
use App\Http\Resources\Auth\AuthenticatedUserResource;
use App\Http\Resources\Auth\UserResource;
use App\Services\Auth\LoginService;
use App\Services\Auth\RegisterCustomerService;
use App\Services\Auth\RegisterVendorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    public function __construct(
        private readonly RegisterCustomerService $registerCustomerService,
        private readonly RegisterVendorService $registerVendorService,
        private readonly LoginService $loginService,
    ) {}

    public function registerCustomer(CustomerRegisterRequest $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource(
            $this->registerCustomerService->handle($request->validated())
        );
    }

    public function registerVendor(VendorRegisterRequest $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource(
            $this->registerVendorService->handle($request->validated())
        );
    }

    public function login(LoginRequest $request): AuthenticatedUserResource
    {
        return new AuthenticatedUserResource(
            $this->loginService->handle($request->validated())
        );
    }

    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()?->delete();

        return response()->json([
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request): UserResource
    {
        return new UserResource(
            $request->user()->loadMissing([
                'customerProfile.defaultAddress',
                'vendorProfile',
            ])
        );
    }
}
