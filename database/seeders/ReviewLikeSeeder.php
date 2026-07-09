<?php

namespace Database\Seeders;

use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Seeder;

class ReviewLikeSeeder extends Seeder
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

        $reviews = Review::with('user')
            ->whereHas('book', function ($query) {
                $query->whereIn('isbn', [
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
                ]);
            })
            ->get();

        if ($users->count() < 5 || $reviews->count() < 32) {
            return;
        }

        foreach ($reviews as $index => $review) {
            $likeUsers = $users
                ->reject(function ($user) use ($review) {
                    return $user->id === $review->user_id;
                })
                ->values()
                ->take($index % 4)
                ->pluck('id');

            $review->likedByUsers()->syncWithoutDetaching($likeUsers);
        }
    }
}
