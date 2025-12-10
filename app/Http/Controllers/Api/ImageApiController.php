<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Image;
use Illuminate\Http\JsonResponse;

class ImageApiController extends Controller
{
    /**
     * Retourne toutes les images en JSON
     */
    public function index(): JsonResponse
    {
        $images = Image::with([
                'uploader:id,name',
                'category:id,name',
                'author:id,name',
                'sector:id,name'
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
                    'uploader' => [
                        'id' => $image->uploader->id ?? null,
                        'name' => $image->uploader->name ?? null,
                    ],
                    'created_at' => $image->created_at->toIso8601String(),
                    'updated_at' => $image->updated_at->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'total' => $images->count(),
            'data' => $images,
        ]);
    }

    /**
     * Retourne une image spécifique
     */
    public function show($id): JsonResponse
    {
        $image = Image::with([
            'uploader:id,name',
            'category:id,name',
            'author:id,name',
            'sector:id,name'
        ])->findOrFail($id);

        return response()->json([
            'success' => true,
            'data' => [
                'id' => $image->id,
                'name' => $image->name,
                'title' => $image->title,
                'filename' => $image->filename,
                'url' => asset('storage/' . $image->path),
                'thumbnail_url' => $image->thumbnail_path
                    ? asset('storage/' . $image->thumbnail_path)
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
                'uploader' => [
                    'id' => $image->uploader->id ?? null,
                    'name' => $image->uploader->name ?? null,
                ],
                'created_at' => $image->created_at->toIso8601String(),
                'updated_at' => $image->updated_at->toIso8601String(),
            ],
        ]);
    }
}
