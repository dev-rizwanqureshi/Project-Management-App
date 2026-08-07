<?php

namespace App\Http\Requests\Project;

use App\Models\Board;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class MoveCardRequest extends FormRequest
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
            'position' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'list_id.exists' => 'Choose an active column on this board.',
            'position.min' => 'Ticket position must be at least 1.',
        ];
    }
}
