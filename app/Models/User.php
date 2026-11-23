<?php

namespace App\Models;

use App\Enums\UserRole;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

#[Fillable(['name', 'email', 'phone', 'role', 'locale', 'avatar_path', 'referral_code', 'referred_by', 'is_active', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Valeurs par défaut des attributs en mémoire.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_active' => true,
        'locale' => 'fr',
    ];

    protected static function booted(): void
    {
        static::creating(function (self $user): void {
            if (! $user->referral_code) {
                $user->referral_code = strtoupper(\Illuminate\Support\Str::random(8));
            }
        });
    }

    /**
     * Définit les conversions de types des attributs.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'role' => UserRole::class,
            'is_active' => 'boolean',
        ];
    }

    /**
     * Profil annonceur associé.
     */
    public function advertiserProfile(): HasOne
    {
        return $this->hasOne(AdvertiserProfile::class);
    }

    /**
     * Profil éditeur associé.
     */
    public function publisherProfile(): HasOne
    {
        return $this->hasOne(PublisherProfile::class);
    }

    /**
     * Portefeuille XAF de l'utilisateur.
     */
    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    /**
     * Moyens de paiement enregistrés (MTN MoMo / Orange Money).
     */
    public function paymentMethods(): HasMany
    {
        return $this->hasMany(PaymentMethod::class);
    }

    /**
     * Campagnes créées par l'annonceur.
     */
    public function campaigns(): HasMany
    {
        return $this->hasMany(Campaign::class, 'advertiser_id');
    }

    /**
     * Sites web enregistrés par l'éditeur.
     */
    public function sites(): HasMany
    {
        return $this->hasMany(Site::class, 'publisher_id');
    }

    /**
     * Comptes de réseaux sociaux de l'éditeur.
     */
    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class, 'publisher_id');
    }

    /**
     * Posts sponsorisés (placements sociaux) de l'éditeur.
     */
    public function socialPlacements(): HasMany
    {
        return $this->hasMany(SocialPlacement::class, 'publisher_id');
    }

    public function isAdmin(): bool
    {
        return $this->role === UserRole::Admin;
    }

    public function isAdvertiser(): bool
    {
        return $this->role === UserRole::Advertiser;
    }

    public function isPublisher(): bool
    {
        return $this->role === UserRole::Publisher;
    }

    /**
     * Retourne l'URL publique de la photo de profil ou null.
     */
    public function avatarUrl(): ?string
    {
        return $this->avatar_path
            ? \Illuminate\Support\Facades\Storage::disk('public')->url($this->avatar_path)
            : null;
    }

    /**
     * Génère les initiales (2 lettres) à partir du nom complet.
     */
    public function initials(): string
    {
        $parts = preg_split('/\s+/', trim((string) $this->name)) ?: [];
        $first = mb_substr($parts[0] ?? 'U', 0, 1);
        $second = isset($parts[1]) ? mb_substr($parts[1], 0, 1) : mb_substr($parts[0] ?? '', 1, 1);

        return mb_strtoupper($first.$second);
    }
}
