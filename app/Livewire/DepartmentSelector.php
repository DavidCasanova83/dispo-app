<?php

namespace App\Livewire;

use App\Services\FrenchGeographyService;
use Livewire\Component;

class DepartmentSelector extends Component
{
    // Properties for wire:model binding with parent
    public $selectedDepartment = '';
    public $departmentUnknown = false;

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
    public function mount($department = '', $unknown = false)
    {
        $this->selectedDepartment = $department;
        $this->departmentUnknown = $unknown;
        $this->searchQuery = $department;

        // If we have a pre-filled department, mark as valid
        if (!empty($department) && !$unknown) {
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

        // If search query is empty and not selecting from dropdown, clear selection
        if (empty($this->searchQuery)) {
            $this->selectedDepartment = '';
            $this->isValidSelection = true; // Empty is valid (will be caught by required validation)
        }
    }

    /**
     * Watch departmentUnknown for changes
     */
    public function updatedDepartmentUnknown()
    {
        if ($this->departmentUnknown) {
            $this->searchQuery = '';
            $this->selectedDepartment = '';
            $this->showDropdown = false;
            $this->isValidSelection = true; // "Unknown" is a valid state
        }
    }

    /**
     * Update search results based on query
     */
    protected function updateSearchResults()
    {
        if (empty(trim($this->searchQuery))) {
            $this->searchResults = $this->geographyService->getAllDepartments();
        } else {
            $this->searchResults = $this->geographyService->searchDepartments($this->searchQuery, 10);
        }
    }

    /**
     * Select a department from the dropdown
     */
    public function selectDepartment($code)
    {
        $department = $this->geographyService->getDepartmentByCode($code);

        if ($department) {
            $this->selectedDepartment = $this->geographyService->formatDepartment($department);
            $this->searchQuery = $this->selectedDepartment;
            $this->showDropdown = false;
            $this->highlightedIndex = -1;
            $this->isValidSelection = true;

            // Emit event to parent component
            $this->dispatch('departmentSelected', $this->selectedDepartment);
        }
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
            // Check if what they typed exactly matches a formatted department
            $isExactMatch = $this->geographyService->isValidDepartment($this->searchQuery);

            if ($isExactMatch) {
                // Accept exact matches and treat as selection
                $this->selectedDepartment = $this->searchQuery;
                $this->isValidSelection = true;
                $this->dispatch('departmentSelected', $this->selectedDepartment);
            } else {
                // Restore to last valid selection (or empty)
                $this->searchQuery = $this->selectedDepartment;
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
                    $this->selectDepartment($this->searchResults[$this->highlightedIndex]['code']);
                }
                break;

            case 'Escape':
                $this->closeDropdown();
                break;
        }
    }

    /**
     * Toggle "Inconnu" state
     */
    public function toggleUnknown()
    {
        $this->departmentUnknown = !$this->departmentUnknown;

        if ($this->departmentUnknown) {
            $this->searchQuery = '';
            $this->selectedDepartment = '';
            $this->showDropdown = false;
            $this->isValidSelection = true; // "Unknown" is a valid state
        }
    }

    /**
     * Render the component
     */
    public function render()
    {
        return view('livewire.department-selector', [
            'results' => $this->searchResults,
            'showDropdown' => $this->showDropdown && count($this->searchResults) > 0,
        ]);
    }

    /**
     * Get the selected department for parent component
     */
    public function getSelectedDepartmentProperty()
    {
        return $this->selectedDepartment;
    }

    /**
     * Get the unknown status for parent component
     */
    public function getDepartmentUnknownProperty()
    {
        return $this->departmentUnknown;
    }
}
