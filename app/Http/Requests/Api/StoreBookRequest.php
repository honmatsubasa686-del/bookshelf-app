<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class StoreBookRequest extends FormRequest
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
            'user_id' => 'required|exists:users,id',
            'title' => 'required|string|max:255',
            'author' => 'required|string|max:120',
            'isbn' => 'nullable|string|digits:13|unique:books,isbn',
            'published_date' => 'required|date',
            'description' => 'nullable|string',
            'genres' => 'required|array|min:1',
            'genres.*' => 'required|exists:genres,id',
        ];
    }

    public function messages(): array
    {
        return [
            'user_id.required' => 'ユーザーIDを入力してください。.',
            'user_id.exists' => '指定されたユーザーが存在しません。',
            'title.required' => 'タイトルを入力してください。',
            'title.string' => 'タイトルは文字列で入力してください。',
            'title.max' => 'タイトルは255文字以内で入力してください。',
            'author.required' => '著者を入力してください。',
            'author.string' => '著者は文字列で入力してください。',
            'author.max' => '著者は120文字以内で入力してください。',
            'isbn.digits' => 'ISBNは13桁の数字で入力してください。',
            'isbn.unique' => 'そのISBNは既に使用されています。',
            'published_date.required' => '出版日を入力してください。',
            'published_date.date' => '出版日は日付形式で入力してください。',
            'description.string' => '説明は文字列で入力してください。',
            'genres.required' => 'ジャンルを１つ以上選択してください。',
            'genres.array' => 'ジャンルは配列で指定してください。',
            'genres.min' => 'ジャンルを１つ以上選択してください。',
            'genres.*.exists' => '選択されたジャンルが存在しません。',
        ];
    }
}
