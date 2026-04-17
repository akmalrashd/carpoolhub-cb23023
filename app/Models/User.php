<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
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
        'role',
        'phone',
        'phone_visible',
        'profile_photo',
        'payment_account_name',
        'payment_account_number',
        'payment_bank_name',
        'payment_qr_duitnow',
        'payment_qr_tng',
        'is_active',
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
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
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

    public function billingCyclesClosed(): HasMany
    {
        return $this->hasMany(BillingCycle::class, 'closed_by');
    }

    public function userNotifications(): HasMany
    {
        return $this->hasMany(UserNotification::class);
    }

    public function archivedDrivenTrips(): HasMany
    {
        return $this->hasMany(ArchivedTrip::class, 'driver_id');
    }

    public function archivedTripParticipants(): HasMany
    {
        return $this->hasMany(ArchivedTripParticipant::class);
    }

    public function archivedTripPayments(): HasMany
    {
        return $this->hasMany(ArchivedTripPayment::class);
    }

    public function getWhatsappDigitsAttribute(): ?string
    {
        $raw = (string) ($this->phone ?? '');
        $digits = preg_replace('/\D+/', '', $raw);
        if (! $digits) {
            return null;
        }

        $knownCountryCodes = [
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

        // International prefix stored as 00 (e.g. 00601112844464 -> 601112844464).
        if (str_starts_with($digits, '00')) {
            $digits = ltrim($digits, '0');
        }

        // Keep numbers that already begin with a known international country code.
        foreach ($knownCountryCodes as $code) {
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

    public function getPaymentQrDuitnowUrlAttribute(): ?string
    {
        if (! $this->payment_qr_duitnow) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->payment_qr_duitnow);
    }

    public function getPaymentQrTngUrlAttribute(): ?string
    {
        if (! $this->payment_qr_tng) {
            return null;
        }

        return \Illuminate\Support\Facades\Storage::disk('public')->url($this->payment_qr_tng);
    }
}
