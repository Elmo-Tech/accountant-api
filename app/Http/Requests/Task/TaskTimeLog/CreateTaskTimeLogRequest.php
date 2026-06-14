<?php

namespace App\Http\Requests\Task\TaskTimeLog;

use App\Enums\Task\TaskTimeLogStatus;
use App\Enums\Task\TaskTimeLogType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\Rules\Enum;


class CreateTaskTimeLogRequest extends FormRequest
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
            // 'startAt' => 'required',
            // 'endAt' => 'nullable',
            'type' => ['required', new Enum(TaskTimeLogType::class)],
            'comment' => ['nullable'],
            'status' => ['required', new Enum(TaskTimeLogStatus::class)],
            'taskId' => 'required',
            'userId' => 'required',
            'currentTime' => 'required'
        ];

    }

    public function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(response()->json([
            'message' => $validator->errors()
        ], 401));
    }

}
