<?php

namespace App\Http\Requests;

use App\Enums\ReadingPlanStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreReadingPlanRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'book_id' => [
                'required',
                'exists:books,id',
                Rule::unique('reading_plans', 'book_id')
                    ->where(fn ($query) => $query
                        ->where('user_id', auth()->id())
                        ->where('status', ReadingPlanStatus::Planned->value)
                    ),
            ],
            'due_date' => [
                'required',
                'date',
                'after_or_equal:today',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'book_id.required' => '書籍を選択してください。',
            'book_id.exists' => '選択された書籍が存在しません。',
            'book_id.unique' => 'この書籍はすでに未読の読書計画に登録されています。',
            'due_date.required' => '読書期限日を入力してください。',
            'due_date.date' => '読書期限日は日付形式で入力してください。',
            'due_date.after_or_equal' => '読書期限日は今日以降の日付を指定してください。',
        ];
    }
}
