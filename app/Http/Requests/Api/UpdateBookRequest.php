<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateBookRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $book = $this->route('book');

        return $book && $book->user_id === $this->user()->id;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:120',
            'isbn' => [
                'nullable',
                'string',
                'digits:13',
                Rule::unique('books', 'isbn')->ignore($this->route('book')),
            ],
            'published_date' => 'nullable|date',
            'description' => 'nullable|string',
            'genres' => 'required|array|min:1',
            'genres.*' => 'required|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者を入力してください。',
            'author.string' => '著者は文字列で入力してください。',
            'author.max' => '著者は120文字以内で入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'そのISBNは既に使用されています。',
            'published_date.date' => '出版日は日付形式で入力してください。',
            'description.string' => '説明は文字列で入力してください。',
            'genres.required' => 'ジャンルを１つ以上選択してください。',
            'genres.array' => 'ジャンルは配列で指定してください。',
            'genres.min' => 'ジャンルを１つ以上選択してください。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
