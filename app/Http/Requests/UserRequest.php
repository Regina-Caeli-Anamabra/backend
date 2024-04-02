<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
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
            "username" => "required|string|unique:users",
            "phone" => "required|int|unique:patients",
            "first_name" => "required|string",
            "last_name" => "required|string",
            "gender" => "required|string",
            "password" => [
                'required',
                'min:8',
                'regex:/[a-z]/',      // must contain at least one lowercase letter
                'regex:/[A-Z]/',      // must contain at least one uppercase letter
                'regex:/[0-9]/',      // must contain at least one digit
                'regex:/[@$!%*#?&.]/'
            ],
        ];
    }

    public function messages()
    {
        // use trans instead on Lang
        return [
            'password.min' => 'Password must be greater then 8 Characters',
            'password.regex' => 'Password must contain a capital letter, a number and a special character'
        ];
    }
}
