<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\Attributes\Layout;

class QualificationForm extends Component
{
  public $city;
  public $cityName;

  public function mount($city, $cityName)
  {
    $this->city = $city;
    $this->cityName = $cityName;
  }

  #[Layout('components.layouts.app')]
  public function render()
  {
    return view('livewire.qualification-form');
  }
}
