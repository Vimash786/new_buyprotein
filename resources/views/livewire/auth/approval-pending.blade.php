<?php

use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Auth;

new #[Layout('components.layouts.auth')] class extends Component {
    public function mount()
    {
        $user = Auth::user();

        // Only users with pending approval should see this page
        if (!$user || $user->approval_status !== 'pending') {
            $this->redirect(route('home'), navigate: true);
        }
    }
}; ?>

<div class="flex flex-col gap-6 text-center">
    <!-- Icon -->
    <div class="flex justify-center">
        <div class="w-20 h-20 rounded-full bg-yellow-100 dark:bg-yellow-900/40 flex items-center justify-center">
            <svg class="w-10 h-10 text-yellow-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                    d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
            </svg>
        </div>
    </div>

    <!-- Title & Description -->
    <div>
        <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Account Pending Approval</h1>
        <p class="mt-2 text-sm text-gray-600 dark:text-gray-400">
            Thank you for registering as a <strong>{{ Auth::user()->role }}</strong>.<br>
            Your account is currently under review by our admin team.
        </p>
    </div>

    <!-- Info Box -->
    <div class="bg-yellow-50 dark:bg-yellow-900/30 border border-yellow-200 dark:border-yellow-800 rounded-xl p-5 text-left space-y-2">
        <p class="text-sm font-semibold text-yellow-800 dark:text-yellow-300 flex items-center gap-2">
            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd" />
            </svg>
            What happens next?
        </p>
        <ul class="text-sm text-yellow-700 dark:text-yellow-400 space-y-1 list-disc list-inside">
            <li>Our team will review your submitted documents.</li>
            <li>You'll receive an email once your account is approved.</li>
            <li>Approval typically takes 24–48 hours.</li>
        </ul>
    </div>

    <!-- Account Info -->
    <div class="bg-gray-50 dark:bg-zinc-800 rounded-xl p-4 text-left text-sm text-gray-600 dark:text-gray-300">
        <p><span class="font-medium">Name:</span> {{ Auth::user()->name }}</p>
        <p><span class="font-medium">Email:</span> {{ Auth::user()->email }}</p>
        <p><span class="font-medium">Role:</span> {{ Auth::user()->role }}</p>
        <p><span class="font-medium">Status:</span>
            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300 ml-1">
                Pending Review
            </span>
        </p>
    </div>

    <!-- Logout link -->
    <div class="text-sm text-gray-500 dark:text-gray-400">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-blue-600 dark:text-blue-400 hover:underline">
                Sign out and come back later
            </button>
        </form>
    </div>
</div>
