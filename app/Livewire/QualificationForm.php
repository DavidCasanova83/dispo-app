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
    public $department = '';
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
    public $contactMethod = 'Direct';
    public $specificRequests = [];
    public $generalRequests = [];
    public $otherRequest = '';

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
            $this->department = $data['department'] ?? '';
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
            $this->contactMethod = $data['contactMethod'] ?? 'Direct';
            $this->specificRequests = $data['specificRequests'] ?? [];
            $this->generalRequests = $data['generalRequests'] ?? [];
            $this->otherRequest = $data['otherRequest'] ?? '';
        }
    }

    protected function saveDraft()
    {
        $formData = [
            'country' => $this->country,
            'otherCountry' => $this->otherCountry,
            'department' => $this->department,
            'departmentUnknown' => $this->departmentUnknown,
            'email' => $this->email,
            'consentNewsletter' => $this->consentNewsletter,
            'consentDataProcessing' => $this->consentDataProcessing,
            'profile' => $this->profile,
            'profileUnknown' => $this->profileUnknown,
            'ageGroups' => $this->ageGroups,
            'ageUnknown' => $this->ageUnknown,
            'contactMethod' => $this->contactMethod,
            'specificRequests' => $this->specificRequests,
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
            $rules['department'] = 'required|string';
        }

        if ($this->email) {
            $rules['email'] = 'email';
        }

        $messages = [
            'country.required' => 'Veuillez sélectionner un pays.',
            'otherCountry.required' => 'Veuillez préciser le pays.',
            'department.required' => 'Veuillez sélectionner un département.',
            'email.email' => 'Veuillez entrer une adresse email valide.',
        ];

        $validator = Validator::make([
            'country' => $this->country,
            'otherCountry' => $this->otherCountry,
            'department' => $this->department,
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

        // Additional validation: Check if department is valid using FrenchGeographyService
        if ($this->country === 'France' && !$this->departmentUnknown && $this->department) {
            if (!$this->geographyService->isValidDepartment($this->department)) {
                $this->addError('department', 'Le département sélectionné n\'est pas valide.');
                throw new \Illuminate\Validation\ValidationException($validator);
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
            'department' => $this->country === 'France' ? ($this->departmentUnknown ? 'Inconnu' : $this->department) : null,
            'email' => $this->email,
            'consentNewsletter' => $this->consentNewsletter,
            'consentDataProcessing' => $this->consentDataProcessing,
            'profile' => $this->profileUnknown ? 'Inconnu' : $this->profile,
            'ageGroups' => $this->ageUnknown ? ['Inconnu'] : $this->ageGroups,
            'contactMethod' => $this->contactMethod,
            'specificRequests' => $this->specificRequests,
            'generalRequests' => $this->generalRequests,
            'otherRequest' => $this->otherRequest,
        ];

        // Créer une nouvelle qualification complète
        Qualification::create([
            'user_id' => Auth::id(),
            'city' => $this->city,
            'current_step' => 3,
            'form_data' => $formData,
            'completed' => true,
            'completed_at' => now(),
        ]);

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
        $this->department = '';
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
        $this->contactMethod = 'Direct';
        $this->specificRequests = [];
        $this->generalRequests = [];
        $this->otherRequest = '';

        // Afficher le message de succès
        $this->showSuccessMessage = true;

        // Masquer le message après 5 secondes
        $this->dispatch('success-message-shown');
    }

    // Watchers pour gérer les changements d'état
    public function updatedDepartmentUnknown($value)
    {
        if ($value) {
            $this->department = 'Inconnu';
        } else {
            $this->department = '';
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
            $this->department = '';
            $this->departmentUnknown = false;
        }
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
     * Listen to departmentSelected event from DepartmentSelector component
     */
    #[On('departmentSelected')]
    public function handleDepartmentSelected($department)
    {
        $this->department = $department;
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
