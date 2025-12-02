<?php

namespace Database\Seeders;

use App\Models\Author;
use App\Models\Category;
use App\Models\Sector;
use Illuminate\Database\Seeder;

class CategoryAuthorSectorSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Catégories initiales
        Category::firstOrCreate(['name' => 'Randonnée']);

        // Auteurs initiaux
        Author::firstOrCreate(['name' => 'Verdon Tourisme']);

        // Secteurs initiaux
        Sector::firstOrCreate(['name' => 'Annot']);
    }
}
