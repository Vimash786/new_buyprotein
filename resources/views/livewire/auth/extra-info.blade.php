<?php

use App\Models\User;
use App\Models\Sellers;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithFileUploads;
    
    public string $role = '';
    public $attachments = [];    public string $company_name = '';
    public string $gst_number = '';
    public string $product_category = '';
    public string $contact_person = '';
    public $brand_certificate = null;

    public function mount()
    {
        $this->role = request()->get('role', 'User');
    }

    /**
     * Handle completion of user/seller registration process
     */
    public function completeRegistration(): void
    {
        $user = Auth::user();
        
        
        if ($this->role === 'User') {
            
            $validated = $this->validate([
                'role' => ['required', 'string', 'in:User,Gym Owner/Trainer/Influencer,Shop Owner'],
                //'attachments' => ['required_unless:industry,User', 'array'],
                //'attachments.*' => ['file', 'max:10240'], // 10MB max per file
            ]);
            
            // Update user with additional info
            $user->update([
                'role' => $validated['role'],
                'profile_completed' => true,
            ]);
            
            // Handle file uploads if industry is not just 'User'
            //if ($validated['role'] === 'User' || !empty($validated['attachments'])) {
                // Store files logic here
                // You might want to create a separate table for user documents
            //}
           
            
        } elseif ($this->role === 'Seller') {
            $validated = $this->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'gst_number' => ['required', 'string', 'max:50'],
                'product_category' => ['required', 'string', 'in:Health Supplements,Fitness Equipment,Apparel,Other'],
                'contact_person' => ['required', 'string', 'max:255'],
                'brand_certificate' => ['required', 'file', 'max:10240'], // 10MB max
            ]);
            
            // Create seller record
            Sellers::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'gst_number' => $validated['gst_number'],
                'product_category' => $validated['product_category'],
                'contact_person' => $validated['contact_person'],
                // Store brand certificate path
                'brand_certificate' => $validated['brand_certificate']->store('seller_certificates', 'public'),
            ]);
            
            // Update user profile completion status
            $user->update(['profile_completed' => true]);
        }

        $completed = $user->profile_completed;
        if( $completed){
            $this->redirectIntended(route('dashboard', absolute: false), navigate: true);
        }
       
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="$role === 'Seller' ? __('Complete Seller Registration') : __('Complete Your Profile')" 
        :description="$role === 'Seller' ? __('Provide additional business information to start selling') : __('Tell us more about yourself')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="completeRegistration" class="flex flex-col gap-6">        <!-- User Additional Info -->
        @if ($role !== 'Seller')
            <!-- Industry Selection -->
            <flux:select wire:model.live="role" placeholder="Choose your category..." label="I am a...">
            <flux:select.option value="User">Regular User</flux:select.option>
            <flux:select.option value="Gym Owner/Trainer/Influencer">Gym Owner/Trainer/Influencer</flux:select.option>
            <flux:select.option value="Shop Owner">Shop Owner</flux:select.option>
            </flux:select>
            
            <!-- Document proof and Business images - only show for Gym Owner/Trainer/Influencer and Shop Owner -->
            @if(in_array($role, ['Gym Owner/Trainer/Influencer', 'Shop Owner']))
                <!-- Document proof -->
                <flux:input 
                    type="file" 
                    wire:model="attachments" 
                    label="Document Proof" 
                    multiple 
                    accept="image/*,application/pdf"
                    required
                    description="Upload at least one document proof (Business license, certification, etc.)"
                />
                
                <!-- Business images -->
                <flux:input 
                    type="file" 
                    wire:model="attachments" 
                    label="Business Images" 
                    accept="image/*"
                    required
                    multiple 
                    description="Upload at least three images of your business/gym"
                />
            @endif
        @endif
        <!-- Seller Additional Info -->
        @if ($role === 'Seller')
            <!-- Company Name -->
            <flux:input
                wire:model="company_name"
                label="Company Name"
                type="text"
                required
                placeholder="Enter your company name"
            />

            <!-- GST Number -->
            <flux:input
                wire:model="gst_number"
                label="GST Number"
                type="text"
                required
                placeholder="Enter your GST number"
            />

            <!-- Product Category -->
            <flux:select wire:model="product_category" placeholder="Choose product category..." label="Product Category">
                <flux:select.option value="Health Supplements">Health Supplements</flux:select.option>
                <flux:select.option value="Fitness Equipment">Fitness Equipment</flux:select.option>
                <flux:select.option value="Apparel">Apparel</flux:select.option>
                <flux:select.option value="Other">Other</flux:select.option>
            </flux:select>

            <!-- Contact Person -->
            <flux:input
                wire:model="contact_person"
                label="Contact Person"
                type="text"
                required
                placeholder="Enter name of contact person"
            />

            <!-- Brand Certificate -->
            <flux:input 
                type="file" 
                wire:model="brand_certificate" 
                label="Brand Certificate" 
                accept="image/*,application/pdf"
                required
                description="Upload your brand certificate or related business document"
            />
        @endif        
        <div class="flex items-center justify-end">
            <flux:button type="submit" variant="primary" class="w-full">
                {{ $role === 'Seller' ? __('Complete Seller Registration') : __('Complete Profile') }}
            </flux:button>
        </div>
    </form>

    @if ($role !== 'Seller')
    <div class="space-x-1 rtl:space-x-reverse text-center text-sm text-zinc-600 dark:text-zinc-400">
        {{ __('Want to register as a seller instead?') }}
        <flux:link href="{{ route('seller.register') }}" wire:navigate>{{ __('Seller Registration') }}</flux:link>
    </div>
    @endif
</div>
