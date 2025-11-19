<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Image>
 */
class ImageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $mimeTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        $extensions = ['jpg', 'png', 'gif', 'webp'];

        $mimeType = fake()->randomElement($mimeTypes);
        $extension = $extensions[array_search($mimeType, $mimeTypes)];

        // Generate a filename
        $baseName = fake()->slug(3);
        $filename = $baseName . '_' . time() . '_' . fake()->bothify('??????????') . '.' . $extension;

        $path = 'images/' . $filename;
        $thumbnailPath = 'images/thumbnails/thumb_' . $filename;

        // Random dimensions
        $width = fake()->randomElement([800, 1024, 1200, 1600, 1920]);
        $height = fake()->randomElement([600, 768, 900, 1200, 1080]);

        // Random file size (between 100KB and 5MB)
        $size = fake()->numberBetween(102400, 5242880);

        return [
            'name' => fake()->sentence(3) . '.' . $extension,
            'title' => fake()->optional(0.8)->words(4, true),
            'filename' => $filename,
            'path' => $path,
            'thumbnail_path' => $thumbnailPath,
            'url' => $path,
            'alt_text' => fake()->optional(0.7)->sentence(5),
            'description' => fake()->optional(0.5)->paragraph(2),
            'mime_type' => $mimeType,
            'size' => $size,
            'width' => $width,
            'height' => $height,
            'uploaded_by' => \App\Models\User::factory(),
            'quantity_available' => fake()->numberBetween(0, 100),
            'max_order_quantity' => fake()->numberBetween(1, 50),
            'print_available' => fake()->boolean(60), // 60% chance of being true
            'edition_year' => fake()->numberBetween(2020, 2025),
        ];
    }
}
