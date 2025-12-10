<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Réinitialiser le mot de passe')" :description="__('Veuillez entrer votre nouveau mot de passe ci-dessous')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="resetPassword" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input
            wire:model="email"
            :label="__('Adresse email')"
            type="email"
            required
            autocomplete="email"
        />

        <!-- Password -->
        <flux:input
            wire:model="password"
            :label="__('Nouveau mot de passe')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Mot de passe')"
            viewable
        />

        <!-- Confirm Password -->
        <flux:input
            wire:model="password_confirmation"
            :label="__('Confirmer le mot de passe')"
            type="password"
            required
            autocomplete="new-password"
            :placeholder="__('Confirmer le mot de passe')"
            viewable
        />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Réinitialiser le mot de passe') }}
            </flux:button>
        </div>
    </form>
</div>
