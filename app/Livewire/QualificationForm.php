<?php

namespace App\Livewire;

use App\Models\Qualification;
use App\Services\FrenchGeographyService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;
use Livewire\Attributes\Layout;
use Livewire\Attributes\On;

class QualificationForm extends Component
{
    // Informations de base
    public $city;
    public $cityName;

    // Étape courante
    public $currentStep = 1;

    // Étape 1 : Informations générales
    public $country = 'France';
    public $otherCountry = '';
    public $departments = [];
    public $departmentUnknown = false;
    public $email = '';
    public $consentNewsletter = false;
    public $consentDataProcessing = false;

    // Étape 2 : Profil
    public $profile = '';
    public $profileUnknown = false;
    public $ageGroups = [];
    public $ageUnknown = false;

    // Étape 3 : Demandes
    public $addedDate;
    public $contactMethod = 'Direct';
    public $specificRequests = [];
    public $otherSpecificRequests = [];
    public $generalRequests = [];
    public $otherRequest = '';

    // État UI pour dropdown "Autre" des demandes spécifiques
    public $showOtherSpecificDropdown = false;
    public $otherSpecificSearchQuery = '';

    // État UI
    public $showSuccessMessage = false;
    public $qualificationId = null;

    // Options de formulaire
    public $specificOptions = [];
    public $generalOptions = [];

    // Service
    protected $geographyService;

    /**
     * Boot method to inject service dependency
     */
    public function boot(FrenchGeographyService $service)
    {
        $this->geographyService = $service;
    }

    public function mount($city, $cityName)
    {
        $this->city = $city;
        $this->cityName = $cityName;

        // Initialiser la date d'ajout avec la date du jour
        $this->addedDate = now()->format('Y-m-d');

        // Initialiser les options
        $this->initializeOptions();

        // Charger le brouillon s'il existe
        $this->loadDraft();
    }

    protected function initializeOptions()
    {
        // Options spécifiques par ville
        $this->specificOptions = [
            'annot' => ['Escalade', 'Train à Vapeur', 'Grès d\'Annot'],
            'colmars-les-alpes' => ['Lac d\'Allos', 'Cascade de la Lance', 'Maison Musée'],
            'entrevaux' => ['Nice', 'Côte d\'azur', 'Chemin de ronde', 'Citadelle', 'Gorge de Daluis', 'Train à Vapeur'],
            'la-palud-sur-verdon' => ['Blanc-Martel', 'Route des Crêtes', 'Escalade et via cordatta'],
            'saint-andre-les-alpes' => ['Lac de Castillon', 'Parapente', 'Train à vapeur']
        ];

        // Options générales
        $this->generalOptions = [
            'Randonnées', 'Pêche', 'Train', 'Villages alentours', 'Patrimoine culturel', 'Patrimoine naturel',
            'Visite guidée', 'Accès et transports', 'Informations pratiques', 'Évènements et animations',
            'Baignade et nautisme', 'Boutique Verdon Tourisme', 'Activité d\'eau vive', 'Vélo et VTT',
            'Autres activités de pleine nature', 'Commerces', 'Produits locaux', 'Restaurants',
            'Hébergements', 'Sociaux pro', 'Demandes d\'habitants'
        ];
    }

    protected function loadDraft()
    {
        // Vérifier que l'utilisateur est authentifié
        $userId = Auth::id();
        if (!$userId) {
            return;
        }

        // Charger le brouillon le plus récent pour cet utilisateur et cette ville
        $draft = Qualification::where('user_id', $userId)
            ->where('city', $this->city)
            ->where('completed', false)
            ->latest()
            ->first();

        if ($draft) {
            $this->qualificationId = $draft->id;
            $this->currentStep = $draft->current_step;

            $data = $draft->form_data;

            // Charger les données de l'étape 1
            $this->country = $data['country'] ?? 'France';
            $this->otherCountry = $data['otherCountry'] ?? '';

            // Handle backwards compatibility: convert string to array if needed
            $departmentData = $data['departments'] ?? $data['department'] ?? [];
            if (is_string($departmentData)) {
                $this->departments = !empty($departmentData) ? [$departmentData] : [];
            } else {
                $this->departments = is_array($departmentData) ? $departmentData : [];
            }

            $this->departmentUnknown = $data['departmentUnknown'] ?? false;
            $this->email = $data['email'] ?? '';
            $this->consentNewsletter = $data['consentNewsletter'] ?? false;
            $this->consentDataProcessing = $data['consentDataProcessing'] ?? false;

            // Charger les données de l'étape 2
            $this->profile = $data['profile'] ?? '';
            $this->profileUnknown = $data['profileUnknown'] ?? false;
            $this->ageGroups = $data['ageGroups'] ?? [];
            $this->ageUnknown = $data['ageUnknown'] ?? false;

            // Charger les données de l'étape 3
            $this->addedDate = $data['addedDate'] ?? now()->format('Y-m-d');
            $this->contactMethod = $data['contactMethod'] ?? 'Direct';
            $this->specificRequests = $data['specificRequests'] ?? [];
            $this->otherSpecificRequests = $data['otherSpecificRequests'] ?? [];
            $this->generalRequests = $data['generalRequests'] ?? [];
            $this->otherRequest = $data['otherRequest'] ?? '';
        }
    }

