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
            "phone" => "required|string|unique:users",
            "first_name" => "required|string",
            "last_name" => "required|string",
            "gender" => "required|string",
            "email" => "required||max:255|unique:users",
            "marital_status" => "required|string",
            "religion" => "required|string",
            "nationality" => "required|string",
            "state" => "required|string",
            "state_of_residence" => "required|string",
            "address_of_residence" => "required|string",
        ];
    }

    public function messages()
    {
        // use trans instead on Lang
        return [
            'password.min' => 'Password must be greater then 8 Characters'
        ];
    }
}
