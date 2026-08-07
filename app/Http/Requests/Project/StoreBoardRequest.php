<?php

namespace App\Http\Requests\Project;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBoardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'workspace_id' => [
                'required',
                'integer',
                Rule::exists('workspaces', 'id')->where(
                    fn ($query) => $query->where('company_id', $user instanceof User ? $user->company_id : 0),
                ),
            ],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:1000'],
            'background' => ['nullable', 'string', 'regex:/^#[0-9a-fA-F]{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'workspace_id.exists' => 'Choose a workspace in your company.',
            'name.required' => 'Enter a name for the board.',
            'background.regex' => 'Board background must be a six-digit hex value.',
        ];
    }
}
