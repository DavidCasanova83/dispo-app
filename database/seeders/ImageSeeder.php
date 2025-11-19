<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Creating 20 fake images...');

        // Get the first user or create one if none exists
        $user = User::first();

        if (!$user) {
            $this->command->error('No users found in database. Please create a user first.');
            return;
        }

        // Create 20 fake images
        Image::factory()
            ->count(20)
            ->create([
                'uploaded_by' => $user->id,
            ]);

        $this->command->info('20 fake images created successfully!');
        $this->command->warn('Note: These are database records only. No actual image files were created.');
    }
}
