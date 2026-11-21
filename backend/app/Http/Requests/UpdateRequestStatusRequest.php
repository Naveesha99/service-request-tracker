<?php

namespace App\Http\Requests;

use App\Enums\ServiceRequestStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateRequestStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::enum(ServiceRequestStatus::class)],
            'expected_version' => ['required', 'integer', 'min:1'],
            'remark' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
