<div class="p-6">
    <h3 class="text-lg font-medium text-gray-900 dark:text-white mb-2">
        Gérer les permissions
    </h3>

    <div class="mb-6">
        <p class="text-sm text-gray-600 dark:text-gray-400 mb-1">Rôle:</p>
        <p class="font-medium text-gray-900 dark:text-white">{{ $role->name }}</p>
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
    </div>

    <div class="mb-6">
        <p class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
            Permissions disponibles:
        </p>
        <div class="space-y-2 max-h-64 overflow-y-auto">
            @foreach($allPermissions as $permission)
                <label class="flex items-center p-3 rounded-lg border-2 transition-all cursor-pointer
                    {{ in_array($permission->name, $selectedPermissions)
                        ? 'border-green-500 bg-green-50 dark:bg-green-900/20'
                        : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600' }}">
                    <input
                        type="checkbox"
                        wire:click="togglePermission('{{ $permission->name }}')"
                        {{ in_array($permission->name, $selectedPermissions) ? 'checked' : '' }}
                        class="w-4 h-4 text-green-600 bg-gray-100 border-gray-300 rounded
                               focus:ring-green-500 dark:focus:ring-green-600
                               dark:ring-offset-gray-800 focus:ring-2
                               dark:bg-gray-700 dark:border-gray-600"
                    >
                    <div class="ml-3 flex-1">
                        <div class="flex items-center justify-between">
                            <span class="text-sm font-medium text-gray-900 dark:text-white">
                                {{ $permission->name }}
                            </span>
                            @if(in_array($permission->name, $selectedPermissions))
                                <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                                </svg>
                            @endif
                        </div>
                        <p class="text-xs text-gray-500 dark:text-gray-400 mt-0.5">
                            {{ $this->getPermissionDescription($permission->name) }}
                        </p>
                    </div>
                </label>
            @endforeach
        </div>
    </div>

    <div class="flex gap-3 justify-end border-t border-gray-200 dark:border-gray-700 pt-4">
        <button
            wire:click="$dispatch('closePermissionsModal')"
            type="button"
            class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-gray-300
                   hover:bg-gray-100 dark:hover:bg-gray-700 rounded-lg transition-colors"
        >
            Annuler
        </button>
        <button
            wire:click="save"
            type="button"
            class="px-4 py-2 text-sm font-medium text-white bg-green-600
                   hover:bg-green-700 rounded-lg transition-colors"
        >
            Enregistrer les permissions
        </button>
    </div>
</div>
