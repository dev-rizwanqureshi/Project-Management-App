<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreInvitationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() instanceof User;
    }

    /**
     * @return array<string, list<mixed>>
     */
    public function rules(): array
    {
        $user = $this->user();

        return [
            'email' => ['required', 'email', 'max:255'],
            'scope' => ['required', Rule::in(['company', 'workspace', 'board'])],
            'workspace_id' => [
                'nullable',
                'integer',
                'required_if:scope,workspace,board',
                Rule::exists('workspaces', 'id')->where(
                    fn ($query) => $query->where('company_id', $user instanceof User ? $user->company_id : 0),
                ),
            ],
            'board_id' => [
                'nullable',
                'integer',
                'required_if:scope,board',
                Rule::exists('boards', 'id')->where(
                    fn ($query) => $query
                        ->where('workspace_id', (int) $this->input('workspace_id'))
                        ->where('is_restricted', false)
                        ->where('is_archived', false),
                ),
            ],
            'role' => ['required', Rule::in(['admin', 'member', 'guest'])],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($this->input('scope') !== 'company' && $this->input('role') === 'admin') {
                $validator->errors()->add('role', 'Workspace and board invitations can only be Member or Viewer.');
            }
        });
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'email.required' => 'Enter the person’s email address.',
            'scope.required' => 'Choose where the person should be invited.',
            'workspace_id.required_if' => 'Choose a workspace.',
            'workspace_id.exists' => 'Choose a workspace in your company.',
            'board_id.required_if' => 'Choose a board.',
            'board_id.exists' => 'Choose a valid board.',
        ];
    }
}
