<?php

use App\Models\Otp;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Mail;
use App\Mail\WelcomeMail;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $email = '';
    public string $otp_code = '';
    public ?Otp $pendingOtp = null;
    public int $timeRemaining = 0;

    public function mount(): void
    {
        $this->email = session('otp_email', '');
        
        if (!$this->email) {
            $this->redirect(route('register'), navigate: true);
            return;
        }

        $this->loadPendingOtp();
    }

    public function loadPendingOtp(): void
    {
        try {
            $this->pendingOtp = Otp::where('email', $this->email)
                ->where('is_verified', false)
                ->where('expires_at', '>', now())
                ->first();

            if ($this->pendingOtp) {
                $this->timeRemaining = max(0, now()->diffInSeconds($this->pendingOtp->expires_at, false));
            } else {
                $this->timeRemaining = 0;
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to load pending OTP: ' . $e->getMessage());
            $this->pendingOtp = null;
            $this->timeRemaining = 0;
        }
    }

    public function updateTimer(): void
    {
        $this->loadPendingOtp();
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp_code' => ['required', 'string', 'size:6'],
        ]);

        try {
            $otp = Otp::verifyOtp($this->email, $this->otp_code);

            if (!$otp) {
                $this->addError('otp_code', 'Invalid or expired OTP code.');
                return;
            }

            // Mark OTP as verified
            $otp->markAsVerified();

            // Create the user account
            $userData = $otp->user_data;
            $userData['password'] = Hash::make($userData['password']);
            $userData['role'] = 'User';

            $user = User::create($userData);

            // Fire registered event
            event(new Registered($user));

            // Log the user in
            Auth::login($user);

            // Clear session data
            session()->forget(['otp_email']);

            // Redirect to dashboard or extra info
            $this->redirect(route('extra.info', absolute: false), navigate: true);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to verify OTP: ' . $e->getMessage());
            $this->addError('otp_code', 'Unable to verify OTP due to database connection issues. Please try again later.');
        }
    }

    public function resendOtp(): void
    {
        try {
            $existingOtp = Otp::where('email', $this->email)
                ->where('is_verified', false)
                ->first();

            if (!$existingOtp) {
                $this->addError('email', 'No pending registration found for this email.');
                return;
            }

            // Create new OTP with same user data
            $newOtp = Otp::createForEmail($this->email, $existingOtp->user_data);

            // Send OTP email
            \Illuminate\Support\Facades\Mail::to($this->email)->send(
                new \App\Mail\OtpVerificationMail($newOtp->otp_code, $existingOtp->user_data['name'] ?? 'User')
            );

            $this->loadPendingOtp();
            session()->flash('status', 'New OTP has been sent to your email.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to resend OTP: ' . $e->getMessage());
            $this->addError('email', 'Unable to resend OTP due to database connection issues. Please try again later.');
        }
    }

    public function goBack(): void
    {
        try {
            // Clear any pending OTP
            Otp::where('email', $this->email)->delete();
        } catch (\Exception $e) {
            // Log the error but continue with redirect
            \Illuminate\Support\Facades\Log::warning('Failed to delete OTP during goBack: ' . $e->getMessage());
        }
        
        session()->forget(['otp_email']);
        
        $this->redirect(route('register'), navigate: true);
    }
}; ?>

<div wire:poll.1s="updateTimer" class="flex flex-col gap-6">
    <x-auth-header 
        :title="__('Verify Your Email')" 
        :description="__('We\'ve sent a 6-digit verification code to your email address')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    @if($pendingOtp && $timeRemaining > 0)
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4 text-center">
            <p class="text-sm text-blue-800">
                Code sent to: <strong>{{ $email }}</strong>
            </p>
            <p class="text-sm text-blue-600 mt-1">
                Time remaining: <span class="font-mono font-bold">{{ gmdate('i:s', $timeRemaining) }}</span>
            </p>
        </div>
    @elseif($pendingOtp)
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-sm text-red-800">
                Your OTP has expired. Please request a new one.
            </p>
        </div>
    @else
        <div class="bg-red-50 border border-red-200 rounded-lg p-4 text-center">
            <p class="text-sm text-red-800">
                No valid OTP found. Please register again.
            </p>
        </div>
    @endif

    <form wire:submit="verifyOtp" class="flex flex-col gap-6">
        <!-- Email Display -->
        <div class="text-center">
            <p class="text-sm text-gray-600">
                Enter the 6-digit code sent to:
            </p>
            <p class="font-medium text-gray-900">{{ $email }}</p>
        </div>

        <!-- OTP Input -->
        <div class="flex justify-center">
            <flux:input
                wire:model="otp_code"
                :label="__('Verification Code')"
                type="text"
                required
                autofocus
                maxlength="6"
                :placeholder="__('000000')"
                class="text-center text-2xl font-mono tracking-widest w-48"
                inputmode="numeric"
                pattern="[0-9]{6}"
            />
        </div>

        <div class="flex flex-col gap-3">
            <flux:button 
                type="submit" 
                variant="primary" 
                class="w-full"
                :disabled="!$pendingOtp || $timeRemaining <= 0"
            >
                {{ __('Verify Code') }}
            </flux:button>

            <div class="flex gap-2">
                <flux:button 
                    type="button" 
                    variant="outline" 
                    class="flex-1"
                    wire:click="resendOtp"
                    :disabled="$timeRemaining > 0"
                >
                    {{ __('Resend Code') }}
                </flux:button>

                <flux:button 
                    type="button" 
                    variant="ghost" 
                    class="flex-1"
                    wire:click="goBack"
                >
                    {{ __('Back to Register') }}
                </flux:button>
            </div>
        </div>
    </form>
</div>
