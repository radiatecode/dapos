<?php

namespace DA\Inventory\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use DA\Inventory\Actions\Customer\CreateCustomer;
use DA\Inventory\Actions\Customer\DeleteCustomer;
use DA\Inventory\Actions\Customer\UpdateCustomer;
use DA\Inventory\Http\Requests\Api\V1\Customer\StoreCustomerRequest;
use DA\Inventory\Http\Requests\Api\V1\Customer\UpdateCustomerRequest;
use DA\Inventory\Http\Resources\Api\V1\CustomerResource;
use DA\Inventory\Models\Customer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CustomerController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $perPage = $request->integer('per_page', $request->integer('limit', 15));
        $name = $request->string('name')->toString();
        $email = $request->string('email')->toString();
        $phone = $request->string('phone')->toString();

        return CustomerResource::collection(
            Customer::queries()->paginateNewestFirst(
                $perPage,
                $name !== '' ? $name : null,
                $email !== '' ? $email : null,
                $phone !== '' ? $phone : null,
            ),
        );
    }

    public function store(StoreCustomerRequest $request, CreateCustomer $create): JsonResponse
    {
        $customer = $create->handle($request->toDTO());

        return CustomerResource::make($customer)
            ->response()
            ->setStatusCode(201);
    }

    public function show(int $id): CustomerResource
    {
        return CustomerResource::make(Customer::queries()->findForTenant($id));
    }

    public function update(UpdateCustomerRequest $request, int $id, UpdateCustomer $update): CustomerResource
    {
        return CustomerResource::make($update->handle($id, $request->toDTO()));
    }

    public function destroy(Request $request, DeleteCustomer $delete): JsonResponse
    {
        if ($request->input('action') !== 'delete') {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid action',
            ], Response::HTTP_BAD_REQUEST);
        }

        $delete->handle($request->input('ids', []));

        return response()->json([
            'status' => 'success',
            'message' => 'Customer deleted successfully',
        ], Response::HTTP_OK);
    }
}
