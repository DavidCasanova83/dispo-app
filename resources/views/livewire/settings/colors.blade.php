<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Couleurs')" :subheading="__('Personnalisez les couleurs de votre application')">
        <form wire:submit="save" class="space-y-6">
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Couleur primaire -->
                <div>
                    <flux:field>
                        <flux:label>Couleur primaire</flux:label>
                        <flux:description>Utilisée pour les boutons principaux et les liens importants</flux:description>
                        <div class="flex items-center gap-3">
                            <flux:input type="color" wire:model.live="primary_color" class="w-16 h-10" />
                            <flux:input type="text" wire:model.live="primary_color" class="flex-1 font-mono" />
                        </div>
                        <flux:error name="primary_color" />
                    </flux:field>
                </div>

                <!-- Couleur secondaire -->
                <div>
                    <flux:field>
                        <flux:label>Couleur secondaire</flux:label>
                        <flux:description>Utilisée pour les éléments d'interaction et les survols</flux:description>
                        <div class="flex items-center gap-3">
                            <flux:input type="color" wire:model.live="secondary_color" class="w-16 h-10" />
                            <flux:input type="text" wire:model.live="secondary_color" class="flex-1 font-mono" />
                        </div>
                        <flux:error name="secondary_color" />
                    </flux:field>
                </div>

                <!-- Couleur d'accent -->
                <div>
                    <flux:field>
                        <flux:label>Couleur d'accent</flux:label>
                        <flux:description>Utilisée pour les fonds de sections et les éléments décoratifs</flux:description>
                        <div class="flex items-center gap-3">
                            <flux:input type="color" wire:model.live="accent_color" class="w-16 h-10" />
                            <flux:input type="text" wire:model.live="accent_color" class="flex-1 font-mono" />
                        </div>
                        <flux:error name="accent_color" />
                    </flux:field>
                </div>

                <!-- Couleur de fond -->
                <div>
                    <flux:field>
                        <flux:label>Couleur de fond</flux:label>
                        <flux:description>Utilisée comme fond général de l'application</flux:description>
                        <div class="flex items-center gap-3">
                            <flux:input type="color" wire:model.live="background_color" class="w-16 h-10" />
                            <flux:input type="text" wire:model.live="background_color" class="flex-1 font-mono" />
                        </div>
                        <flux:error name="background_color" />
                    </flux:field>
                </div>
            </div>

            <!-- Aperçu des couleurs -->
            <div class="mt-8">
                <flux:label>Aperçu</flux:label>
                <div class="mt-3 p-6 rounded-lg border-2 border-gray-200" 
                     style="background-color: {{ $background_color }}">
                    <div class="space-y-4">
                        <div class="flex items-center gap-4">
                            <flux:button style="background-color: {{ $primary_color }}; color: white;">
                                Bouton principal
                            </flux:button>
                            <flux:button variant="outline" style="border-color: {{ $secondary_color }}; color: {{ $secondary_color }};">
                                Bouton secondaire
                            </flux:button>
                        </div>
                        <div class="p-4 rounded-lg" style="background-color: {{ $accent_color }}">
                            <p class="text-sm text-gray-700">
                                Exemple de section avec couleur d'accent
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Actions -->
            <div class="flex items-center gap-4 pt-6">
                <flux:button type="submit" variant="primary">
                    Enregistrer les couleurs
                </flux:button>
                
                <flux:button type="button" variant="outline" wire:click="resetToDefault">
                    Réinitialiser par défaut
                </flux:button>
            </div>

            <!-- Message de succès -->
            @if (session('status'))
                <div class="mt-4 p-3 bg-green-100 border border-green-400 text-green-700 rounded">
                    {{ session('status') }}
                </div>
            @endif
        </form>
    </x-settings.layout>
</section>
