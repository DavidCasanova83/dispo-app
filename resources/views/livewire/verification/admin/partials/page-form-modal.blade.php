{{-- Formulaire « Ajouter / Modifier une page » (trait EditsVerificationPage). Attend $availableUsers à la création. --}}
<div class="fixed inset-0 z-50 overflow-y-auto" wire:key="page-form-modal">
    <div class="flex min-h-screen items-center justify-center p-4">
        <div class="fixed inset-0 bg-black/50" wire:click="closeFormModal"></div>
        <div class="relative bg-white dark:bg-gray-800 rounded-xl shadow-xl w-full max-w-2xl p-6">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-4">
                {{ $editingId ? 'Modifier la page' : 'Ajouter une page à vérifier' }}
            </h2>
            <form wire:submit.prevent="savePage" class="space-y-4">
                <div>
                    <label for="page-title" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Titre *</label>
                    <input id="page-title" type="text" wire:model="title"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    @error('title') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div>
                    <label for="page-url" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse FR *</label>
                    <input id="page-url" type="url" wire:model="url" placeholder="https://www.verdontourisme.com/…"
                        class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                    @error('url') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="page-url-en" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse EN</label>
                        <input id="page-url-en" type="url" wire:model="urlEn" placeholder="https://www.verdontourisme.com/en/…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        @error('urlEn') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="page-url-it" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Adresse IT</label>
                        <input id="page-url-it" type="url" wire:model="urlIt" placeholder="https://www.verdontourisme.com/it/…"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        @error('urlIt') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                <p class="text-xs text-gray-500 dark:text-gray-400 -mt-2">
                    Les adresses EN et IT servent aux traducteurs (module Traductions) ; les relecteurs ne relisent que la version française.
                </p>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="page-category" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Catégorie</label>
                        <select id="page-category" wire:model="category"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            <option value="">Aucune</option>
                            @foreach (\App\Models\VerificationPage::CATEGORIES as $code => $label)
                                <option value="{{ $code }}">{{ $label }}</option>
                            @endforeach
                        </select>
                        @error('category') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label for="page-priority" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Priorité</label>
                        <select id="page-priority" wire:model="priority"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                            <option value="high">Haute</option>
                            <option value="medium">Moyenne</option>
                            <option value="low">Basse</option>
                        </select>
                    </div>
                    <div>
                        <label for="page-deadline" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Échéance</label>
                        <input id="page-deadline" type="date" wire:model="deadline"
                            class="w-full rounded-lg border border-gray-300 dark:border-gray-600 bg-white dark:bg-gray-700 px-3 py-2 text-sm text-gray-900 dark:text-white">
                        @error('deadline') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                </div>
                @unless ($editingId)
                    <div>
                        <p class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-1">Relecteurs</p>
                        <div class="border border-gray-300 dark:border-gray-600 rounded-lg p-3 max-h-48 overflow-y-auto bg-white dark:bg-gray-700">
                            @forelse ($availableUsers as $user)
                                <label class="flex items-center gap-2 py-1 cursor-pointer">
                                    <input type="checkbox" value="{{ $user->id }}" wire:model="assigneeIds"
                                        class="rounded border-gray-300 text-gray-900 focus:ring-gray-900">
                                    <span class="text-sm text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    <span class="text-xs text-gray-500">{{ $user->email }}</span>
                                </label>
                            @empty
                                <p class="text-sm text-gray-500 italic">Aucun utilisateur approuvé.</p>
                            @endforelse
                        </div>
                        @error('assigneeIds.*') <p class="mt-1 text-xs text-red-600">{{ $message }}</p> @enderror
                    </div>
                @endunless
                <div class="flex justify-end gap-2 pt-2">
                    <button type="button" wire:click="closeFormModal"
                        class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 bg-gray-100 dark:bg-gray-700 hover:bg-gray-200 rounded-lg">
                        Annuler
                    </button>
                    <button type="submit"
                        class="px-4 py-2 text-sm font-medium text-white bg-gray-900 hover:bg-gray-800 dark:bg-white dark:text-gray-900 dark:hover:bg-gray-100 rounded-lg">
                        {{ $editingId ? 'Enregistrer' : 'Ajouter la page' }}
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
