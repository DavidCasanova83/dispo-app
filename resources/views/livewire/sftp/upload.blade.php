<div class="flex h-full w-full flex-1 flex-col gap-6">
    {{-- Header --}}
    <div class="flex justify-between items-center">
        <div>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Upload PDF vers SFTP</h1>
            <p class="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Téléchargez des fichiers PDF sur le serveur SFTP configuré
            </p>
        </div>
    </div>

    @if($activeConfig)
        {{-- Upload Form --}}
        <div class="rounded-xl border border-neutral-200 dark:border-neutral-700 bg-white dark:bg-gray-800 p-6">
            <form wire:submit.prevent="upload">
                <div class="space-y-6">
                    {{-- File Input --}}
                    <div>
                        <flux:label>Fichier PDF</flux:label>
                        <div class="mt-2">
                            <input
                                type="file"
                                wire:model="pdfFile"
                                accept=".pdf,application/pdf"
                                class="block w-full text-sm text-gray-900 dark:text-gray-100 border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none"
                            />
                        </div>
                        @error('pdfFile')
                            <span class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        {{-- Loading indicator for file upload --}}
                        <div wire:loading wire:target="pdfFile" class="mt-2">
                            <p class="text-sm text-blue-600 dark:text-blue-400">Chargement du fichier...</p>
                        </div>

                        {{-- File info --}}
                        @if($pdfFile && !$errors->has('pdfFile'))
                            <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg">
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center gap-2">
                                        <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                        </svg>
                                        <span class="text-sm font-medium text-green-800 dark:text-green-200">
                                            {{ $pdfFile->getClientOriginalName() }}
                                        </span>
                                    </div>
                                    <button type="button" wire:click="clearFile" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                        </svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Actions --}}
                    <div class="flex gap-3">
                        <flux:button
                            type="submit"
                            variant="primary"
                            :disabled="$uploading || !$pdfFile"
                        >
                            @if($uploading)
                                <span>Upload en cours...</span>
                            @else
                                <span>Uploader le fichier</span>
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
                    <h3 class="text-sm font-semibold text-blue-900 dark:text-blue-100">Configuration active</h3>
                    <div class="mt-2 space-y-1 text-sm text-blue-800 dark:text-blue-200">
                        <p><strong>Serveur:</strong> {{ $activeConfig->host }}:{{ $activeConfig->port }}</p>
                        <p><strong>Chemin distant:</strong> {{ $activeConfig->remote_path }}</p>
                        <p class="mt-3 text-xs">Seuls les fichiers PDF sont acceptés (max 50 Mo)</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        {{-- No Configuration Warning --}}
        <div class="rounded-xl border border-yellow-200 dark:border-yellow-700 bg-yellow-50 dark:bg-yellow-900/20 p-6">
            <div class="flex items-start gap-3">
                <div class="rounded-lg bg-yellow-100 dark:bg-yellow-900/30 p-2">
                    <svg class="w-5 h-5 text-yellow-600 dark:text-yellow-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                    </svg>
                </div>
                <div class="flex-1">
                    <h3 class="text-sm font-semibold text-yellow-900 dark:text-yellow-100">Configuration requise</h3>
                    <p class="mt-2 text-sm text-yellow-800 dark:text-yellow-200">
                        Aucune configuration SFTP active n'est disponible. Veuillez d'abord configurer le serveur SFTP dans la section Configuration.
                    </p>
                    <div class="mt-4">
                        <flux:button href="{{ route('sftp.configuration') }}" variant="primary">
                            Configurer SFTP
                        </flux:button>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
