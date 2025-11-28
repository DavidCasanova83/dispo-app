<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;

class FixImageUrlsCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:fix-urls';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix image URLs to use paths instead of full URLs';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing image URLs...');

        $images = Image::all();
        $fixed = 0;

        foreach ($images as $image) {
            // Si l'URL contient /storage/ ou http://, on la remplace par le path
            if (str_contains($image->url, '/storage/') || str_contains($image->url, 'http://') || str_contains($image->url, 'https://')) {
                // Extraire le chemin depuis l'URL
                // Par exemple: /storage/images/file.jpg => images/file.jpg
                // Ou: http://localhost:8000/storage/images/file.jpg => images/file.jpg
                $path = $image->path; // On utilise le path qui existe déjà

                $image->update(['url' => $path]);
                $fixed++;

                $this->line("Fixed: {$image->name}");
            }
        }

        $this->info("Fixed {$fixed} image(s).");

        return Command::SUCCESS;
    }
}
