<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Configuration SFTP</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Configurez les paramètres de connexion au serveur SFTP pour l'upload de fichiers PDF
            </p>
        </div>
    </div>

    {{-- Configuration Form --}}
    <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
        <form wire:submit.prevent="save">
            <div class="space-y-6">
                {{-- Name --}}
                <flux:input wire:model="name" label="Nom de la configuration" placeholder="Configuration SFTP" />

                {{-- Host --}}
                <flux:input wire:model="host" label="Hôte" placeholder="sftp.example.com" required />

                {{-- Port --}}
                <flux:input wire:model="port" type="number" label="Port" placeholder="22" required />

                {{-- Username --}}
                <flux:input wire:model="username" label="Nom d'utilisateur" placeholder="username" required />

                {{-- Password --}}
                <flux:input
                    wire:model="password"
                    type="password"
                    label="Mot de passe"
                    placeholder="{{ $configuration ? 'Laisser vide pour conserver le mot de passe actuel' : 'Entrez le mot de passe' }}"
                    :required="!$configuration"
                />

                {{-- Remote Path --}}
                <flux:input wire:model="remote_path" label="Chemin distant" placeholder="/" required />

                {{-- Timeout --}}
                <flux:input wire:model="timeout" type="number" label="Timeout (secondes)" placeholder="30" required />

                {{-- Active Toggle --}}
                <flux:checkbox wire:model="is_active" label="Configuration active" />

                {{-- Actions --}}
                <div class="flex gap-3">
                    <flux:button type="submit" variant="primary" :disabled="$testing">
                        Enregistrer la configuration
                    </flux:button>

                    <flux:button type="button" wire:click="testConnection" variant="outline" :disabled="$testing">
                        @if($testing)
                            <span>Test en cours...</span>
                        @else
                            <span>Tester la connexion</span>
                        @endif
                    </flux:button>
                </div>
            </div>
        </form>
    </div>

    {{-- Info Card --}}
    <div class="rounded-xl border border-blue-200 dark:border-blue-700 bg-blue-50 dark:bg-blue-900/20 p-6">
        <div class="flex items-start gap-3">
            <div class="rounded-lg bg-blue-100 dark:bg-blue-900/30 p-2">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                </svg>
            </div>
            <div class="flex-1">
                <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Informations importantes</h3>
                <ul class="mt-2 space-y-1 text-sm text-blue-800 dark:text-blue-200">
                    <li>• Le mot de passe est crypté avant d'être stocké en base de données</li>
                    <li>• Testez la connexion avant d'enregistrer pour vérifier les paramètres</li>
                    <li>• Une seule configuration peut être active à la fois</li>
                    <li>• Le chemin distant doit exister ou être créé sur le serveur SFTP</li>
                </ul>
            </div>
        </div>
    </div>
</div>
