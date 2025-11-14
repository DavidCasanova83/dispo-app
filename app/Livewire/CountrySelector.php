<?php

namespace App\Livewire;

use App\Services\FrenchGeographyService;
use Livewire\Component;

class CountrySelector extends Component
{
    // Properties for wire:model binding with parent
    public $selectedCountry = '';

    // Internal search state
    public $searchQuery = '';
    public $showDropdown = false;
    public $highlightedIndex = -1;
    public $searchResults = [];
    public $isValidSelection = false;

    protected $geographyService;

    /**
     * Component lifecycle: boot service
     */
    public function boot(FrenchGeographyService $service)
    {
        $this->geographyService = $service;
    }

    /**
     * Component mount: initialize search results
     */
    public function mount($country = '')
    {
        $this->selectedCountry = $country;
        $this->searchQuery = $country;

        // If we have a pre-filled country, mark as valid
        if (!empty($country)) {
            $this->isValidSelection = true;
        }

        $this->updateSearchResults();
    }

    /**
     * Watch searchQuery for changes and update results
     */
    public function updatedSearchQuery()
    {
        $this->updateSearchResults();
        $this->showDropdown = true;
        $this->highlightedIndex = -1;
        $this->isValidSelection = false; // Mark as invalid until selection from dropdown

        // If search query is empty, clear selection
        if (empty($this->searchQuery)) {
            $this->selectedCountry = '';
            $this->isValidSelection = true; // Empty is valid (will be caught by required validation)
        }
    }

    /**
     * Update search results based on query
     */
    protected function updateSearchResults()
    {
        if (empty(trim($this->searchQuery))) {
            $this->searchResults = $this->geographyService->getAllCountries();
        } else {
            $this->searchResults = $this->geographyService->searchCountries($this->searchQuery, 10);
        }
    }

    /**
     * Select a country from the dropdown
     */
    public function selectCountry($country)
    {
        $this->selectedCountry = $country;
        $this->searchQuery = $country;
        $this->showDropdown = false;
        $this->highlightedIndex = -1;
        $this->isValidSelection = true;

        // Emit event to parent component
        $this->dispatch('countrySelected', $this->selectedCountry);
    }

    /**
     * Focus on search input
     */
    public function focusSearch()
    {
        $this->showDropdown = true;
        $this->updateSearchResults();
    }

    /**
     * Blur search input - validate that only dropdown selections are kept
     */
    public function blurSearch()
    {
        // If the user typed something but didn't select from dropdown
        if (!$this->isValidSelection && !empty(trim($this->searchQuery))) {
            // Check if what they typed exactly matches a country name
            $isExactMatch = $this->geographyService->isValidCountry($this->searchQuery);

            if ($isExactMatch) {
                // Accept exact matches and treat as selection
                $matchedCountry = $this->geographyService->getCountryByName($this->searchQuery);
                if ($matchedCountry) {
                    $this->selectedCountry = $matchedCountry;
                    $this->searchQuery = $matchedCountry;
                    $this->isValidSelection = true;
                    $this->dispatch('countrySelected', $this->selectedCountry);
                }
            } else {
                // Restore to last valid selection (or empty)
                $this->searchQuery = $this->selectedCountry;
            }
        }

        // Delay to allow click on dropdown item
        $this->dispatch('delayedBlur');
    }

    /**
     * Close dropdown
     */
    public function closeDropdown()
    {
        $this->showDropdown = false;
        $this->highlightedIndex = -1;
    }

    /**
     * Handle keyboard navigation
     */
    public function handleKeydown($key)
    {
        if (!$this->showDropdown) {
            return;
        }

        switch ($key) {
            case 'ArrowDown':
                $this->highlightedIndex = min($this->highlightedIndex + 1, count($this->searchResults) - 1);
                break;

            case 'ArrowUp':
                $this->highlightedIndex = max($this->highlightedIndex - 1, 0);
                break;

            case 'Enter':
                if ($this->highlightedIndex >= 0 && isset($this->searchResults[$this->highlightedIndex])) {
                    $this->selectCountry($this->searchResults[$this->highlightedIndex]);
                }
                break;

            case 'Escape':
                $this->closeDropdown();
                break;
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.country-selector', [
            'results' => $this->searchResults,
            'showDropdown' => $this->showDropdown && count($this->searchResults) > 0,
        ]);
    }

    /**
     * Get the selected country for parent component
     */
    public function getSelectedCountryProperty()
    {
        return $this->selectedCountry;
    }
}
