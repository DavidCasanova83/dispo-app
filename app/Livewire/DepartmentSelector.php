<?php

namespace App\Livewire;

use App\Services\FrenchGeographyService;
use Livewire\Component;

class DepartmentSelector extends Component
{
    // Properties for wire:model binding with parent
    public $selectedDepartments = [];
    public $departmentUnknown = false;

    // Internal search state
    public $searchQuery = '';
    public $showDropdown = false;
    public $highlightedIndex = -1;
    public $searchResults = [];

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
    public function mount($departments = [], $unknown = false)
    {
        // Handle backwards compatibility: convert string to array
        if (is_string($departments)) {
            $this->selectedDepartments = !empty($departments) ? [$departments] : [];
        } else {
            $this->selectedDepartments = is_array($departments) ? $departments : [];
        }

        $this->departmentUnknown = $unknown;
        $this->searchQuery = '';

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
    }

    /**
     * Watch departmentUnknown for changes
     */
    public function updatedDepartmentUnknown()
    {
        if ($this->departmentUnknown) {
            $this->searchQuery = '';
            $this->selectedDepartments = [];
            $this->showDropdown = false;
            $this->dispatch('departmentsSelected', $this->selectedDepartments);
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
     * Toggle a department in the selection
     */
    public function toggleDepartment($code)
    {
        $department = $this->geographyService->getDepartmentByCode($code);

        if ($department) {
            $formattedDept = $this->geographyService->formatDepartment($department);

            // Toggle: if already selected, remove it; otherwise add it
            if (in_array($formattedDept, $this->selectedDepartments)) {
                $this->selectedDepartments = array_values(array_diff($this->selectedDepartments, [$formattedDept]));
            } else {
                $this->selectedDepartments[] = $formattedDept;
            }

            // Clear search query after selection
            $this->searchQuery = '';
            $this->highlightedIndex = -1;

            // Emit event to parent component with updated array
            $this->dispatch('departmentsSelected', $this->selectedDepartments);
        }
    }

    /**
     * Remove a department from the selection (for chip removal)
     */
    public function removeDepartment($department)
    {
        $this->selectedDepartments = array_values(array_diff($this->selectedDepartments, [$department]));
        $this->dispatch('departmentsSelected', $this->selectedDepartments);
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
     * Blur search input
     */
    public function blurSearch()
    {
        // Delay to allow click on dropdown item before closing
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
                    $this->toggleDepartment($this->searchResults[$this->highlightedIndex]['code']);
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
            $this->selectedDepartments = [];
            $this->showDropdown = false;
            $this->dispatch('departmentsSelected', $this->selectedDepartments);
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
     * Get the selected departments for parent component
     */
    public function getSelectedDepartmentsProperty()
    {
        return $this->selectedDepartments;
    }

    /**
     * Get the unknown status for parent component
     */
    public function getDepartmentUnknownProperty()
    {
        return $this->departmentUnknown;
    }

    /**
     * Check if a department is selected
     */
    public function isDepartmentSelected($department)
    {
        return in_array($department, $this->selectedDepartments);
    }
}
