<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\AccommodationPublicResource;
use App\Models\Accommodation;
use Illuminate\Http\JsonResponse;

class AccommodationApiController extends Controller
{
    /**
     * Get all accommodations as public JSON array.
     */
    public function index(): JsonResponse
    {
        $accommodations = Accommodation::all();

        $data = $accommodations->map(function ($accommodation) {
            return (new AccommodationPublicResource($accommodation))->resolve();
        });

        return response()->json($data->values()->all());
    }
}
