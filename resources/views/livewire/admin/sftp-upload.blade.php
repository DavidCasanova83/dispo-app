<div class="space-y-6">
    {{-- Header --}}
    <div>
        <h1 class="text-2xl font-semibold text-zinc-900 dark:text-white">Upload de PDF vers SFTP</h1>
        <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">
            Uploadez des fichiers PDF vers le serveur SFTP configuré
        </p>
    </div>

    {{-- Flash Messages --}}
    @if (session()->has('message'))
        <flux:banner variant="success" icon="check-circle">
            {{ session('message') }}
        </flux:banner>
    @endif

    @if (session()->has('error'))
        <flux:banner variant="danger" icon="x-circle">
            {{ session('error') }}
        </flux:banner>
    @endif

    {{-- Statistics --}}
    <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-5">
        <flux:card class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Total</p>
                    <p class="mt-1 text-2xl font-semibold text-zinc-900 dark:text-white">
                        {{ $stats['total'] }}
                    </p>
                </div>
                <flux:icon.document-text class="size-8 text-zinc-400" />
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">En attente</p>
                    <p class="mt-1 text-2xl font-semibold text-amber-600 dark:text-amber-400">
                        {{ $stats['pending'] }}
                    </p>
                </div>
                <flux:icon.clock class="size-8 text-amber-400" />
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">En cours</p>
                    <p class="mt-1 text-2xl font-semibold text-blue-600 dark:text-blue-400">
                        {{ $stats['uploading'] }}
                    </p>
                </div>
                <flux:icon.arrow-up-tray class="size-8 text-blue-400" />
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Complétés</p>
                    <p class="mt-1 text-2xl font-semibold text-green-600 dark:text-green-400">
                        {{ $stats['completed'] }}
                    </p>
                </div>
                <flux:icon.check-circle class="size-8 text-green-400" />
            </div>
        </flux:card>

        <flux:card class="p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">Échoués</p>
                    <p class="mt-1 text-2xl font-semibold text-red-600 dark:text-red-400">
                        {{ $stats['failed'] }}
                    </p>
                </div>
                <flux:icon.x-circle class="size-8 text-red-400" />
            </div>
        </flux:card>
    </div>

    {{-- Upload Form --}}
    <flux:card class="p-6">
        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
            Uploader un nouveau fichier PDF
        </h2>

        <form wire:submit="uploadFile" class="space-y-4">
            <flux:select wire:model="configuration_id"
                        label="Configuration SFTP"
                        placeholder="Sélectionnez une configuration"
                        required>
                @foreach ($configurations as $config)
                    <flux:option value="{{ $config->id }}">{{ $config->name }}</flux:option>
                @endforeach
            </flux:select>

            @if (count($configurations) === 0)
                <flux:banner variant="warning" icon="exclamation-triangle">
                    Aucune configuration SFTP active disponible. Veuillez d'abord configurer un serveur SFTP.
                </flux:banner>
            @endif

            <div>
                <flux:field>
                    <flux:label>Fichier PDF</flux:label>
                    <flux:description>
                        Sélectionnez un fichier PDF (maximum 10 MB)
                    </flux:description>
                    <div class="mt-2">
                        <input type="file"
                               wire:model="file"
                               accept=".pdf,application/pdf"
                               class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-md file:border-0 file:text-sm file:font-semibold file:bg-zinc-100 file:text-zinc-700 hover:file:bg-zinc-200 dark:file:bg-zinc-800 dark:file:text-zinc-300 dark:hover:file:bg-zinc-700"
                               {{ count($configurations) === 0 ? 'disabled' : '' }}>
                    </div>
                    @error('file')
                        <flux:error>{{ $message }}</flux:error>
                    @enderror
                </flux:field>

                @if ($file)
                    <div class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                        <flux:icon.document-check class="inline size-4" />
                        Fichier sélectionné: {{ $file->getClientOriginalName() }}
                    </div>
                @endif

                <div wire:loading wire:target="file" class="mt-2 text-sm text-blue-600 dark:text-blue-400">
                    <flux:icon.arrow-path class="inline size-4 animate-spin" />
                    Chargement du fichier...
                </div>
            </div>

            <div class="flex justify-end">
                <flux:button type="submit"
                            variant="primary"
                            icon="cloud-arrow-up"
                            :disabled="!$file || !$configuration_id || count($configurations) === 0"
                            wire:loading.attr="disabled"
                            wire:target="uploadFile">
                    <span wire:loading.remove wire:target="uploadFile">Uploader</span>
                    <span wire:loading wire:target="uploadFile">Upload en cours...</span>
                </flux:button>
            </div>
        </form>
    </flux:card>

    {{-- Filters --}}
    <flux:card class="p-4">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex-1">
                <flux:input wire:model.live.debounce.300ms="search"
                           placeholder="Rechercher par nom de fichier..."
                           icon="magnifying-glass" />
            </div>

            <flux:select wire:model.live="filterStatus" class="sm:w-48">
                <flux:option value="all">Tous les statuts</flux:option>
                <flux:option value="pending">En attente</flux:option>
                <flux:option value="uploading">En cours</flux:option>
                <flux:option value="completed">Complétés</flux:option>
                <flux:option value="failed">Échoués</flux:option>
            </flux:select>
        </div>
    </flux:card>

    {{-- Uploads List --}}
    <flux:card>
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="border-b border-zinc-200 dark:border-zinc-700">
                    <tr class="text-left">
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Fichier
                        </th>
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Configuration
                        </th>
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Taille
                        </th>
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Statut
                        </th>
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Date
                        </th>
                        <th class="px-4 py-3 text-sm font-semibold text-zinc-900 dark:text-white">
                            Actions
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    @forelse ($uploads as $upload)
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <flux:icon.document-text class="size-5 text-red-600" />
                                    <div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white">
                                            {{ $upload->original_filename }}
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400">
                                            Par {{ $upload->user->name }}
                                        </p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $upload->configuration->name }}
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                {{ $upload->formatted_file_size }}
                            </td>
                            <td class="px-4 py-3">
                                @if ($upload->status === 'completed')
                                    <flux:badge color="green" size="sm">
                                        <flux:icon.check-circle class="size-3" />
                                        Complété
                                    </flux:badge>
                                @elseif ($upload->status === 'failed')
                                    <flux:badge color="red" size="sm">
                                        <flux:icon.x-circle class="size-3" />
                                        Échoué
                                    </flux:badge>
                                @elseif ($upload->status === 'uploading')
                                    <flux:badge color="blue" size="sm">
                                        <flux:icon.arrow-path class="size-3 animate-spin" />
                                        En cours
                                    </flux:badge>
                                @else
                                    <flux:badge color="amber" size="sm">
                                        <flux:icon.clock class="size-3" />
                                        En attente
                                    </flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3 text-sm text-zinc-600 dark:text-zinc-400">
                                <div>
                                    {{ $upload->created_at->format('d/m/Y H:i') }}
                                </div>
                                @if ($upload->uploaded_at)
                                    <div class="text-xs text-zinc-500">
                                        Uploadé {{ $upload->uploaded_at->diffForHumans() }}
                                    </div>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex gap-2">
                                    @if ($upload->isFailed())
                                        <flux:button wire:click="retry({{ $upload->id }})"
                                                    size="sm"
                                                    variant="ghost"
                                                    icon="arrow-path">
                                            Réessayer
                                        </flux:button>
                                    @endif

                                    @if ($upload->error_message)
                                        <flux:button size="sm"
                                                    variant="ghost"
                                                    icon="information-circle"
                                                    title="{{ $upload->error_message }}">
                                            Erreur
                                        </flux:button>
                                    @endif

                                    <flux:button wire:click="delete({{ $upload->id }})"
                                                wire:confirm="Êtes-vous sûr de vouloir supprimer cet upload ?"
                                                size="sm"
                                                variant="ghost"
                                                icon="trash"
                                                class="text-red-600 hover:text-red-700 dark:text-red-400">
                                        Supprimer
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-12 text-center">
                                <flux:icon.cloud-arrow-up class="mx-auto size-12 text-zinc-400 dark:text-zinc-600" />
                                <h3 class="mt-4 text-lg font-semibold text-zinc-900 dark:text-white">
                                    Aucun upload
                                </h3>
                                <p class="mt-2 text-sm text-zinc-600 dark:text-zinc-400">
                                    Commencez par uploader votre premier fichier PDF
                                </p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </flux:card>

    {{-- Pagination --}}
    @if ($uploads->hasPages())
        <div class="mt-6">
            {{ $uploads->links() }}
        </div>
    @endif
</div>
