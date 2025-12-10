<?php

namespace App\Livewire\Auth;

use App\Models\User;
use App\Services\MailjetService;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.auth')]
class ForgotPassword extends Component
{
    public string $email = '';

    /**
     * Send a password reset link to the provided email address.
     */
    public function sendPasswordResetLink(): void
    {
        $this->validate([
            'email' => ['required', 'string', 'email'],
        ]);

        // Trouver l'utilisateur
        $user = User::where('email', $this->email)->first();

        // Message générique pour ne pas révéler si l'email existe
        $genericMessage = 'Si un compte existe avec cette adresse email, vous recevrez un lien de réinitialisation.';

        if (!$user) {
            // Ne pas révéler que l'utilisateur n'existe pas
            session()->flash('status', $genericMessage);
            return;
        }

        // Générer le token de reset
        $token = Password::createToken($user);

        // Construire l'URL de reset
        $resetUrl = url(route('password.reset', [
            'token' => $token,
            'email' => $user->email,
        ], false));

        // Envoyer l'email via Mailjet
        $mailjetService = app(MailjetService::class);
        $result = $mailjetService->sendPasswordResetEmail(
            $user->email,
            $user->name,
            $resetUrl
        );

        if ($result['success']) {
            session()->flash('status', $genericMessage);
        } else {
            $this->addError('email', 'Une erreur est survenue lors de l\'envoi de l\'email. Veuillez réessayer.');
        }
    }
}
