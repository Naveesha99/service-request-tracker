<?php

namespace App\Models;

use App\Enums\ServiceRequestStatus;
use Database\Factories\RequestStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Model;

class RequestStatusHistory extends Model
{
    /** @use HasFactory<RequestStatusHistoryFactory> */
    use HasFactory;

    const UPDATED_AT = null;

    protected $fillable = [
        'service_request_id',
        'actor_user_id',
        'from_status',
        'to_status',
        'remark',
    ];

    protected function casts(): array
    {
        return [
            'from_status' => ServiceRequestStatus::class,
            'to_status' => ServiceRequestStatus::class,
        ];
    }

    public function serviceRequest(): BelongsTo
    {
        return $this->belongsTo(ServiceRequest::class, 'service_request_id');
    }

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_user_id');
    }
}
