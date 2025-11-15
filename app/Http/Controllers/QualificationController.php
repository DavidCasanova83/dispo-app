<?php

namespace App\Http\Controllers;

use App\Models\Qualification;
use App\Exports\QualificationsExport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Carbon\Carbon;

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
   * Display the edit page for a qualification.
   */
  public function edit(string $city, int $id): View
  {
    // Validate city exists
    abort_if(!array_key_exists($city, Qualification::getCities()), 404);

    // Load qualification with user relation
    $qualification = Qualification::with('user')->findOrFail($id);

    // Verify qualification belongs to the correct city
    abort_if($qualification->city !== $city, 404);

    return view('qualification.edit', [
      'city' => $city,
      'cityName' => Qualification::getCities()[$city],
      'qualification' => $qualification,
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

  /**
   * Export qualifications to Excel
   */
  public function export(Request $request)
  {
    // Construire les filtres depuis la requête
    $filters = [];

    // Villes
    if ($request->has('cities')) {
      $cities = $request->input('cities');
      // Retirer "all" si présent
      $filters['cities'] = array_filter($cities, fn($city) => $city !== 'all');
    }

    // Période
    $dateRange = $request->input('dateRange', 'all');

    if ($dateRange !== 'all' && $dateRange !== 'custom') {
      $endDate = now();
      $startDate = match($dateRange) {
        '7d' => now()->subDays(7),
        '30d' => now()->subDays(30),
        '3m' => now()->subMonths(3),
        '6m' => now()->subMonths(6),
        '1y' => now()->subYear(),
        default => null,
      };

      if ($startDate) {
        $filters['startDate'] = $startDate->format('Y-m-d');
        $filters['endDate'] = $endDate->format('Y-m-d');
      }
    } elseif ($dateRange === 'custom') {
      if ($request->has('startDate')) {
        $filters['startDate'] = $request->input('startDate');
      }
      if ($request->has('endDate')) {
        $filters['endDate'] = $request->input('endDate');
      }
    }

    // Statut
    if ($request->has('status')) {
      $filters['status'] = $request->input('status');
    }

    // Nom du fichier avec timestamp
    $filename = 'qualifications-' . now()->format('Y-m-d_His') . '.xlsx';

    return Excel::download(new QualificationsExport($filters), $filename);
  }
}
