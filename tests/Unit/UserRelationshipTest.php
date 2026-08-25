<?php

namespace Tests\Unit;

use App\Models\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Tests\TestCase;

class UserRelationshipTest extends TestCase
{
    /**
     * A basic unit test example.
     */
    public function test_user_has_many_reviews(): void
    {
        $user = new User;

        $relation = $user->reviews();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function test_user_has_many_reading_plans(): void
    {
        $user = new User;

        $relation = $user->readingPlans();

        $this->assertInstanceOf(HasMany::class, $relation);
    }

    public function test_user_belongs_to_many_favorite_books(): void
    {
        $user = new User;

        $relation = $user->favoriteBooks();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }

    public function test_user_belongs_to_many_liked_reviews(): void
    {
        $user = new User;

        $relation = $user->likedReviews();

        $this->assertInstanceOf(BelongsToMany::class, $relation);
    }
}
