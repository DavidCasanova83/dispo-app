{{--
    Versions linguistiques d'une brochure (fenêtre de modification, trait EditsBrochures).

    Pour l'anglais et l'italien : la version existante (ouvrir, supprimer), ou
    l'ajout d'une version (fichier ou lien Calaméo, couverture facultative), ou
    le rattachement d'un document déjà en ligne. Plus « ce document contient
    aussi » pour un document bilingue (carte FR/EN).
--}}
@php
    $versionLanguages = \App\Models\Image::VERSION_LANGUAGES;
    $languageAdjectives = \App\Models\Image::LANGUAGE_ADJECTIVES;
    $languageNouns = ['en' => "l'anglais", 'it' => "l'italien"];
    $mainVersions = $editingImage->versions;
    $inputClasses = 'w-full px-3 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent';
@endphp

<div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
    <h4 class="text-sm font-semibold text-gray-900 dark:text-white">Versions linguistiques</h4>
    <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5 mb-3">
        Affichées sur la carte de cette brochure (pastilles FR · EN · IT). Elles reprennent sa catégorie, son secteur, son auteur et son responsable.
    </p>

    {{-- Document bilingue --}}
    <div class="mb-3 flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-700 dark:text-gray-300">
        <span>Ce document contient aussi :</span>
        @foreach ($versionLanguages as $code)
            <label class="inline-flex items-center gap-1.5 cursor-pointer">
                <input type="checkbox" wire:model="editExtraLanguages" value="{{ $code }}"
                    class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#3E9B90] focus:ring-2 focus:ring-[#3E9B90]">
                {{ $languageNouns[$code] }}
            </label>
        @endforeach
    </div>

    <div class="space-y-2">
        @foreach ($versionLanguages as $code)
            @php
                $version = $mainVersions->firstWhere('language', $code);
                $adjective = $languageAdjectives[$code];
                $formOpen = !$version && $versionFormLanguage === $code;
            @endphp
            <div wire:key="brochure-version-{{ $code }}" class="rounded-lg border {{ $formOpen ? 'border-[#3E9B90]' : 'border-gray-200 dark:border-gray-700' }} p-3">
                <div class="flex items-center gap-3">
                    <span class="flex-shrink-0 text-xs font-bold px-1.5 py-0.5 rounded {{ $version ? 'bg-[#3E9B90] text-white' : 'bg-gray-100 dark:bg-gray-700 text-gray-500 dark:text-gray-300' }}">
                        {{ strtoupper($code) }}
                    </span>

                    @if ($version)
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $version->title ?? $version->name }}</p>
                            <p class="text-xs text-gray-500 dark:text-gray-400">
                                Édition {{ $version->edition_year ?? '—' }}
                                @if ($version->isOutdatedVersion())
                                    <span class="text-amber-600 dark:text-amber-400 font-medium">· plus ancienne que la version française ({{ $editingImage->edition_year }})</span>
                                @endif
                                @if ($version->is_offline)
                                    <span class="text-red-600 dark:text-red-400 font-medium">· hors ligne</span>
                                @endif
                                · <a href="{{ $version->consultUrl() }}" target="_blank" rel="noopener noreferrer" class="text-[#3E9B90] hover:underline">voir</a>
                            </p>
                        </div>
                        <button type="button" wire:click="openEditModal({{ $version->id }})"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-[#3E9B90] text-white hover:bg-[#2d7a72] transition-colors cursor-pointer">
                            Modifier
                        </button>
                        <button type="button" wire:click="removeVersion({{ $version->id }})"
                            wire:confirm="Supprimer la version {{ $adjective }} ? Son fichier sera effacé."
                            class="px-3 py-1.5 text-xs font-medium rounded-lg bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 hover:bg-red-200 dark:hover:bg-red-900/50 transition-colors cursor-pointer">
                            Supprimer
                        </button>
                    @elseif ($formOpen)
                        <p class="flex-1 text-sm font-medium text-gray-900 dark:text-white">Nouvelle version {{ $adjective }}</p>
                    @else
                        <p class="flex-1 text-sm text-gray-500 dark:text-gray-400">
                            Pas de version {{ $adjective }}
                            @if (in_array($code, (array) $editingImage->extra_languages, true))
                                <span class="block text-xs">Le document français contient déjà {{ $languageNouns[$code] }}.</span>
                            @endif
                        </p>
                        <button type="button" wire:click="openVersionForm('{{ $code }}')"
                            class="px-3 py-1.5 text-xs font-medium rounded-lg border border-[#3E9B90] text-[#3E9B90] hover:bg-[#3E9B90]/10 transition-colors cursor-pointer">
                            Ajouter
                        </button>
                    @endif
                </div>

                @if ($formOpen)
                    <div class="mt-3 space-y-3">
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                            <div class="sm:col-span-2">
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Titre</label>
                                <input type="text" wire:model="versionTitle" placeholder="{{ $editingImage->title }}" class="{{ $inputClasses }}">
                                @error('versionTitle') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Année d'édition <span class="text-red-500">*</span></label>
                                <input type="number" wire:model="versionEditionYear" min="1900" max="2100" class="{{ $inputClasses }}">
                                @error('versionEditionYear') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div>
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">PDF ou image téléchargeable (max 50 Mo)</label>
                            <input type="file" wire:model="versionContentFile" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 px-3 py-2">
                            <div wire:loading wire:target="versionContentFile" class="text-xs text-gray-500 mt-1">Chargement du fichier...</div>
                            @error('versionContentFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>

                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Couverture (facultative)</label>
                                <input type="file" wire:model="versionCoverFile" accept="image/jpeg,image/png,image/gif,image/webp"
                                    class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 px-3 py-2">
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Sinon : celle de la version française.</p>
                                @error('versionCoverFile') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Lien Calaméo (facultatif)</label>
                                <input type="url" wire:model="versionCalameoLinkUrl" placeholder="https://www.calameo.com/…" class="{{ $inputClasses }}">
                                @error('versionCalameoLinkUrl') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        <div class="flex justify-end gap-2">
                            <button type="button" wire:click="cancelVersionForm"
                                class="px-4 py-2 text-xs font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors cursor-pointer">
                                Annuler
                            </button>
                            <button type="button" wire:click="addVersion" wire:loading.attr="disabled" wire:target="addVersion,versionContentFile,versionCoverFile"
                                class="px-4 py-2 text-xs font-medium text-white bg-[#3E9B90] hover:bg-[#2d7a72] rounded-lg transition-colors cursor-pointer disabled:opacity-50">
                                Ajouter la version {{ $adjective }}
                            </button>
                        </div>

                        {{-- Rattacher un document déjà en ligne (anciennes brochures « English - … ») --}}
                        <div class="pt-3 border-t border-dashed border-gray-200 dark:border-gray-700">
                            <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">Ou rattacher un document déjà en ligne</label>
                            <div class="flex gap-2">
                                <select wire:model="versionAttachId" class="{{ $inputClasses }}">
                                    <option value="">-- Choisir un document --</option>
                                    @foreach ($this->versionCandidates as $candidate)
                                        <option value="{{ $candidate->id }}">{{ $candidate->title ?? $candidate->name }}{{ $candidate->edition_year ? ' (' . $candidate->edition_year . ')' : '' }}</option>
                                    @endforeach
                                </select>
                                <button type="button" wire:click="attachVersion"
                                    class="flex-shrink-0 px-4 py-2 text-xs font-medium rounded-lg border border-[#3E9B90] text-[#3E9B90] hover:bg-[#3E9B90]/10 transition-colors cursor-pointer">
                                    Rattacher
                                </button>
                            </div>
                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">Le document garde son fichier et ses statistiques, et quitte la liste des brochures.</p>
                            @error('versionAttachId') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    </div>
                @endif
            </div>
        @endforeach
    </div>
</div>
