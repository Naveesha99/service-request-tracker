<?php

namespace App\Http\Requests;

use App\Enums\ServiceRequestPriority;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreServiceRequestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'min:3', 'max:100'],
            'description' => ['required', 'string', 'min:10', 'max:500'],
            'priority' => ['required', 'string', Rule::enum(ServiceRequestPriority::class)],
        ];
    }
}
