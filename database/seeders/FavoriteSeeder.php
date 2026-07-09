<?php

namespace Database\Seeders;

use App\Models\Book;
use App\Models\User;
use Illuminate\Database\Seeder;

class FavoriteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $users = User::whereIn('email', [
            'yamada@example.com',
            'suzuki@example.com',
            'tanaka@example.com',
            'sato@example.com',
            'takahashi@example.com',
        ])->get()->keyBy('email');

        $books = Book::whereIn('isbn', [
            '9784101010014',
            '9784422100524',
            '9784873115658',
            '9784863940246',
            '9784101010021',
            '9784309226712',
            '9784048930598',
            '9784478025819',
            '9784163902302',
            '9784822289607',
            '9784822251468',
        ])->get()->keyBy('isbn');

        if ($users->count() < 5 || $books->count() < 11) {
            return;
        }

        $favorites = [
            'yamada@example.com' => [
                '9784101010014',
                '9784422100524',
                '9784873115658',
                '9784309226712',
            ],
            'suzuki@example.com' => [
                '9784863940246',
                '9784478025819',
                '9784163902302',
            ],
            'tanaka@example.com' => [
                '9784101010021',
                '9784048930598',
                '9784822289607',
                '9784822251468',
            ],
            'sato@example.com' => [
                '9784422100524',
                '9784309226712',
                '9784478025819',
                '9784822289607',
                '9784822251468',
            ],
            'takahashi@example.com' => [
                '9784101010014',
                '9784873115658',
                '9784863940246',
                '9784163902302',
            ],
        ];

        foreach ($favorites as $email => $isbnList) {
            $bookIds = collect($isbnList)->map(function ($isbn) use ($books) {
                return $books[$isbn]->id;
            });

            $users[$email]->favoriteBooks()->syncWithoutDetaching($bookIds);
        }
    }
}
