<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Jobs\SendUserApprovalEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'approved', 
        'approved_at',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'approved' => 'boolean',
            'approved_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

     /**
     * Vérifier si l'utilisateur est approuvé
     */
    public function isApproved(): bool
    {
        return $this->approved === true;
    }

     /**
     * Approuver l'utilisateur
     */
    public function approve(): void
    {
        $this->update([
            'approved' => true,
            'approved_at' => now(),
        ]);

        // Envoyer l'email de notification d'approbation
        SendUserApprovalEmail::dispatch($this);
    }

     /**
     * Désapprouver l'utilisateur
     */
    public function disapprove(): void
    {
        $this->update([
            'approved' => false,
            'approved_at' => null,
        ]);
    }

    /**
     * Get the user's initials
     */
    public function initials(): string
    {
        return Str::of($this->name)
            ->explode(' ')
            ->take(2)
            ->map(fn ($word) => Str::substr($word, 0, 1))
            ->implode('');
    }
}
