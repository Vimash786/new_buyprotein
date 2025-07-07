<?php

namespace Database\Factories;

use App\Models\Blog;
use App\Models\User;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Blog>
 */
class BlogFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Blog::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = $this->faker->sentence(6, true);
        $slug = \Illuminate\Support\Str::slug($title);
        
        return [
            'title' => $title,
            'slug' => $slug,
            'excerpt' => $this->faker->paragraph(3),
            'content' => $this->faker->paragraphs(8, true),
            'featured_image' => 'blogs/' . $this->faker->image('public/storage/blogs', 800, 600, null, false),
            'meta_title' => $this->faker->sentence(8, true),
            'meta_description' => $this->faker->paragraph(2),
            'tags' => $this->faker->words(5),
            'is_featured' => $this->faker->boolean(20), // 20% chance of being featured
            'status' => $this->faker->randomElement(['draft', 'published', 'archived']),
            'published_at' => $this->faker->optional(0.8)->dateTimeBetween('-1 year', 'now'),
            'views_count' => $this->faker->numberBetween(0, 10000),
            'likes_count' => $this->faker->numberBetween(0, 500),
            'comments_count' => $this->faker->numberBetween(0, 100),
            'author_id' => User::factory(),
            'category_id' => Category::factory(),
        ];
    }

    /**
     * Indicate that the blog is published.
     */
    public function published(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'published',
            'published_at' => $this->faker->dateTimeBetween('-6 months', 'now'),
        ]);
    }

    /**
     * Indicate that the blog is draft.
     */
    public function draft(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'draft',
            'published_at' => null,
        ]);
    }

    /**
     * Indicate that the blog is featured.
     */
    public function featured(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_featured' => true,
        ]);
    }

    /**
     * Indicate that the blog is archived.
     */
    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => 'archived',
        ]);
    }
}
