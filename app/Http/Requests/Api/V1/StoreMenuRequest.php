<?php

namespace App\Http\Requests\Api\V1;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreMenuRequest extends FormRequest
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
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'max:40', 'alpha_dash:ascii', Rule::unique('menus', 'code')],
            'name' => ['required', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if (! $this->boolean('active', true) || ! $this->filled('name')) {
                return;
            }

            $exists = DB::table('menus')
                ->where('active', true)
                ->whereRaw('lower(trim(name)) = lower(trim(?))', [$this->string('name')->toString()])
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'Nama menu aktif sudah digunakan.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'code' => strtoupper(trim($this->string('code')->toString())),
            'name' => trim($this->string('name')->toString()),
        ]);
    }
}
