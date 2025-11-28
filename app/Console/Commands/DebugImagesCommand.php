<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class DebugImagesCommand extends Command
{
    protected $signature = 'images:debug';
    protected $description = 'Debug images data';

    public function handle()
    {
        $images = Image::latest()->take(5)->get();

        foreach ($images as $image) {
            $this->info("=== Image: {$image->name} ===");
            $this->line("ID: {$image->id}");
            $this->line("Path: {$image->path}");
            $this->line("URL (stored): {$image->url}");
            $this->line("Thumbnail path: " . ($image->thumbnail_path ?? 'NULL'));
            $this->line("Generated URL: " . Storage::disk('public')->url($image->path));
            $this->line("Thumbnail exists: " . ($image->thumbnail_path && Storage::disk('public')->exists($image->thumbnail_path) ? 'YES' : 'NO'));
            $this->line("Image exists: " . (Storage::disk('public')->exists($image->path) ? 'YES' : 'NO'));
            $this->newLine();
        }

        return Command::SUCCESS;
    }
}
