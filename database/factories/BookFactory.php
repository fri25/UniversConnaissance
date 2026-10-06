<?php

namespace Database\Factories;

use App\Models\Author;
use App\Models\Book;
use App\Models\Category;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Book>
 */
class BookFactory extends Factory
{
    public function definition(): array
    {
        $title = rtrim($this->faker->unique()->sentence(3), '.');

        return [
            'title' => $title,
            'slug' => Str::slug($title),
            'summary' => $this->faker->paragraph(),
            'publisher' => $this->faker->company(),
            'published_year' => $this->faker->numberBetween(1990, 2025),
            'language' => 'fr',
            'pages' => $this->faker->numberBetween(80, 600),
            'format' => 'pdf',
            'price' => 2500,
            'old_price' => null,
            'file_path' => 'ebooks/'.Str::slug($title).'.pdf',
            'file_size' => 12000,
            'is_active' => true,
        ];
    }

    public function configure(): static
    {
        return $this->afterCreating(function (Book $book) {
            if ($book->authors()->doesntExist()) {
                $book->authors()->attach(Author::factory()->create());
            }
            if ($book->categories()->doesntExist()) {
                $book->categories()->attach(Category::factory()->create());
            }
        });
    }

    public function inactive(): static
    {
        return $this->state(['is_active' => false]);
    }

    public function onPromo(int $price = 2000, int $oldPrice = 4000): static
    {
        return $this->state(['price' => $price, 'old_price' => $oldPrice]);
    }
}
