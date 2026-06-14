<?php

namespace App\Http\Requests\Client;

use App\Enums\Client\AddableToBulk;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Http\Exceptions\HttpResponseException;


class CreateClientRequest extends FormRequest
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
            'iva' => ['required'],
            'ragioneSociale' => ['required'],
            'cf' => ['required'],
            'note' => ['nullable'],
            'phone' => ['nullable'],
            'email' => ['nullable'],
            'hoursPerMonth' => ['nullable'],
            'price' => ['nullable'],
            'monthlyPrice' => ['nullable'],
            'addresses' => ['nullable'],
            'contacts' => ['nullable'],
            'paymentTypeId' => ['nullable'] ,
            'payStepsId' => ['nullable'],
            'paymentTypeTwoId' => ['nullable'],
            'addableToBulkInvoice'=>['nullable',new Enum(AddableToBulk::class) ],
            'allowedDaysToPay'=>['nullable'],
            'iban' => ['nullable'],
            'abi' => ['nullable'],
            'cab' => ['nullable'],
            'discounts' => ['nullable'],
            'isCompany' => ['nullable'],
            'bankAccounts' => ['nullable'],
            'totalTax' => ['nullable'],
            'totalTaxDescription' => ['nullable'],
            'payInstallments' => ['nullable'],
            'emailF24' => ['nullable'],
            'sdi' => ['nullable'],
            'proforma' => ['nullable', 'boolean'],
            'limitDecreto' => ['nullable', 'numeric'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
