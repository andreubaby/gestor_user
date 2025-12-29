<?php

namespace App\Http\Requests\Auth;
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'email'    => ['required','string','email:rfc,dns','max:255'],
            'password' => ['required','string','min:12','max:255'],
            'remember' => ['sometimes','boolean'],
        ];
    }

    public function messages(): array
    {
        return [
            'password.min' => 'Credenciales incorrectas.',
            'email.email'  => 'Credenciales incorrectas.',
        ];
    }
}
