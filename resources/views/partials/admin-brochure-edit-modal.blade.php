{{--
    Modal d'édition admin d'une brochure (utilisé par le trait EditsBrochures).

    Variables attendues (passées via @include):
      $categories            : Collection<Category>
      $subCategories         : Collection<SubCategory> (avec relation category chargée)
      $authors               : Collection<Author>
      $sectors               : Collection<Sector>
      $responsables          : Collection<User>
      $usedDisplayOrders     : array<int>

    Le composant hôte doit utiliser le trait App\Livewire\Concerns\EditsBrochures.
--}}
@if ($showEditModal && $editingImage)
    <div class="fixed inset-0 z-50 overflow-y-auto">
        <div class="flex min-h-screen items-center justify-center px-4 py-6">
            <div class="fixed inset-0 bg-black/30 dark:bg-black/50 backdrop-blur-sm transition-opacity"
                wire:click="closeEditModal"></div>

            <div class="relative bg-white dark:bg-[#001716] rounded-lg shadow-xl max-w-2xl w-full p-6 max-h-[90vh] overflow-y-auto">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-4">
                    Modifier la brochure
                </h3>

                <form wire:submit.prevent="updateImage">
                    {{-- Image preview --}}
                    <div class="mb-4">
                        <img src="{{ asset('storage/' . ($editingImage->thumbnail_path ?? $editingImage->path)) }}"
                            alt="{{ $editingImage->alt_text ?? $editingImage->name }}"
                            class="w-32 h-32 object-cover rounded-lg">
                        <p class="text-sm text-gray-500 dark:text-gray-400 mt-2">{{ $editingImage->name }}</p>
                    </div>

                    <div class="space-y-4">
                        {{-- Auteur (obligatoire) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Auteur <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="editAuthorId"
                                class="w-full px-4 py-2 text-sm rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent {{ empty($editAuthorId) ? 'border-red-300 dark:border-red-700' : 'border-gray-300 dark:border-gray-600' }}">
                                <option value="">-- Sélectionner un auteur --</option>
                                @foreach ($authors as $author)
                                    <option value="{{ $author->id }}">{{ $author->name }}</option>
                                @endforeach
                            </select>
                            @error('editAuthorId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Catégorie / Sous-catégorie / Secteur --}}
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Catégorie
                                </label>
                                <select wire:model.live="editCategoryId"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    <option value="">-- Aucune --</option>
                                    @foreach ($categories as $category)
                                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                                    @endforeach
                                </select>
                                @error('editCategoryId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Sous-catégorie
                                </label>
                                <select wire:model="editSubCategoryId"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent disabled:opacity-50"
                                    {{ !$editCategoryId ? 'disabled' : '' }}>
                                    <option value="">-- Aucune --</option>
                                    @foreach ($subCategories->where('category_id', (int) $editCategoryId) as $subCategory)
                                        <option value="{{ $subCategory->id }}">{{ $subCategory->name }}</option>
                                    @endforeach
                                </select>
                                @error('editSubCategoryId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Secteur
                                </label>
                                <select wire:model="editSectorId"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                    <option value="">-- Aucun --</option>
                                    @foreach ($sectors as $sector)
                                        <option value="{{ $sector->id }}">{{ $sector->name }}</option>
                                    @endforeach
                                </select>
                                @error('editSectorId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Responsable (obligatoire) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Responsable <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="editResponsableId"
                                class="w-full px-4 py-2 text-sm rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent {{ empty($editResponsableId) ? 'border-red-300 dark:border-red-700' : 'border-gray-300 dark:border-gray-600' }}">
                                <option value="">-- Sélectionner un responsable --</option>
                                @foreach ($responsables as $responsable)
                                    <option value="{{ $responsable->id }}">{{ $responsable->name }}</option>
                                @endforeach
                            </select>
                            @error('editResponsableId') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Titre (obligatoire) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Titre <span class="text-red-500">*</span>
                            </label>
                            <input type="text" wire:model.live.debounce.500ms="editTitle"
                                class="w-full px-4 py-2 text-sm rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent {{ empty($editTitle) ? 'border-red-300 dark:border-red-700' : 'border-gray-300 dark:border-gray-600' }}">
                            @error('editTitle') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Année d'édition (obligatoire) --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Année d'édition <span class="text-red-500">*</span>
                            </label>
                            <input type="number" wire:model.live.debounce.500ms="editEditionYear" min="1900" max="2100"
                                class="w-full px-4 py-2 text-sm rounded-lg border bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent {{ empty($editEditionYear) ? 'border-red-300 dark:border-red-700' : 'border-gray-300 dark:border-gray-600' }}">
                            @error('editEditionYear') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Texte alternatif --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Texte alternatif
                            </label>
                            <input type="text" wire:model="editAltText"
                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            @error('editAltText') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Description --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                Description
                            </label>
                            <textarea wire:model="editDescription" rows="3"
                                class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent"></textarea>
                            @error('editDescription') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                        </div>

                        {{-- Liens --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Texte du lien
                                </label>
                                <input type="text" wire:model="editLinkText"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    URL du lien
                                </label>
                                <input type="url" wire:model="editLinkUrl"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                @error('editLinkUrl') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Liens Calameo --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Texte du lien Calameo
                                </label>
                                <input type="text" wire:model="editCalameoLinkText"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    URL du lien Calameo
                                </label>
                                <input type="url" wire:model="editCalameoLinkUrl"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                @error('editCalameoLinkUrl') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Quantités --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantité disponible
                                </label>
                                <input type="number" wire:model="editQuantityAvailable" min="0"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                @error('editQuantityAvailable') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Quantité max par commande
                                </label>
                                <input type="number" wire:model="editMaxOrderQuantity" min="0"
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                @error('editMaxOrderQuantity') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Ordre d'affichage et Print --}}
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    Ordre d'affichage
                                </label>
                                <input type="number" wire:model="editDisplayOrder" min="0" placeholder="Ex: 1, 2, 3..."
                                    class="w-full px-4 py-2 text-sm rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-[#3E9B90] focus:border-transparent">
                                @if(count($usedDisplayOrders) > 0)
                                    <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                                        Ordres utilisés : {{ implode(', ', $usedDisplayOrders) }}
                                    </p>
                                @endif
                                @error('editDisplayOrder') <span class="text-sm text-red-500">{{ $message }}</span> @enderror
                            </div>
                            <div class="flex items-end pb-2">
                                <label class="flex items-center gap-2 text-sm font-medium text-gray-700 dark:text-gray-300">
                                    <input type="checkbox" wire:model="editPrintAvailable"
                                        class="rounded border-gray-300 dark:border-gray-600 dark:bg-gray-700 text-[#3E9B90] focus:ring-2 focus:ring-[#3E9B90]">
                                    Disponible à la commande
                                </label>
                            </div>
                        </div>

                        {{-- Gestion du fichier téléchargeable (PDF/Image) --}}
                        <div class="border-t border-gray-200 dark:border-gray-700 pt-4 mt-4">
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                PDF ou image téléchargeable
                            </label>

                            @if ($editingImage->pdf_path)
                                @php
                                    $editFileExtension = strtolower(pathinfo($editingImage->pdf_path, PATHINFO_EXTENSION));
                                    $editIsPdf = $editFileExtension === 'pdf';
                                @endphp
                                <div class="flex items-center gap-3 p-3 bg-gray-50 dark:bg-gray-800 rounded-lg mb-3">
                                    @if ($editIsPdf)
                                        <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                                d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path>
                                        </svg>
                                    @else
                                        <img src="{{ asset('storage/' . $editingImage->pdf_path) }}" alt="Preview" class="w-8 h-8 object-cover rounded">
                                    @endif
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900 dark:text-white">{{ $editIsPdf ? 'PDF actuel' : 'Image actuelle' }}</p>
                                        <a href="{{ asset('storage/' . $editingImage->pdf_path) }}" target="_blank"
                                            class="text-xs text-[#3E9B90] hover:underline">
                                            Voir le fichier
                                        </a>
                                    </div>
                                    <label class="flex items-center gap-2 text-sm text-red-600 cursor-pointer">
                                        <input type="checkbox" wire:model="removePdf"
                                            class="rounded border-gray-300 text-red-600 focus:ring-red-500">
                                        Supprimer
                                    </label>
                                </div>
                            @endif

                            <div>
                                <label class="block text-xs font-medium text-gray-700 dark:text-gray-300 mb-1">
                                    {{ $editingImage->pdf_path ? 'Remplacer le fichier' : 'Ajouter un PDF ou une image' }} (max 50MB)
                                </label>
                                <input type="file" wire:model="editPdfFile" accept=".pdf,.jpg,.jpeg,.png,application/pdf,image/jpeg,image/png"
                                    class="w-full text-sm text-gray-900 dark:text-white border border-gray-300 dark:border-gray-600 rounded-lg cursor-pointer bg-gray-50 dark:bg-gray-700 focus:outline-none px-3 py-2"
                                    {{ $removePdf ? 'disabled' : '' }}>
                                @error('editPdfFile')
                                    <span class="text-sm text-red-500 mt-1 block">{{ $message }}</span>
                                @enderror
                                <div wire:loading wire:target="editPdfFile" class="text-xs text-gray-500 mt-1">
                                    Chargement du fichier...
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Récapitulatif des champs manquants --}}
                    @if (!$this->canSaveEdit && count($this->missingEditFields) > 0)
                        <div class="mt-6 rounded-lg border border-amber-200 dark:border-amber-800 bg-amber-50 dark:bg-amber-900/20 p-4">
                            <div class="flex items-start gap-3">
                                <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path>
                                </svg>
                                <div class="flex-1">
                                    <p class="text-sm font-semibold text-amber-800 dark:text-amber-200 mb-1">
                                        Champs obligatoires manquants
                                    </p>
                                    <ul class="text-xs text-amber-700 dark:text-amber-300 list-disc list-inside space-y-0.5">
                                        @foreach ($this->missingEditFields as $field)
                                            <li>{{ $field }}</li>
                                        @endforeach
                                    </ul>
                                </div>
                            </div>
                        </div>
                    @endif

                    <div class="flex gap-3 justify-end mt-6">
                        <button type="button" wire:click="closeEditModal"
                            class="px-6 py-3 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-200 dark:bg-gray-700 hover:bg-gray-300 dark:hover:bg-gray-600 rounded-lg transition-colors">
                            Annuler
                        </button>

                        <button type="submit"
                            @disabled(!$this->canSaveEdit)
                            class="px-6 py-3 text-sm font-medium text-white bg-[#3E9B90] hover:bg-[#2d7a72] rounded-lg transition-colors shadow-md disabled:opacity-50 disabled:cursor-not-allowed disabled:bg-gray-400 dark:disabled:bg-gray-600 disabled:hover:bg-gray-400 dark:disabled:hover:bg-gray-600">
                            Enregistrer
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endif
