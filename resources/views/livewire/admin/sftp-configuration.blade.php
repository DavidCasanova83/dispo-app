<div class="space-y-6">
    {{-- Header --}}
    <div class="flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Configuration SFTP</h1>
            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                Gérez les configurations SFTP pour l'upload de fichiers PDF
            </p>
        </div>
        <x-button wire:click="create" icon="plus" variant="primary">
            Nouvelle configuration
        </x-button>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <x-alert variant="success" icon="check-circle">
            {{ session('message') }}
        </x-alert>
    @endif

    @if (session()->has('testResult'))
        @php $result = session('testResult'); @endphp
        <x-alert variant="{{ $result['success'] ? 'success' : 'danger' }}"
                 icon="{{ $result['success'] ? 'check-circle' : 'x-circle' }}">
            {{ $result['message'] }}
        </x-alert>
    @endif

    {{-- Configurations List --}}
    <div class="grid gap-4">
        @forelse ($configurations as $config)
            <x-card class="p-6">
                <div class="flex items-start justify-between">
                    <div class="flex-1">
                        <div class="flex items-center gap-3">
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">
                                {{ $config->name }}
                            </h3>
                            @if ($config->active)
                                <x-badge color="green" size="sm">Active</x-badge>
                            @else
                                <x-badge color="zinc" size="sm">Inactive</x-badge>
                            @endif
                        </div>

                        <div class="mt-3 grid gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                            <div class="flex items-center gap-2">
                                <flux:icon.server class="size-4" />
                                <span><strong>Hôte:</strong> {{ $config->host }}:{{ $config->port }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:icon.user class="size-4" />
                                <span><strong>Utilisateur:</strong> {{ $config->username }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:icon.folder class="size-4" />
                                <span><strong>Dossier distant:</strong> {{ $config->remote_path }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <flux:icon.cloud-arrow-up class="size-4" />
                                <span><strong>Uploads:</strong> {{ $config->uploads_count }}</span>
                            </div>
                            @if ($config->last_test_at)
                                <div class="flex items-center gap-2">
                                    <flux:icon.check-circle class="size-4" />
                                    <span><strong>Dernier test:</strong> {{ $config->last_test_at->diffForHumans() }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="flex gap-2">
                        <x-button
                            wire:click="testConnection({{ $config->id }})"
                            size="sm"
                            variant="ghost"
                            icon="signal"
                            wire:loading.attr="disabled"
                            wire:target="testConnection({{ $config->id }})">
                            Tester
                        </x-button>
                        <x-button
                            wire:click="toggleActive({{ $config->id }})"
                            size="sm"
                            variant="ghost"
                            icon="{{ $config->active ? 'eye-slash' : 'eye' }}">
                            {{ $config->active ? 'Désactiver' : 'Activer' }}
                        </x-button>
                        <x-button
                            wire:click="edit({{ $config->id }})"
                            size="sm"
                            variant="ghost"
                            icon="pencil">
                            Éditer
                        </x-button>
                        <x-button
                            wire:click="delete({{ $config->id }})"
                            wire:confirm="Êtes-vous sûr de vouloir supprimer cette configuration ?"
                            size="sm"
                            variant="ghost"
                            icon="trash"
                            class="text-red-600 hover:text-red-700 dark:text-red-400">
                            Supprimer
                        </x-button>
                    </div>
                </div>
            </x-card>
        @empty
            <x-card class="p-12 text-center">
                <flux:icon.server class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                    Aucune configuration SFTP
                </h3>
                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                    Commencez par créer votre première configuration SFTP
                </p>
                <x-button wire:click="create" icon="plus" variant="primary" class="mt-4">
                    Créer une configuration
                </x-button>
            </x-card>
        @endforelse
    </div>

    {{-- Pagination --}}
    @if ($configurations->hasPages())
        <div class="mt-6">
            {{ $configurations->links() }}
        </div>
    @endif

    {{-- Configuration Modal --}}
    @if ($showModal)
        <x-modal name="config-modal" class="space-y-6">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    {{ $editingId ? 'Modifier la configuration' : 'Nouvelle configuration' }}
                </h2>
                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
                    {{ $editingId ? 'Modifiez les paramètres de connexion SFTP' : 'Ajoutez une nouvelle configuration SFTP' }}
                </p>
            </div>

            <form wire:submit="save" class="space-y-6">
                <x-form.input
                    wire:model="name"
                    label="Nom de la configuration"
                    placeholder="Production SFTP"
                    required />

                <div class="grid gap-4 sm:grid-cols-3">
                    <div class="sm:col-span-2">
                        <x-form.input
                            wire:model="host"
                            label="Hôte SFTP"
                            placeholder="sftp.example.com"
                            required />
                    </div>
                    <x-form.input
                        wire:model="port"
                        label="Port"
                        type="number"
                        min="1"
                        max="65535"
                        required />
                </div>

                <x-form.input
                    wire:model="username"
                    label="Nom d'utilisateur"
                    placeholder="username"
                    required />

                <x-form.input
                    wire:model="password"
                    label="Mot de passe"
                    type="password"
                    placeholder="••••••••"
                    :description="$editingId ? 'Laissez vide pour conserver le mot de passe actuel' : ''" />

                <x-form.textarea
                    wire:model="private_key"
                    label="Clé privée SSH (alternative au mot de passe)"
                    rows="4"
                    placeholder="-----BEGIN RSA PRIVATE KEY-----"
                    description="Utilisez soit un mot de passe, soit une clé privée" />

                <x-form.input
                    wire:model="remote_path"
                    label="Dossier distant"
                    placeholder="/uploads/pdf"
                    required />

                <x-form.switch
                    wire:model="active"
                    label="Configuration active" />

                <div class="flex gap-2 justify-end">
                    <x-button
                        type="button"
                        wire:click="closeModal"
                        variant="ghost">
                        Annuler
                    </x-button>
                    <x-button type="submit" variant="primary">
                        {{ $editingId ? 'Mettre à jour' : 'Créer' }}
                    </x-button>
                </div>
            </form>
        </x-modal>
    @endif
</div>
