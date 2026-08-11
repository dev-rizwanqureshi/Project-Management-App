<?php

namespace App\Http\Requests\Project;

use App\Models\Board;
use App\Models\TaskList;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertTaskListRequest extends FormRequest
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
        $taskList = $this->route('taskList');
        $taskListId = $taskList instanceof TaskList ? $taskList->id : (int) $taskList;

        return [
            'name' => [
                'required',
                'string',
                'max:80',
                Rule::unique('lists', 'name')
                    ->where(fn ($query) => $query
                        ->where('board_id', $boardId)
                        ->where('is_archived', false))
                    ->ignore($taskListId),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.required' => 'Enter a name for the section.',
            'name.unique' => 'This board already has a section with that name.',
        ];
    }
}
