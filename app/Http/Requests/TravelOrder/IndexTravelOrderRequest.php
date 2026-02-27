<?php

namespace App\Http\Requests\TravelOrder;

use App\Enums\TravelOrderStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class IndexTravelOrderRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['sometimes', Rule::in(array_column(TravelOrderStatus::cases(), 'value'))],
            'destination' => ['sometimes', 'string', 'max:255'],
            'departure_from' => ['sometimes', 'date'],
            'departure_to' => ['sometimes', 'date', 'after_or_equal:departure_from'],
            'return_from' => ['sometimes', 'date'],
            'return_to' => ['sometimes', 'date', 'after_or_equal:return_from'],
        ];
    }
}