    protected function saveDraft()
    {
        $formData = [
            'country' => $this->country,
            'otherCountry' => $this->otherCountry,
            'departments' => $this->departments,
            'departmentUnknown' => $this->departmentUnknown,
            'email' => $this->email,
            'consentNewsletter' => $this->consentNewsletter,
            'consentDataProcessing' => $this->consentDataProcessing,
            'profile' => $this->profile,
            'profileUnknown' => $this->profileUnknown,
            'ageGroups' => $this->ageGroups,
            'ageUnknown' => $this->ageUnknown,
            'addedDate' => $this->addedDate,
            'contactMethod' => $this->contactMethod,
            'specificRequests' => $this->specificRequests,
            'otherSpecificRequests' => $this->otherSpecificRequests,
            'generalRequests' => $this->generalRequests,
            'otherRequest' => $this->otherRequest,
        ];

        if ($this->qualificationId) {
            // Mettre à jour le brouillon existant
            Qualification::where('id', $this->qualificationId)->update([
                'current_step' => $this->currentStep,
                'form_data' => $formData,
            ]);
        } else {
            // Créer un nouveau brouillon
            $qualification = Qualification::create([
                'user_id' => Auth::id(),
                'city' => $this->city,
                'current_step' => $this->currentStep,
                'form_data' => $formData,
                'completed' => false,
            ]);

            $this->qualificationId = $qualification->id;
        }
    }

    public function nextStep()
    {
        // Valider l'étape courante avant de passer à la suivante
        if ($this->currentStep === 1) {
            $this->validateStep1();
        } elseif ($this->currentStep === 2) {
            $this->validateStep2();
        }

        // Sauvegarder le brouillon
        $this->saveDraft();

        // Passer à l'étape suivante
        $this->currentStep++;
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    protected function validateStep1()
    {
        $rules = [
            'country' => 'required|string',
        ];

        if ($this->country === 'Autre') {
            $rules['otherCountry'] = 'required|string';
        }

        if ($this->country === 'France' && !$this->departmentUnknown) {
            $rules['departments'] = 'required|array|min:1';
        }

        if ($this->email) {
            $rules['email'] = 'email';
        }

        $messages = [
            'country.required' => 'Veuillez sélectionner un pays.',
            'otherCountry.required' => 'Veuillez préciser le pays.',
            'departments.required' => 'Veuillez sélectionner au moins un département.',
            'departments.min' => 'Veuillez sélectionner au moins un département.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
        ];

        $validator = Validator::make([
            'country' => $this->country,
            'otherCountry' => $this->otherCountry,
            'departments' => $this->departments,
            'email' => $this->email,
        ], $rules, $messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
            throw new \Illuminate\Validation\ValidationException($validator);
        }

        // Additional validation: Check if each department is valid using FrenchGeographyService
        if ($this->country === 'France' && !$this->departmentUnknown && !empty($this->departments)) {
            foreach ($this->departments as $department) {
                if (!$this->geographyService->isValidDepartment($department)) {
                    $this->addError('departments', "Le département \"$department\" n'est pas valide.");
                    throw new \Illuminate\Validation\ValidationException($validator);
                }
            }
        }

        // Additional validation: Check if other country is valid using FrenchGeographyService
        if ($this->country === 'Autre' && $this->otherCountry) {
            if (!$this->geographyService->isValidCountry($this->otherCountry)) {
                $this->addError('otherCountry', 'Le pays sélectionné n\'est pas valide.');
                throw new \Illuminate\Validation\ValidationException($validator);
            }
        }
    }

    protected function validateStep2()
    {
        $rules = [
            'profile' => 'required|string',
            'ageGroups' => 'required|array|min:1',
        ];

        $messages = [
            'profile.required' => 'Veuillez sélectionner un profil.',
            'ageGroups.required' => 'Veuillez sélectionner au moins une tranche d\'âge.',
            'ageGroups.min' => 'Veuillez sélectionner au moins une tranche d\'âge.',
        ];

        $validator = Validator::make([
            'profile' => $this->profile,
            'ageGroups' => $this->ageGroups,
        ], $rules, $messages);

        if ($validator->fails()) {
            foreach ($validator->errors()->messages() as $field => $messages) {
                foreach ($messages as $message) {
                    $this->addError($field, $message);
                }
            }
            throw new \Illuminate\Validation\ValidationException($validator);
        }
    }

    public function submit()
    {
        // Valider l'étape 3
        // Au moins une demande doit être remplie
        if (empty($this->specificRequests) && empty($this->generalRequests) && empty(trim($this->otherRequest))) {
            $this->addError('requests', 'Veuillez sélectionner au moins une demande ou préciser votre demande.');
            return;
        }

        // Valider le champ otherRequest si rempli
        if ($this->otherRequest) {
            $validator = Validator::make([
                'otherRequest' => $this->otherRequest,
            ], [
                'otherRequest' => 'string|max:1000',
            ], [
                'otherRequest.max' => 'La demande ne peut pas dépasser 1000 caractères.',
            ]);

            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $field => $messages) {
                    foreach ($messages as $message) {
                        $this->addError($field, $message);
                    }
                }
                return;
            }
        }

