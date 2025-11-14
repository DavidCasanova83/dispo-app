<div class="p-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Gérer les rôles
    </h3>

    <div class="mb-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Utilisateur:</p>
        <p class="font-medium text-gray-900 dark:text-white">{{ $user->name }}</p>
        <p class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</p>
    </div>

    <div class="mb-6">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">Rôles disponibles:</p>
        <div class="space-y-3">
            @foreach($availableRoles as $role)
                <label class="flex items-center p-4 rounded-lg border-2 transition-all cursor-pointer {{ in_array($role->name, $selectedRoles) ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/20' : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                    <input
                        type="checkbox"
                        wire:click="toggleRole('{{ $role->name }}')"
                        {{ in_array($role->name, $selectedRoles) ? 'checked' : '' }}
                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                    >
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">{{ $role->name }}</span>
                            @if(in_array($role->name, $selectedRoles))
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">
                            @if($role->name === 'Super-admin')
                                Accès complet + gestion des utilisateurs
                            @elseif($role->name === 'Admin')
                                Accès complet sauf gestion des utilisateurs
                            @elseif($role->name === 'Qualification')
                                Accès section qualification et stats
                            @elseif($role->name === 'Disponibilites')
                                Accès informations hébergement
                            @elseif($role->name === 'Utilisateurs')
                                Accès formulaires des villes uniquement
                            @endif
                        </p>
                        <div class="mt-2">
                            <p class="text-xs font-medium text-gray-600 dark:text-gray-400">Permissions:</p>
                            <div class="flex flex-wrap gap-1 mt-1">
                                @foreach($role->permissions as $permission)
                                    <span class="px-2 py-0.5 text-xs rounded bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300">
                                        {{ $permission->name }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex gap-3 justify-end border-t border-gray-200 dark:border-gray-700 pt-4">
        <button
            wire:click="$dispatch('closeModal')"
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
            Annuler
        </button>
        <button
            wire:click="save"
            type="button"
            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors"
        >
            Enregistrer
        </button>
    </div>
</div>
