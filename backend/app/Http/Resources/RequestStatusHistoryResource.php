<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class RequestStatusHistoryResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'from_status' => $this->from_status instanceof \BackedEnum ? $this->from_status->value : $this->from_status,
            'to_status' => $this->to_status instanceof \BackedEnum ? $this->to_status->value : $this->to_status,
            'remark' => $this->remark,
            'actor' => [
                'id' => $this->actor->id,
                'name' => $this->actor->name,
                'email' => $this->actor->email,
            ],
            'created_at' => $this->created_at,
        ];
    }
}
