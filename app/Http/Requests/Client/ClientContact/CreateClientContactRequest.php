<?php

namespace App\Http\Requests\Client\ClientContact;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;


class CreateClientContactRequest extends FormRequest
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
            'firstName' => 'nullable|required_without:lastName',
            'lastName'  => 'nullable|required_without:firstName',
            'phone' => ['nullable'],
            'prefix' => ['nullable'],
            'email' => ['nullable'],
            'note' => ['nullable'],
            'parameterValueId' => ['nullable'],
            'clientId' => ['nullable'],
            'cf' => ['nullable'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
