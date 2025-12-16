<?php

namespace App\Console\Commands;

use App\Models\Image;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class GenerateImagesJsonCommand extends Command
{
    protected $signature = 'images:generate-json';
    protected $description = 'Generate a static JSON file with all images data';

    public function handle()
    {
        $this->info('Generating images JSON file...');

        $images = Image::with([
                'uploader:id,name',
                'category:id,name',
                'author:id,name',
                'sector:id,name',
                'responsable:id,name'
            ])
            ->latest()
            ->get()
            ->map(function ($image) {
                return [
                    'id' => $image->id,
                    'name' => $image->name,
                    'title' => $image->title,
                    'filename' => $image->filename,
                    'url' => asset('storage/' . $image->path),
                    'thumbnail_url' => $image->thumbnail_path
                        ? asset('storage/' . $image->thumbnail_path)
                        : null,
                    'pdf_url' => $image->pdf_path
                        ? asset('storage/' . $image->pdf_path)
                        : null,
                    'alt_text' => $image->alt_text,
                    'description' => $image->description,
                    'link_url' => $image->link_url,
                    'link_text' => $image->link_text,
                    'calameo_link_url' => $image->calameo_link_url,
                    'calameo_link_text' => $image->calameo_link_text,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                    'size_formatted' => $image->formattedSize(),
                    'dimensions' => [
                        'width' => $image->width,
                        'height' => $image->height,
                    ],
                    'display_order' => $image->display_order,
                    'product' => [
                        'quantity_available' => $image->quantity_available,
                        'max_order_quantity' => $image->max_order_quantity,
                        'print_available' => $image->print_available,
                        'edition_year' => $image->edition_year,
                    ],
                    'category' => $image->category ? [
                        'id' => $image->category->id,
                        'name' => $image->category->name,
                    ] : null,
                    'author' => $image->author ? [
                        'id' => $image->author->id,
                        'name' => $image->author->name,
                    ] : null,
                    'sector' => $image->sector ? [
                        'id' => $image->sector->id,
                        'name' => $image->sector->name,
                    ] : null,
                    'responsable' => $image->responsable ? [
                        'id' => $image->responsable->id,
                        'name' => $image->responsable->name,
                    ] : null,
                    'uploader' => [
                        'id' => $image->uploader->id ?? null,
                        'name' => $image->uploader->name ?? null,
                    ],
                    'created_at' => $image->created_at->toIso8601String(),
                    'updated_at' => $image->updated_at->toIso8601String(),
                ];
            });

        $data = [
            'success' => true,
            'total' => $images->count(),
            'generated_at' => now()->toIso8601String(),
            'data' => $images,
        ];

        $jsonContent = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        // Créer le fichier dans storage/app/public/api/ (accessible via /storage/api/)
        $directory = storage_path('app/public/api');
        if (!File::exists($directory)) {
            File::makeDirectory($directory, 0755, true);
        }

        $filePath = $directory . '/images.json';
        File::put($filePath, $jsonContent);

        $this->info("JSON file generated successfully at: {$filePath}");
        $this->line("Public URL: " . asset('storage/api/images.json'));
        $this->line("Total images: {$images->count()}");

        return Command::SUCCESS;
    }
}
