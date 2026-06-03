<?php

declare(strict_types=1);

namespace App\Modules\Orders\Presentation\Http\Controllers;

use App\Modules\Orders\Application\Services\AddressService;
use App\Modules\Orders\Presentation\Http\Requests\StoreAddressRequest;
use App\Modules\Orders\Presentation\Http\Resources\AddressResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Routing\Controller;

class AddressController extends Controller
{
    public function __construct(private readonly AddressService $addresses)
    {
    }

    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection($this->addresses->forUser($request->user()));
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = $this->addresses->create($request->user(), $request->validated());

        return (new AddressResource($address))->response()->setStatusCode(JsonResponse::HTTP_CREATED);
    }

    public function update(StoreAddressRequest $request, string $address): AddressResource
    {
        return new AddressResource($this->addresses->update($request->user(), $address, $request->validated()));
    }

    public function destroy(Request $request, string $address): JsonResponse
    {
        $this->addresses->delete($request->user(), $address);

        return response()->json(status: JsonResponse::HTTP_NO_CONTENT);
    }
}
