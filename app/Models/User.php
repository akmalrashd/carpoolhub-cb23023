<?php

namespace App\Models;

use App\Notifications\ResetPasswordNotification;
use App\Notifications\VerifyEmailNotification;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Schema;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_visible',
        'password',
        'google_id',
        'role',
        'phone',
        'phone_visible',
        'vehicle_model',
        'vehicle_plate',
        'profile_photo',
        'payment_account_name',
        'payment_account_number',
        'payment_bank_name',
        'payment_qr_duitnow',
        'payment_qr_tng',
        'driving_license_photo',
        'selfie_photo',
        'is_active',
        'driver_verification_status',
        'driver_verification_reason',
        'driver_verified_at',
        'driver_reviewed_by',
        'driving_license_expiry',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
        'telegram_chat_id',
        'telegram_username',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
            'driving_license_expiry' => 'date',
            'driver_verified_at' => 'datetime',
        ];
    }

    /**
     * The heavy base64 image columns (~5-7 MB each). They are only ever shown on
     * the admin user page and the owner's own settings, so list/eager-load
     * queries for other pages should not drag them into memory.
     *
     * @var list<string>
     */
    public const HEAVY_MEDIA_COLUMNS = ['driving_license_photo', 'selfie_photo'];

    /**
     * Select every user column EXCEPT the heavy image blobs. Selecting
     * all-but-heavy (rather than an allowlist) guarantees no display column —
     * name, avatar, payment fields, accessors' source columns — is ever missed,
     * so this stays behaviour-preserving while dropping the megabytes.
     */
    public function scopeWithoutHeavyMedia(Builder $query): Builder
    {
        static $columns = null;

        if ($columns === null) {
            $columns = array_values(array_diff(
                Schema::getColumnListing($this->getTable()),
                self::HEAVY_MEDIA_COLUMNS
            ));
        }

        return $query->select($columns);
    }

    /**
     * Resolve one of the two contact-visibility settings against a viewer.
     *
     * The settings page has always let users choose who can see their email and
     * phone (public / connections only / hidden) and SettingsService has always
     * stored the choice — but nothing ever read it back, so "hidden" displayed
     * the address to anyone regardless. This is the missing half.
     *
     * Takes an already-known connection flag rather than querying, so callers
     * rendering a list resolve visibility without a query per row.
     */
    public function showsContactTo(?string $visibility, bool $viewerIsConnection): bool
    {
        // Matches SettingsService: an unset value behaves as visible_friend.
        return match ((string) ($visibility ?: 'visible_friend')) {
            'visible_public' => true,
            'visible_friend' => $viewerIsConnection,
            default => false,
        };
    }

    public function showsEmailTo(bool $viewerIsConnection): bool
    {
        return $this->showsContactTo($this->email_visible, $viewerIsConnection);
    }

    public function showsPhoneTo(bool $viewerIsConnection): bool
    {
        return $this->showsContactTo($this->phone_visible, $viewerIsConnection);
    }

    public function requestedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'requester_id');
    }

    public function receivedConnections(): HasMany
    {
        return $this->hasMany(Connection::class, 'receiver_id');
    }

    public function acceptedConnections(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            'connections',
            'requester_id',
            'receiver_id'
        )->wherePivot('status', 'accepted')->withTimestamps();
    }

    public function savedRoutes(): HasMany
    {
        return $this->hasMany(SavedRoute::class);
    }

    public function drivenTrips(): HasMany
    {
        return $this->hasMany(Trip::class, 'driver_id');
    }

    public function tripParticipants(): HasMany
    {
        return $this->hasMany(TripParticipant::class);
    }

    public function tripPayments(): HasMany
    {
        return $this->hasMany(TripPayment::class);
    }

    public function tripJoinRequests(): HasMany
    {
        return $this->hasMany(TripJoinRequest::class, 'user_id');
    }

    public function riskProfile(): HasOne
    {
        return $this->hasOne(PassengerRiskProfile::class);
    }

    public function reviewedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'driver_reviewed_by');
    }

    /**
     * A pending or rejected driver has is_active=false like any other
     * deactivated account, but — unlike a plain suspension — there IS
     * something they can do about it themselves: update their documents in
     * Settings and resubmit. EnsureUserIsActive and the login controllers
     * both consult this single definition so a driver in this state can
     * reach exactly Settings/Notifications/Logout while staying blocked from
     * everything else, without the three call sites risking drifting apart
     * on what counts as "awaiting self-service".
     */
    public function isDriverAwaitingSelfService(): bool
    {
        return $this->role === 'driver'
            && in_array($this->driver_verification_status, ['pending', 'rejected'], true);
    }

    public function sendPasswordResetNotification(#[\SensitiveParameter] $token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }

    /**
     * A Google sign-in already proves the address is real and owned by
     * whoever is signing in — Google verified it before ever handing us the
     * email — so linking a google_id counts as verified even if
     * email_verified_at itself is still empty.
     */
    public function hasVerifiedEmail(): bool
    {
        return $this->email_verified_at !== null || $this->google_id !== null;
    }

    public function sendEmailVerificationNotification(): void
    {
        $this->notify(new VerifyEmailNotification());
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    /**
     * ITU country calling codes, used only to recognise a phone number that
     * already carries one. A literal in the accessor body was rebuilt on every
     * single call — this list never changes at runtime, so it belongs on the
     * class instead of being re-allocated per row rendered.
     *
     * @var list<string>
     */
    private const KNOWN_COUNTRY_CODES = [
        '1', '7', '20', '27', '30', '31', '32', '33', '34', '36', '39',
        '40', '41', '43', '44', '45', '46', '47', '48', '49',
        '51', '52', '53', '54', '55', '56', '57', '58',
        '60', '61', '62', '63', '64', '65', '66',
        '81', '82', '84', '86', '90', '91', '92', '93', '94', '95', '98',
        '211', '212', '213', '216', '218',
        '220', '221', '222', '223', '224', '225', '226', '227', '228', '229',
        '230', '231', '232', '233', '234', '235', '236', '237', '238', '239',
        '240', '241', '242', '243', '244', '245', '246', '248', '249',
        '250', '251', '252', '253', '254', '255', '256', '257', '258',
        '260', '261', '262', '263', '264', '265', '266', '267', '268', '269',
        '290', '291', '297', '298', '299',
        '350', '351', '352', '353', '354', '355', '356', '357', '358', '359',
        '370', '371', '372', '373', '374', '375', '376', '377', '378', '380',
        '381', '382', '383', '385', '386', '387', '389',
        '420', '421', '423',
        '500', '501', '502', '503', '504', '505', '506', '507', '508', '509',
        '590', '591', '592', '593', '594', '595', '596', '597', '598', '599',
        '670', '672', '673', '674', '675', '676', '677', '678', '679', '680',
        '681', '682', '683', '685', '686', '687', '688', '689', '690', '691',
        '692', '850', '852', '853', '855', '856', '880', '886',
        '960', '961', '962', '963', '964', '965', '966', '967', '968', '970',
        '971', '972', '973', '974', '975', '976', '977', '992', '993', '994',
        '995', '996', '998',
    ];

    public function getWhatsappDigitsAttribute(): ?string
    {
        $raw = (string) ($this->phone ?? '');
        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        // International prefix stored as 00 (e.g. 00601112844464 -> 601112844464).
        if (str_starts_with($digits, '00')) {
            $digits = ltrim($digits, '0');
        }

        // Keep numbers that already begin with a known international country code.
        foreach (self::KNOWN_COUNTRY_CODES as $code) {
            if (str_starts_with($digits, $code) && strlen($digits) > strlen($code) + 5) {
                return $digits;
            }
        }

        // Backward compatibility for legacy local MY numbers saved like 01xxxxxxxx.
        if (preg_match('/^01\d{8,9}$/', $digits) === 1) {
            $digits = '60' . ltrim($digits, '0');
        }

        return $digits !== '' ? $digits : null;
    }

    public function getWhatsappUrlAttribute(): ?string
    {
        return $this->whatsapp_digits ? ('https://wa.me/' . $this->whatsapp_digits) : null;
    }

    /**
     * Resolve an image column to a usable <img src>. Images are stored as base64
     * data URIs (returned as-is), but older rows may still hold a storage path
     * (resolved to a public URL) — so both keep working during and after the
     * switch to base64. A Google sign-in stores its avatar as a plain
     * https:// URL (googleusercontent.com), which also needs to pass through
     * unchanged rather than being treated as a relative storage path.
     */
    private function imageSrc(?string $value): ?string
    {
        if (! $value) {
            return null;
        }

        if (str_starts_with($value, 'data:') || str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        return asset('storage/' . $value);
    }

    public function getProfilePhotoUrlAttribute(): ?string
    {
        return $this->imageSrc($this->profile_photo);
    }

    public function getPaymentQrDuitnowUrlAttribute(): ?string
    {
        return $this->imageSrc($this->payment_qr_duitnow);
    }

    public function getPaymentQrTngUrlAttribute(): ?string
    {
        return $this->imageSrc($this->payment_qr_tng);
    }

}
