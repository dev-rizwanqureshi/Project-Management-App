<?php

namespace App\Http\Requests\Project;

use App\Models\Board;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCardRequest extends FormRequest
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
        $board = $this->route('board');
        $boardId = $board instanceof Board ? $board->id : (int) $board;
        $user = $this->user();

        return [
            'list_id' => [
                'required',
                'integer',
                Rule::exists('lists', 'id')->where(
                    fn ($query) => $query
                        ->where('board_id', $boardId)
                        ->where('is_archived', false),
                ),
            ],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'due_date' => ['nullable', 'date'],
            'assignee_ids' => ['sometimes', 'array'],
            'assignee_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('users', 'id')->where(
                    fn ($query) => $query
                        ->where('company_id', $user instanceof User ? $user->company_id : 0)
                        ->where('is_restricted', false),
                ),
            ],
            'label_ids' => ['sometimes', 'array'],
            'label_ids.*' => [
                'integer',
                'distinct',
                Rule::exists('labels', 'id')->where('board_id', $boardId),
            ],
            'attachments' => ['sometimes', 'array', 'max:5'],
            'attachments.*' => [
                'file',
                'max:10240',
                'mimes:jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,txt,zip',
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'list_id.exists' => 'Choose an active column on this board.',
            'title.required' => 'Enter a title for the ticket.',
            'assignee_ids.*.exists' => 'Every assignee must belong to your company.',
            'label_ids.*.exists' => 'Every label must belong to this board.',
        ];
    }
}