        // Créer une nouvelle qualification complète
        $formData = [
            'country' => $this->country === 'Autre' ? $this->otherCountry : $this->country,
            'departments' => $this->country === 'France' ? ($this->departmentUnknown ? [] : $this->departments) : [],
            'email' => $this->email,
            'consentNewsletter' => $this->consentNewsletter,
            'consentDataProcessing' => $this->consentDataProcessing,
            'profile' => $this->profileUnknown ? 'Inconnu' : $this->profile,
            'ageGroups' => $this->ageUnknown ? ['Inconnu'] : $this->ageGroups,
            'contactMethod' => $this->contactMethod,
            'specificRequests' => $this->specificRequests,
            'otherSpecificRequests' => $this->otherSpecificRequests,
            'generalRequests' => $this->generalRequests,
            'otherRequest' => $this->otherRequest,
        ];

        // Créer une nouvelle qualification complète
        $qualification = Qualification::create([
            'user_id' => Auth::id(),
            'city' => $this->city,
            'current_step' => 3,
            'form_data' => $formData,
            'completed' => true,
            'completed_at' => now(),
        ]);

        // Mettre à jour created_at avec la date choisie + heure actuelle
        $qualification->created_at = \Carbon\Carbon::parse($this->addedDate)
            ->setTime(now()->hour, now()->minute, now()->second);
        $qualification->save();

        // Supprimer le brouillon s'il existe
        if ($this->qualificationId) {
            Qualification::where('id', $this->qualificationId)->delete();
        }

