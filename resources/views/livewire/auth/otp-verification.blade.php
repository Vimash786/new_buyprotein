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
    public bool $isExpired = false;
    public bool $resendCooldown = false;
    public int $resendCountdown = 0;

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
                $this->timeRemaining = max(0, (int) now()->diffInSeconds($this->pendingOtp->expires_at, false));
                $this->isExpired = false;
            } else {
                $this->timeRemaining = 0;
                // Check if there's an expired OTP
                $expiredOtp = Otp::where('email', $this->email)->where('is_verified', false)->first();
                $this->isExpired = $expiredOtp !== null;
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

    // Called from JS when OTP input is fully entered (6 digits)
    public function autoVerify(): void
    {
        if (strlen($this->otp_code) === 6) {
            $this->verifyOtp();
        }
    }

    public function verifyOtp(): void
    {
        $this->validate([
            'otp_code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ], [
            'otp_code.required' => 'Please enter the 6-digit code.',
            'otp_code.size'     => 'The code must be exactly 6 digits.',
            'otp_code.regex'    => 'Only numbers are allowed.',
        ]);

        try {
            $otp = Otp::verifyOtp($this->email, $this->otp_code);

            if (!$otp) {
                $this->addError('otp_code', 'Invalid or expired code. Please check and try again.');
                $this->otp_code = '';
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

            // Redirect to extra info
            $this->redirect(route('extra.info', absolute: false), navigate: true);
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to verify OTP: ' . $e->getMessage());
            $this->addError('otp_code', 'Something went wrong. Please try again.');
        }
    }

    public function resendOtp(): void
    {
        if ($this->timeRemaining > 0) return; // Block if still active

        try {
            $existingOtp = Otp::where('email', $this->email)
                ->where('is_verified', false)
                ->first();

            if (!$existingOtp) {
                $this->addError('otp_code', 'No pending registration found for this email.');
                return;
            }

            // Create new OTP with same user data
            $newOtp = Otp::createForEmail($this->email, $existingOtp->user_data);

            // Send OTP email
            \Illuminate\Support\Facades\Mail::to($this->email)->send(
                new \App\Mail\OtpVerificationMail($newOtp->otp_code, $existingOtp->user_data['name'] ?? 'User')
            );

            $this->otp_code = '';
            $this->resetErrorBag();
            $this->loadPendingOtp();
            session()->flash('status', 'A new code has been sent to your email.');
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to resend OTP: ' . $e->getMessage());
            $this->addError('otp_code', 'Unable to resend code. Please try again later.');
        }
    }

    public function goBack(): void
    {
        try {
            Otp::where('email', $this->email)->delete();
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::warning('Failed to delete OTP during goBack: ' . $e->getMessage());
        }
        session()->forget(['otp_email']);
        $this->redirect(route('register'), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6" wire:poll.1s="updateTimer">
    <x-auth-header 
        :title="__('Verify Your Email')" 
        :description="__('Enter the 6-digit code we sent to your email')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    {{-- Email Badge --}}
    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl px-4 py-3 flex items-center gap-3">
        <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/50 flex items-center justify-center shrink-0">
            <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
            </svg>
        </div>
        <div class="min-w-0">
            <p class="text-xs text-gray-500 dark:text-gray-400">Code sent to</p>
            <p class="text-sm font-medium text-gray-900 dark:text-white truncate">{{ $email }}</p>
        </div>
        <button wire:click="goBack" class="ml-auto text-xs text-blue-600 dark:text-blue-400 hover:underline shrink-0">Change</button>
    </div>

    {{-- Timer / Expired Banner --}}
    @if($pendingOtp && $timeRemaining > 0)
        <div class="rounded-xl border {{ $timeRemaining <= 30 ? 'bg-amber-50 dark:bg-amber-900/20 border-amber-200 dark:border-amber-800' : 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' }} px-4 py-3 flex items-center justify-between">
            <p class="text-sm {{ $timeRemaining <= 30 ? 'text-amber-800 dark:text-amber-300' : 'text-blue-800 dark:text-blue-300' }}">
                {{ $timeRemaining <= 30 ? '⚠️ Code expiring soon!' : '✉️ Code is active' }}
            </p>
            <span class="font-mono font-bold text-lg {{ $timeRemaining <= 30 ? 'text-amber-600 dark:text-amber-400' : 'text-blue-600 dark:text-blue-400' }}">
                {{ gmdate('i:s', $timeRemaining) }}
            </span>
        </div>
    @elseif($isExpired || ($pendingOtp && $timeRemaining <= 0))
        <div class="rounded-xl border bg-red-50 dark:bg-red-900/20 border-red-200 dark:border-red-800 px-4 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-red-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm text-red-800 dark:text-red-300">Your code has expired. Please request a new one below.</p>
        </div>
    @else
        <div class="rounded-xl border bg-gray-50 dark:bg-zinc-800 border-gray-200 dark:border-zinc-700 px-4 py-3 flex items-center gap-3">
            <svg class="w-5 h-5 text-gray-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <p class="text-sm text-gray-600 dark:text-gray-400">No valid code found. Please request a new one.</p>
        </div>
    @endif

    {{-- OTP Form --}}
    <form wire:submit="verifyOtp" class="flex flex-col gap-5">

        {{-- 6-digit Input --}}
        <div class="flex flex-col items-center gap-2">
            <input
                id="otp_input"
                type="text"
                wire:model.live="otp_code"
                inputmode="numeric"
                pattern="[0-9]*"
                maxlength="6"
                autocomplete="one-time-code"
                placeholder="• • • • • •"
                autofocus
                class="w-52 text-center text-3xl font-mono tracking-[0.5em] py-3 px-4
                       border-2 rounded-xl bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                       placeholder-gray-300 dark:placeholder-gray-600
                       focus:outline-none focus:ring-2 focus:ring-blue-500
                       transition-colors duration-150
                       {{ $errors->has('otp_code') ? 'border-red-400 dark:border-red-500' : (strlen($otp_code) === 6 && !$errors->has('otp_code') ? 'border-green-400' : 'border-gray-300 dark:border-gray-600') }}"
                x-on:input="
                    // strip non-digits
                    $event.target.value = $event.target.value.replace(/[^0-9]/g, '').slice(0, 6);
                    $wire.set('otp_code', $event.target.value);
                    // auto-submit when 6 digits entered
                    if ($event.target.value.length === 6) {
                        setTimeout(() => $wire.verifyOtp(), 300);
                    }
                "
            />

            @error('otp_code')
                <p class="text-xs text-red-500 flex items-center gap-1">
                    <svg class="w-3 h-3 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                    {{ $message }}
                </p>
            @enderror

            {{-- Progress dots --}}
            <div class="flex gap-2 mt-1">
                @for($i = 0; $i < 6; $i++)
                    <div class="w-2 h-2 rounded-full transition-colors duration-150
                        {{ $i < strlen($otp_code) ? 'bg-blue-500' : 'bg-gray-200 dark:bg-zinc-600' }}">
                    </div>
                @endfor
            </div>

            {{-- Loading indicator when verifying --}}
            <div wire:loading wire:target="verifyOtp" class="flex items-center gap-2 text-sm text-blue-600">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                </svg>
                Verifying...
            </div>
        </div>

        {{-- Verify Button --}}
        <button
            type="submit"
            @if(!$pendingOtp || $timeRemaining <= 0) disabled @endif
            class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                   transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2
                   {{ (!$pendingOtp || $timeRemaining <= 0)
                       ? 'bg-gray-200 dark:bg-zinc-700 text-gray-400 dark:text-gray-500 cursor-not-allowed'
                       : 'bg-blue-600 hover:bg-blue-700 text-white' }}"
        >
            <span wire:loading.remove wire:target="verifyOtp">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </span>
            <span wire:loading.remove wire:target="verifyOtp">Verify Code</span>
            <span wire:loading wire:target="verifyOtp" class="flex items-center gap-2">
                <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                Verifying...
            </span>
        </button>

        {{-- Resend & Back --}}
        <div class="flex gap-2">
            <button
                type="button"
                wire:click="resendOtp"
                wire:loading.attr="disabled"
                @if($timeRemaining > 0) disabled @endif
                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium border transition-colors duration-150
                       {{ $timeRemaining > 0
                           ? 'border-gray-200 dark:border-zinc-700 text-gray-400 dark:text-gray-500 cursor-not-allowed bg-transparent'
                           : 'border-blue-300 dark:border-blue-600 text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 bg-transparent' }}"
            >
                <span wire:loading.remove wire:target="resendOtp">
                    @if($timeRemaining > 0)
                        Resend in {{ gmdate('i:s', $timeRemaining) }}
                    @else
                        Resend Code
                    @endif
                </span>
                <span wire:loading wire:target="resendOtp" class="flex items-center justify-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Sending...
                </span>
            </button>

            <button
                type="button"
                wire:click="goBack"
                class="flex-1 px-4 py-2.5 rounded-lg text-sm font-medium text-gray-600 dark:text-gray-300
                       hover:bg-gray-100 dark:hover:bg-zinc-700 transition-colors duration-150"
            >
                ← Back
            </button>
        </div>
    </form>

    {{-- Help text --}}
    <p class="text-xs text-center text-gray-400 dark:text-gray-500">
        Didn't receive the code? Check your spam folder or click "Resend Code" after the timer expires.
    </p>
</div>
