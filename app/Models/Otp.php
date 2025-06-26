<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Otp extends Model
{
    protected $fillable = [
        'email',
        'otp_code',
        'expires_at',
        'is_verified',
        'user_data'
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_verified' => 'boolean',
        'user_data' => 'array'
    ];

    /**
     * Generate a 6-digit OTP code
     */
    public static function generateOtpCode(): string
    {
        return str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create a new OTP for email verification
     */
    public static function createForEmail(string $email, array $userData = []): self
    {
        // Delete any existing OTPs for this email
        self::where('email', $email)->delete();

        return self::create([
            'email' => $email,
            'otp_code' => self::generateOtpCode(),
            'expires_at' => Carbon::now()->addMinutes(3), // 3 minutes validity
            'user_data' => $userData,
            'is_verified' => false
        ]);
    }

    /**
     * Verify OTP code
     */
    public static function verifyOtp(string $email, string $otpCode): ?self
    {
        return self::where('email', $email)
            ->where('otp_code', $otpCode)
            ->where('expires_at', '>', Carbon::now())
            ->where('is_verified', false)
            ->first();
    }

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at < Carbon::now();
    }

    /**
     * Mark OTP as verified
     */
    public function markAsVerified(): void
    {
        $this->update(['is_verified' => true]);
    }
}
