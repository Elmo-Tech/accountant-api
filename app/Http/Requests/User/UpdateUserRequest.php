<?php

namespace App\Http\Requests\User;

use App\Enums\User\UserStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
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
            'userId' => 'required',
            'username'=> ['required', "unique:users,username,{$this->userId}"],
            'firstName' => 'required',
            'lastName' => 'required',
            'email'=> ['required', "unique:users,email,{$this->userId}"],
            'phone' => 'nullable',
            'address' => 'nullable',
            'status' => ['required', new Enum(UserStatus::class)],
            'password'=> [
                'sometimes',
                'nullable',
                Password::min(8)->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
            'roleId'=> 'required',
            'avatar' => ["sometimes", "nullable","image", "mimes:jpeg,jpg,png,gif", "max:2048"],
            'perHourRate' => ['required'],
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
