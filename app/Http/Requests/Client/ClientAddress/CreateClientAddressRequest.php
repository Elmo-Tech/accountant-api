<?php

namespace App\Http\Requests\Client\ClientAddress;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class CreateClientAddressRequest extends FormRequest
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
            'address' => ['required'],
            'province' => ['nullable'],
            'cap' => ['nullable'],
            'city' => ['nullable'],
            'region' => ['nullable'],
            'latitude' => ['nullable'],
            'longitude' => ['nullable'],
            'note' => ['nullable'],
            'parameterValueId' => ['nullable'],
            'clientId' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
