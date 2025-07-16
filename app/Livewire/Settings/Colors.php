<?php

namespace App\Livewire\Settings;

use App\Models\UserColorSettings;
use Livewire\Component;

class Colors extends Component
{
    public string $primary_color = '#3A9C92';
    public string $secondary_color = '#7AB6A8';
    public string $accent_color = '#FFFDF4';
    public string $background_color = '#FAF7F3';

    public function mount(): void
    {
        $settings = auth()->user()->colorSettings;
        
        if ($settings) {
            $this->primary_color = $settings->primary_color;
            $this->secondary_color = $settings->secondary_color;
            $this->accent_color = $settings->accent_color;
            $this->background_color = $settings->background_color;
        }
    }

    public function save(): void
    {
        $this->validate([
            'primary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'secondary_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'accent_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
            'background_color' => 'required|regex:/^#[0-9a-fA-F]{6}$/',
        ]);

        UserColorSettings::updateOrCreate(
            ['user_id' => auth()->id()],
            [
                'primary_color' => $this->primary_color,
                'secondary_color' => $this->secondary_color,
                'accent_color' => $this->accent_color,
                'background_color' => $this->background_color,
            ]
        );

        session()->flash('status', 'Couleurs mises à jour avec succès !');
    }

    public function resetToDefault(): void
    {
        $defaults = UserColorSettings::getDefaultColors();
        
        $this->primary_color = $defaults['primary_color'];
        $this->secondary_color = $defaults['secondary_color'];
        $this->accent_color = $defaults['accent_color'];
        $this->background_color = $defaults['background_color'];
    }

    public function render()
    {
        return view('livewire.settings.colors');
    }
}
