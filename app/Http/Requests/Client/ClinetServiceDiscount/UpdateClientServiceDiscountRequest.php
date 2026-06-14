<?php

namespace App\Http\Requests\Client\ClinetServiceDiscount;

use Illuminate\Validation\Rules\Enum;
use App\Enums\Client\ClientShowStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use App\Enums\Client\ClientServiceDiscountType;
use App\Enums\Client\ClientServiceDiscountStatus;
use Illuminate\Http\Exceptions\HttpResponseException;

class UpdateClientServiceDiscountRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'clientServiceDiscountId' => ['required'],
            'serviceCategoryIds' => ['required'],
            'discount' => ['required'],
            'category' =>['nullable'],
            'type' => ['required', new Enum(ClientServiceDiscountType::class)],
            'isActive' => ['required', new Enum(ClientServiceDiscountStatus::class)],
            'isShow' => ['nullable', new Enum(ClientShowStatus::class)]
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
