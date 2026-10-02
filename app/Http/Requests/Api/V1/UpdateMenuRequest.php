<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Menu;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateMenuRequest extends FormRequest
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
        $menu = $this->route('menu');

        return [
            'code' => [
                'sometimes',
                'required',
                'string',
                'max:40',
                'alpha_dash:ascii',
                Rule::unique('menus', 'code')->ignore($menu instanceof Menu ? $menu->id : null),
            ],
            'name' => ['sometimes', 'required', 'string', 'max:120'],
            'active' => ['sometimes', 'boolean'],
        ];
    }

    /**
     * @return array<int, callable(Validator): void>
     */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $menu = $this->route('menu');

            if (! $menu instanceof Menu) {
                return;
            }

            $isActive = $this->has('active') ? $this->boolean('active') : $menu->active;
            $name = $this->has('name') ? $this->string('name')->toString() : $menu->name;

            if (! $isActive) {
                return;
            }

            $exists = DB::table('menus')
                ->where('id', '!=', $menu->id)
                ->where('active', true)
                ->whereRaw('lower(trim(name)) = lower(trim(?))', [$name])
                ->exists();

            if ($exists) {
                $validator->errors()->add('name', 'Nama menu aktif sudah digunakan.');
            }
        }];
    }

    protected function prepareForValidation(): void
    {
        $input = [];

        if ($this->has('code')) {
            $input['code'] = strtoupper(trim($this->string('code')->toString()));
        }

        if ($this->has('name')) {
            $input['name'] = trim($this->string('name')->toString());
        }

        $this->merge($input);
    }
}
