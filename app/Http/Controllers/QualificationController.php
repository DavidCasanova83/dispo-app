<?php

namespace App\Http\Controllers;

use App\Models\Qualification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class QualificationController extends Controller
{
  /**
   * Display the qualification city selection page.
   */
  public function index(): View
  {
    return view('qualification.index', [
      'cities' => Qualification::getCities(),
    ]);
  }

  /**
   * Display the city-specific dashboard.
   */
  public function cityDashboard(string $city): View
  {
    // Validate city exists
    abort_if(!array_key_exists($city, Qualification::getCities()), 404);

    $stats = [
      'total' => Qualification::forCity($city)->count(),
      'completed' => Qualification::forCity($city)->completed()->count(),
      'incomplete' => Qualification::forCity($city)->incomplete()->count(),
    ];

    return view('qualification.city-dashboard', [
      'city' => $city,
      'cityName' => Qualification::getCities()[$city],
      'stats' => $stats,
    ]);
  }

  /**
   * Display the qualification form for a city.
   */
  public function form(string $city): View
  {
    // Validate city exists
    abort_if(!array_key_exists($city, Qualification::getCities()), 404);

    return view('qualification.form', [
      'city' => $city,
      'cityName' => Qualification::getCities()[$city],
    ]);
  }

  /**
   * Display the data management page for a city.
   */
  public function data(string $city): View
  {
    // Validate city exists
    abort_if(!array_key_exists($city, Qualification::getCities()), 404);

    return view('qualification.data', [
      'city' => $city,
      'cityName' => Qualification::getCities()[$city],
    ]);
  }

  /**
   * Save qualification data from the form.
   */
  public function save(Request $request)
  {
    $validated = $request->validate([
      'city' => 'required|string|in:annot,colmars-les-alpes,entrevaux,la-palud-sur-verdon,saint-andre-les-alpes',
      'country' => 'required|string',
      'department' => 'nullable|string',
      'email' => 'nullable|email',
      'consentNewsletter' => 'nullable|boolean',
      'consentDataProcessing' => 'nullable|boolean',
      'profile' => 'required|string',
      'ageGroups' => 'required|array',
      'specificRequests' => 'nullable|array',
      'generalRequests' => 'nullable|array',
      'otherRequest' => 'nullable|string',
    ]);

    $qualification = Qualification::create([
      'city' => $validated['city'],
      'user_id' => Auth::id(),
      'current_step' => 3,
      'form_data' => $validated,
      'completed' => true,
      'completed_at' => now(),
    ]);

    return response()->json([
      'success' => true,
      'message' => 'Qualification enregistrée avec succès',
      'qualification_id' => $qualification->id,
    ]);
  }
}
