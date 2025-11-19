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
        $images = Image::with('uploader:id,name')
            ->select([
                'id',
                'name',
                'title',
                'filename',
                'path',
                'thumbnail_path',
                'alt_text',
                'description',
                'mime_type',
                'size',
                'width',
                'height',
                'uploaded_by',
                'quantity_available',
                'max_order_quantity',
                'print_available',
                'edition_year',
                'created_at',
                'updated_at'
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
                    'alt_text' => $image->alt_text,
                    'description' => $image->description,
                    'mime_type' => $image->mime_type,
                    'size' => $image->size,
                    'size_formatted' => $image->formattedSize(),
                    'dimensions' => [
                        'width' => $image->width,
                        'height' => $image->height,
                    ],
                    'product' => [
                        'quantity_available' => $image->quantity_available,
                        'max_order_quantity' => $image->max_order_quantity,
                        'print_available' => $image->print_available,
                        'edition_year' => $image->edition_year,
                    ],
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
        $image = Image::with('uploader:id,name')->findOrFail($id);

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
                'mime_type' => $image->mime_type,
                'size' => $image->size,
                'size_formatted' => $image->formattedSize(),
                'dimensions' => [
                    'width' => $image->width,
                    'height' => $image->height,
                ],
                'product' => [
                    'quantity_available' => $image->quantity_available,
                    'max_order_quantity' => $image->max_order_quantity,
                    'print_available' => $image->print_available,
                    'edition_year' => $image->edition_year,
                ],
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
