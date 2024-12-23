<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserDataRegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules()
    {
        return [
            'firstname' => 'required',
            'lastname' => 'required',
            'ID' => 'required',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:6',
        ];
    }

    public function messages()
    {
        return [
            'firstname.required' => 'First Name required',
            'lastname' => 'Last Name required',
            'ID' => 'National ID numberrequired',
            'email' => 'Email required',
            'password' => 'Password required',
        ];
    }
}
