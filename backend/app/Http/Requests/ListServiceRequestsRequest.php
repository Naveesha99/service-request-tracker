<?php

namespace App\Http\Requests;

use App\Enums\ServiceRequestPriority;
use App\Enums\ServiceRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListServiceRequestsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'search' => ['sometimes', 'nullable', 'string', 'max:100'],
            'status' => ['sometimes', 'required', 'string', Rule::enum(ServiceRequestStatus::class)],
            'priority' => ['sometimes', 'required', 'string', Rule::enum(ServiceRequestPriority::class)],
            'page' => ['sometimes', 'integer', 'min:1'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:25'],
        ];
    }
}
