<?php

namespace App\Http\Requests\Task;

use App\Enums\Task\TaskStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;

class CreateTaskRequest extends FormRequest
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
            'title' => ['nullable'],
            'description' => ['nullable'],
            'status' => ['required', new Enum(TaskStatus::class)],
            'clientId' => ['required'],
            'userId' => ['required'],
            'serviceCategoryId' => ['required'],
            'connectionTypeId' => ['nullable'],
            'startDate' => ['nullable'],
            'endDate' => ['nullable']
        ];
    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
