<?php

use App\Models\User;
use App\Models\Sellers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;

new #[Layout('components.layouts.auth')] class extends Component {
    public string $name = '';
    public string $email = '';
    public string $password = '';
    public string $password_confirmation = '';
    public string $industry = '';
    public $document_proof = null;
    public $business_images = null;

    /**
     * Handle an incoming seller registration request. 
     */
    public function register(): void
    {
        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:' . User::class],
            'password' => ['required', 'string', 'confirmed', Rules\Password::defaults()],
            'industry' => ['required', 'string', 'in:Gym Owner/Trainer/Influencer,Shop Owner'],
            'document_proof' => ['required'],
            'business_images' => ['required'],
        ]);

        $validated['password'] = Hash::make($validated['password']);
        $validated['role'] = 'seller'; // Add seller role

        event(new Registered(($user = User::create($validated))));

        Auth::login($user);

        $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header :title="__('Register as a Seller')" :description="__('Join our marketplace and start selling your products')" />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="register" class="flex flex-col gap-6">
        <!-- Name -->
        @if ($role === 'User')
            <!--slelect role put as check yes and no,if Gym Owner/Trainer/Influencer and Shop Owner selected show document and image input field-->
            <flux:select wire:model="industry" placeholder="Choose industry...">
                <flux:select.option>User</flux:select.option>
                <flux:select.option>Gym Owner/Trainer/Influencer</flux:select.option>
                <flux:select.option>Shop Owner</flux:select.option>
            </flux:select>

            <!-- Document proof -->
            <flux:input 
            type="file" 
            wire:model="attachments" 
            label="Document proof" 
            multiple 
            accept="image/*,application/pdf"
            required
            :description="__('Upload at least one document proof')"

            />
            <!-- Add three image -->
            <flux:input 
            type="file" 
            wire:model="attachments" 
            label="Add three images" 
            accept="image/*"
            required
            multiple 
            :description="__('Upload at least three images')"
            />
        @endif
         
        @if ($role === 'Seller')

            <!-- Company Name -->
            <flux:input
            wire:model="company_name"
            :label="__('Company Name')"
            type="text"
            required
            :placeholder="__('Enter your company name')"
            />

            <!-- GST Number -->
            <flux:input
            wire:model="gst_number"
            :label="__('GST Number')"
            type="text"
            required
            :placeholder="__('Enter your GST number')"
            />

            <!-- Product Category -->
            <flux:select wire:model="product_category" placeholder="Choose product category...">
            <flux:select.option>Health Supplements</flux:select.option>
            <flux:select.option>Fitness Equipment</flux:select.option>
            <flux:select.option>Apparel</flux:select.option>
            <flux:select.option>Other</flux:select.option>
            </flux:select>

            <!-- Contact Person -->
            <flux:input
            wire:model="contact_person"
            :label="__('Contact Person')"
            type="text"
            required
            :placeholder="__('Enter name of contact person')"
            />

            <!-- Brand Certificate -->
            <flux:input 
            type="file" 
            wire:model="brand_certificate" 
            label="Brand Certificate" 
            accept="image/*,application/pdf"
            required
            :description="__('Upload your brand certificate or related document')"
            />
        @endif
        

        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ __('Register as Seller') }}
            </flux:button>
        </div>
    </form>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Already have an account?') }}
        <flux:link :href="route('login')" wire:navigate>{{ __('Log in') }}</flux:link>
    </div>

    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Want to register as a customer instead?') }}
        <flux:link :href="route('register')" wire:navigate>{{ __('Customer Registration') }}</flux:link>
    </div>
</div>