        // Réinitialiser le formulaire
        $this->resetForm();
    }

    protected function resetForm()
    {
        // Réinitialiser l'étape
        $this->currentStep = 1;
        $this->qualificationId = null;

        // Réinitialiser Étape 1
        $this->country = 'France';
        $this->otherCountry = '';
        $this->departments = [];
        $this->departmentUnknown = false;
        $this->email = '';
        $this->consentNewsletter = false;
        $this->consentDataProcessing = false;

        // Réinitialiser Étape 2
        $this->profile = '';
        $this->profileUnknown = false;
        $this->ageGroups = [];
        $this->ageUnknown = false;

        // Réinitialiser Étape 3
        $this->addedDate = now()->format('Y-m-d');
        $this->contactMethod = 'Direct';
        $this->specificRequests = [];
        $this->otherSpecificRequests = [];
        $this->generalRequests = [];
        $this->otherRequest = '';
        $this->showOtherSpecificDropdown = false;
        $this->otherSpecificSearchQuery = '';

        // Afficher le message de succès
        $this->showSuccessMessage = true;

        // Masquer le message après 5 secondes
        $this->dispatch('success-message-shown');
    }

    // Watchers pour gérer les changements d'état
    public function updatedDepartmentUnknown($value)
    {
        if ($value) {
            $this->departments = [];
        } else {
            $this->departments = [];
        }
    }

    public function updatedProfileUnknown($value)
    {
        if ($value) {
            $this->profile = 'Inconnu';
        } else {
            $this->profile = '';
        }
    }

    public function updatedAgeUnknown($value)
    {
        if ($value) {
            $this->ageGroups = ['Inconnu'];
        } else {
            $this->ageGroups = [];
        }
    }

    public function updatedCountry($value)
    {
        if ($value !== 'Autre') {
            $this->otherCountry = '';
        }
        if ($value !== 'France') {
            $this->departments = [];
            $this->departmentUnknown = false;
        }
    }

    public function updatedOtherRequest($value)
    {
        $this->otherRequest = $this->formatText($value);
    }

    protected function formatText($text)
    {
        if (empty(trim($text))) {
            return $text;
        }

        // Trim whitespace
        $text = trim($text);

        // Normalize multiple spaces to single space
        $text = preg_replace('/\s+/', ' ', $text);

        // Capitalize first letter
        $text = mb_strtoupper(mb_substr($text, 0, 1)) . mb_substr($text, 1);

        // Add period if missing and doesn't end with punctuation
        if (!preg_match('/[.!?]$/', $text)) {
            $text .= '.';
        }

        return $text;
    }

    // Méthodes pour manipuler les tableaux
    public function toggleAgeGroup($age)
    {
        if (in_array($age, $this->ageGroups)) {
            $this->ageGroups = array_values(array_diff($this->ageGroups, [$age]));
        } else {
            $this->ageGroups[] = $age;
        }
    }

    public function toggleSpecificRequest($request)
    {
        if (in_array($request, $this->specificRequests)) {
            $this->specificRequests = array_values(array_diff($this->specificRequests, [$request]));
        } else {
            $this->specificRequests[] = $request;
        }
    }

    public function toggleGeneralRequest($request)
    {
        if (in_array($request, $this->generalRequests)) {
            $this->generalRequests = array_values(array_diff($this->generalRequests, [$request]));
        } else {
            $this->generalRequests[] = $request;
        }
    }

    public function setContactMethod($method)
    {
        $this->contactMethod = $method;
    }

    // Getters pour les options de la ville courante
    public function getCitySpecificOptionsProperty()
    {
        return $this->specificOptions[$this->city] ?? [];
    }

    /**
     * Récupérer toutes les demandes spécifiques des autres villes
     * (en excluant celles de la ville courante)
     */
    public function getAllOtherCitySpecificOptionsProperty()
    {
        $allOptions = [];

        foreach ($this->specificOptions as $city => $options) {
            // Exclure les options de la ville courante
            if ($city !== $this->city) {
                $allOptions = array_merge($allOptions, $options);
            }
        }

        // Dédupliquer et trier
        $allOptions = array_unique($allOptions);
        sort($allOptions);

        return $allOptions;
    }

    /**
     * Filtrer les options selon la recherche
     */
    public function getFilteredOtherSpecificOptionsProperty()
    {
        $options = $this->allOtherCitySpecificOptions;

        if (empty($this->otherSpecificSearchQuery)) {
            return $options;
        }

        $query = mb_strtolower($this->otherSpecificSearchQuery);

        return array_filter($options, function($option) use ($query) {
            return mb_strpos(mb_strtolower($option), $query) !== false;
        });
    }

    /**
     * Toggle une demande spécifique d'une autre ville
     */
    public function toggleOtherSpecificRequest($request)
    {
        if (in_array($request, $this->otherSpecificRequests)) {
            $this->otherSpecificRequests = array_values(array_diff($this->otherSpecificRequests, [$request]));
        } else {
            $this->otherSpecificRequests[] = $request;
        }
    }

    /**
     * Supprimer une demande spécifique d'une autre ville
     */
    public function removeOtherSpecificRequest($request)
    {
        $this->otherSpecificRequests = array_values(array_diff($this->otherSpecificRequests, [$request]));
    }

    /**
     * Ouvrir le dropdown des autres demandes spécifiques
     */
    public function openOtherSpecificDropdown()
    {
        $this->showOtherSpecificDropdown = true;
        $this->otherSpecificSearchQuery = '';
    }

    /**
     * Fermer le dropdown des autres demandes spécifiques
     */
    public function closeOtherSpecificDropdown()
    {
        $this->showOtherSpecificDropdown = false;
        $this->otherSpecificSearchQuery = '';
    }

    /**
     * Listen to departmentsSelected event from DepartmentSelector component
     */
    #[On('departmentsSelected')]
    public function handleDepartmentsSelected($departments)
    {
        $this->departments = $departments;
    }

    /**
     * Listen to departmentUnknownChanged event from DepartmentSelector component
     */
    #[On('departmentUnknownChanged')]
    public function handleDepartmentUnknownChanged($unknown)
    {
        $this->departmentUnknown = $unknown;
    }

    /**
     * Listen to countrySelected event from CountrySelector component
     */
    #[On('countrySelected')]
    public function handleCountrySelected($country)
    {
        $this->otherCountry = $country;
    }

    #[Layout('components.layouts.app')]
    public function render()
    {
        return view('livewire.qualification-form');
    }
}
