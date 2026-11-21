<?php

namespace App\Http\Controllers\Api;

use App\Enums\ServiceRequestStatus;
use App\Exceptions\RequestStatusConflictException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListServiceRequestsRequest;
use App\Http\Requests\StoreServiceRequestRequest;
use App\Http\Requests\UpdateRequestStatusRequest;
use App\Http\Resources\RequestStatusHistoryResource;
use App\Http\Resources\ServiceRequestResource;
use App\Models\ServiceRequest;
use App\Services\RequestStatusService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Request;

class ServiceRequestController extends Controller
{
    public function store(StoreServiceRequestRequest $request): JsonResponse
    {
        $data = $request->validated();

        $serviceRequest = new ServiceRequest([
            'title' => $data['title'],
            'description' => $data['description'],
            'priority' => $data['priority'],
        ]);
        $serviceRequest->forceFill([
            'created_by' => $request->user()->id,
            'status' => ServiceRequestStatus::OPEN->value,
            'version' => 1,
        ]);
        $serviceRequest->save();

        return (new ServiceRequestResource($serviceRequest))
            ->additional(['message' => 'Service request created successfully.'])
            ->response()
            ->setStatusCode(201);
    }

    public function index(ListServiceRequestsRequest $request): AnonymousResourceCollection
    {
        $data = $request->validated();
        $perPage = (int) ($data['per_page'] ?? 10);

        $query = $request->user()->serviceRequests()
            ->latest('created_at')
            ->latest('id');

        $search = isset($data['search']) ? trim($data['search']) : '';
        if ($search !== '') {
            $escapedSearch = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $search);
            $query->whereRaw("title LIKE ? ESCAPE '\\'", ['%' . $escapedSearch . '%']);
        }

        if (isset($data['status'])) {
            $query->where('status', $data['status']);
        }

        if (isset($data['priority'])) {
            $query->where('priority', $data['priority']);
        }

        $paginator = $query->paginate($perPage, ['*'], 'page', (int) ($data['page'] ?? 1));

        return ServiceRequestResource::collection($paginator);
    }

    public function show(Request $request, int $id): ServiceRequestResource
    {
        $serviceRequest = $request->user()->serviceRequests()->find($id);

        abort_if($serviceRequest === null, 404, 'Service request not found.');

        return new ServiceRequestResource($serviceRequest);
    }

    public function updateStatus(UpdateRequestStatusRequest $request, int $id, RequestStatusService $service): JsonResponse
    {
        $serviceRequest = $request->user()->serviceRequests()->find($id);

        abort_if($serviceRequest === null, 404, 'Service request not found.');

        $data = $request->validated();

        try {
            $updated = $service->transition(
                $serviceRequest,
                $request->user(),
                $data['status'],
                (int) $data['expected_version'],
                $data['remark'] ?? null,
            );
        } catch (RequestStatusConflictException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
                'error' => ['code' => $exception->errorCode],
            ], 409);
        }

        return (new ServiceRequestResource($updated))->response();
    }

    public function history(Request $request, int $id): AnonymousResourceCollection
    {
        $serviceRequest = $request->user()->serviceRequests()->find($id);

        abort_if($serviceRequest === null, 404, 'Service request not found.');

        $histories = $serviceRequest->statusHistories()
            ->with('actor:id,name,email')
            ->oldest('created_at')
            ->oldest('id')
            ->get();

        return RequestStatusHistoryResource::collection($histories);
    }
}
