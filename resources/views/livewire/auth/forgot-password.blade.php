<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Mot de passe oublié')" :description="__('Entrez votre email pour recevoir un lien de réinitialisation')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="sendPasswordResetLink" class="flex flex-col gap-6">
        <!-- Email Address -->
        <flux:input wire:model="email" :label="__('Adresse email')" type="email" required autofocus
            placeholder="email@exemple.com" />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Envoyer le lien de réinitialisation') }}</flux:button>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-400">
        {{ __('Ou, retourner à la') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('connexion') }}</flux:link>
    </div>
</div>
