<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Créer un compte')" :description="__('Entrez vos informations ci-dessous pour créer votre compte')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        <flux:input wire:model="name" :label="__('Nom complet')" type="text" required autofocus autocomplete="name"
            :placeholder="__('Nom complet')" />

        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Adresse email')" type="email" required autocomplete="email"
            placeholder="email@example.com" />

        <!-- Password -->
        <flux:input wire:model="password" :label="__('Mot de passe')" type="password" required
            autocomplete="new-password" :placeholder="__('Mot de passe')" viewable />

        <!-- Confirm Password -->
        <flux:input wire:model="password_confirmation" :label="__('Confirmer le mot de passe')" type="password" required
            autocomplete="new-password" :placeholder="__('Confirmer le mot de passe')" viewable />

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Créer un compte') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Vous avez déjà un compte ?') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('Se connecter') }}</flux:link>
    </div>
</div>
