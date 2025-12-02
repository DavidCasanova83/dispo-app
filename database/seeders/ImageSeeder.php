<?php

namespace Database\Seeders;

use App\Models\Image;
use App\Models\User;
use Illuminate\Database\Seeder;

class ImageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::first();

        if (!$user) {
            $this->command->error('No users found in database. Run DatabaseSeeder first.');
            return;
        }

        // Only create images if less than 20 exist (idempotent)
        $existingCount = Image::count();
        if ($existingCount >= 20) {
            $this->command->info("$existingCount images already exist. Skipping.");
            return;
        }

        $toCreate = 20 - $existingCount;

        $this->command->info("Creating $toCreate fake images...");

        Image::factory()
            ->count($toCreate)
            ->create([
                'uploaded_by' => $user->id,
            ]);

        $this->command->info("$toCreate fake images created successfully!");
        $this->command->warn('Note: These are database records only. No actual image files were created.');
    }
}
