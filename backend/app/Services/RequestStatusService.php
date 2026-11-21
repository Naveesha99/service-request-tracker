<?php

namespace App\Services;

use App\Enums\ServiceRequestStatus;
use App\Exceptions\RequestStatusConflictException;
use App\Models\RequestStatusHistory;
use App\Models\ServiceRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class RequestStatusService
{
    public function transition(ServiceRequest $serviceRequest, User $actor, string $requestedStatus, int $expectedVersion, ?string $remark): ServiceRequest
    {
        return DB::transaction(function () use ($serviceRequest, $actor, $requestedStatus, $expectedVersion, $remark): ServiceRequest {
            $current = $actor->serviceRequests()
                ->whereKey($serviceRequest->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($current->version !== $expectedVersion) {
                throw new RequestStatusConflictException(
                    'STALE_VERSION',
                    'This service request has been updated by another operation. Please refresh and try again.',
                );
            }

            $fromStatus = $current->status;
            $toStatus = ServiceRequestStatus::from($requestedStatus);

            if (! $this->isAllowedTransition($fromStatus, $toStatus)) {
                throw new RequestStatusConflictException(
                    'INVALID_TRANSITION',
                    'The requested status transition is not allowed.',
                );
            }

            $affected = DB::table('service_requests')
                ->where('id', $current->id)
                ->where('created_by', $actor->id)
                ->where('version', $expectedVersion)
                ->where('status', $fromStatus->value)
                ->update([
                    'status' => $toStatus->value,
                    'version' => $expectedVersion + 1,
                    'updated_at' => now(),
                ]);

            if ($affected !== 1) {
                throw new RequestStatusConflictException(
                    'STALE_VERSION',
                    'This service request has been updated by another operation. Please refresh and try again.',
                );
            }

            RequestStatusHistory::create([
                'service_request_id' => $current->id,
                'actor_user_id' => $actor->id,
                'from_status' => $fromStatus->value,
                'to_status' => $toStatus->value,
                'remark' => $remark,
            ]);

            $current->status = $toStatus;
            $current->version = $expectedVersion + 1;
            $current->updated_at = now();

            return $current;
        });
    }

    private function isAllowedTransition(ServiceRequestStatus $from, ServiceRequestStatus $to): bool
    {
        return match ($from) {
            ServiceRequestStatus::OPEN => $to === ServiceRequestStatus::IN_PROGRESS,
            ServiceRequestStatus::IN_PROGRESS => $to === ServiceRequestStatus::RESOLVED,
            ServiceRequestStatus::RESOLVED => false,
        };
    }
}
