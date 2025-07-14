<?php

use App\Models\User;
use App\Models\Sellers;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public string $name = '';
    public string $email = '';
    
    // Seller profile fields
    public string $company_name = '';
    public string $gst_number = '';
    public array $product_category = [];
    public string $contact_person = '';
    public string $brand = '';
    public $brand_logo_file = null;
    public $brand_certificate_file = null;
    public string $existing_brand_logo = '';
    public string $existing_brand_certificate = '';
    
    public $seller = null;
    public $categories = [];
    public $selectedCategory = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $user = Auth::user();
        $this->name = $user->name;
        $this->email = $user->email;
        
        // Load seller data if user is a seller
        if ($user->role === 'Seller') {
            $this->seller = Sellers::where('user_id', $user->id)->first();
            if ($this->seller) {
                $this->company_name = $this->seller->company_name ?? '';
                $this->gst_number = $this->seller->gst_number ?? '';
                //$this->product_category = $this->seller->product_category;
                $this->contact_person = $this->seller->contact_person ?? '';
                $this->brand = $this->seller->brand ?? '';
                $this->existing_brand_logo = $this->seller->brand_logo ?? '';
                $this->existing_brand_certificate = $this->seller->brand_certificate ?? '';
            }
        }
        
        // Load categories for seller
        $this->categories = Category::active()->ordered()->get();
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($user->id)
            ],
        ];

        // Add seller validation rules if user is a seller
        if ($user->role === 'Seller') {
            $rules = array_merge($rules, [
                'company_name' => ['required', 'string', 'max:255'],
                'gst_number' => ['required', 'string', 'max:255', Rule::unique('sellers', 'gst_number')->ignore($this->seller?->id)],
                'product_category' => ['required', 'array', 'min:1'],
                'contact_person' => ['required', 'string', 'max:255'],
                'brand' => ['required', 'string', 'max:255'],
                'brand_logo_file' => ['nullable', 'file', 'image', 'max:10240'],
                'brand_certificate_file' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png,gif', 'max:10240'],
            ]);
        }

        $validated = $this->validate($rules);

        // Update user basic info
        $updateData = [
            'name' => $validated['name'],
            'email' => $validated['email']
        ];

        if ($user->email !== $validated['email']) {
            $updateData['email_verified_at'] = null;
        }

        $user->update($updateData);

        // Update seller information if user is a seller
        if ($user->role === 'Seller') {
            $sellerData = [
                'company_name' => $validated['company_name'],
                'gst_number' => $validated['gst_number'],
                'product_category' => $validated['product_category'],
                'contact_person' => $validated['contact_person'],
                'brand' => $validated['brand'],
            ];

            // Handle brand logo upload
            if ($this->brand_logo_file) {
                // Delete old brand logo if it exists
                if ($this->existing_brand_logo) {
                    Storage::disk('public')->delete($this->existing_brand_logo);
                }
                
                $fileName = time() . '_brand_logo_' . $this->brand_logo_file->getClientOriginalName();
                $filePath = $this->brand_logo_file->storeAs('brand_logos', $fileName, 'public');
                $sellerData['brand_logo'] = $filePath;
                $this->existing_brand_logo = $filePath;
            }

            // Handle brand certificate upload
            if ($this->brand_certificate_file) {
                // Delete old brand certificate if it exists
                if ($this->existing_brand_certificate) {
                    Storage::disk('public')->delete($this->existing_brand_certificate);
                }
                
                $fileName = time() . '_brand_certificate_' . $this->brand_certificate_file->getClientOriginalName();
                $filePath = $this->brand_certificate_file->storeAs('brand_certificates', $fileName, 'public');
                $sellerData['brand_certificate'] = $filePath;
                $this->existing_brand_certificate = $filePath;
            }

            // Update or create seller record
            if ($this->seller) {
                $this->seller->update($sellerData);
            } else {
                $sellerData['user_id'] = $user->id;
                $this->seller = Sellers::create($sellerData);
            }

            // Reset file inputs
            $this->brand_logo_file = null;
            $this->brand_certificate_file = null;
        }

        $this->dispatch('profile-updated', name: $user->name);
    }

    public function removeCategory($category)
    {
        $this->product_category = array_values(array_filter($this->product_category, function($cat) use ($category) {
            return $cat !== $category;
        }));
    }

    public function addCategory()
    {
        if ($this->selectedCategory && !in_array($this->selectedCategory, $this->product_category)) {
            $this->product_category[] = $this->selectedCategory;
            $this->selectedCategory = ''; // Reset selection
        }
    }

    public function updatedSelectedCategory($value)
    {
        if ($value) {
            $this->addCategory();
        }
    }

    /**
     * Send an email verification notification to the current user.
     */
    public function resendVerificationNotification(): void
    {
        $user = Auth::user();

        if ($user->hasVerifiedEmail()) {
            $this->redirectIntended(default: route('dashboard', absolute: false));
            return;
        }

        $user->sendEmailVerificationNotification();
        Session::flash('status', 'verification-link-sent');
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <x-settings.layout :heading="__('Profile')" :subheading="auth()->user()->role === 'Seller' ? __('Update your profile and business information') : __('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <!-- Basic Profile Information -->
            <div class="space-y-6">
                <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Basic Information') }}</h3>
                
                <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

                <div>
                    <flux:input wire:model="email" :label="__('Email')" type="email" required autocomplete="email" />

                    @if (auth()->user() instanceof \Illuminate\Contracts\Auth\MustVerifyEmail &&! auth()->user()->hasVerifiedEmail())
                        <div>
                            <flux:text class="mt-4">
                                {{ __('Your email address is unverified.') }}

                                <flux:link class="text-sm cursor-pointer" wire:click.prevent="resendVerificationNotification">
                                    {{ __('Click here to re-send the verification email.') }}
                                </flux:link>
                            </flux:text>

                            @if (session('status') === 'verification-link-sent')
                                <flux:text class="mt-2 font-medium !dark:text-green-400 !text-green-600">
                                    {{ __('A new verification link has been sent to your email address.') }}
                                </flux:text>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Seller Business Information -->
            @if (auth()->user()->role === 'Seller')
                <div class="space-y-6 pt-6 border-t border-gray-200 dark:border-gray-700">
                    <h3 class="text-lg font-medium text-gray-900 dark:text-white">{{ __('Business Information') }}</h3>
                    
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Company Name -->
                        <flux:input 
                            wire:model="company_name" 
                            :label="__('Company Name')" 
                            type="text" 
                            required 
                            placeholder="Enter your company name"
                        />

                        <!-- GST Number -->
                        <flux:input 
                            wire:model="gst_number" 
                            :label="__('GST Number')" 
                            type="text" 
                            required 
                            placeholder="Enter your GST number"
                        />
                    </div>

                    <!-- Product Categories -->
                    <div>
                        <label class="text-sm font-medium text-gray-900 dark:text-gray-100">
                            {{ __('Product Categories') }}
                            <span class="text-red-500">*</span>
                        </label>
                        
                        <!-- Selected Categories Display -->
                        @if(!empty($product_category))
                            <div class="flex flex-wrap gap-2 mt-2 mb-3">
                                @foreach($product_category as $category)
                                    <span class="inline-flex items-center gap-x-1.5 py-2 px-3 rounded-full text-sm font-medium bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
                                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                            <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                        </svg>
                                        {{ $category }}
                                        <button 
                                            type="button" 
                                            wire:click="removeCategory('{{ $category }}')"
                                            class="ml-1 inline-flex items-center justify-center w-5 h-5 text-blue-800 hover:text-blue-600 hover:bg-blue-200 rounded-full transition-colors duration-150"
                                        >
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                            </svg>
                                        </button>
                                    </span>
                                @endforeach
                            </div>
                        @endif

                        <!-- Category Selection -->
                        <div class="mt-2">
                            <select 
                                wire:model="selectedCategory"
                                class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                            >
                                <option value="">Choose product categories...</option>
                                @foreach($categories as $category)
                                    @if(!in_array($category->name, $product_category))
                                        <option value="{{ $category->name }}">{{ $category->name }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            Select categories that best describe your products. You can choose multiple categories to reach a broader audience.
                        </p>
                        
                        @error('product_category') 
                            <span class="mt-2 text-sm text-red-600">{{ $errors->first('product_category') }}</span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Contact Person -->
                        <flux:input 
                            wire:model="contact_person" 
                            :label="__('Contact Person')" 
                            type="text" 
                            required 
                            placeholder="Enter contact person name"
                        />

                        <!-- Brand Name -->
                        <flux:input 
                            wire:model="brand" 
                            :label="__('Brand Name')" 
                            type="text" 
                            required 
                            placeholder="Enter your brand name"
                        />
                    </div>

                    <!-- File Uploads -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Brand Logo -->
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Brand Logo') }}</flux:label>
                                <flux:description>{{ __('Upload your brand logo (optional)') }}</flux:description>
                                
                                @if($existing_brand_logo)
                                    <div class="mb-3 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <img src="{{ Storage::url($existing_brand_logo) }}" alt="Current Brand Logo" class="w-12 h-12 rounded object-cover border border-gray-300">
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Current Logo</p>
                                                    <p class="text-xs text-gray-500">Click to view full size</p>
                                                </div>
                                            </div>
                                            <a href="{{ Storage::url($existing_brand_logo) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                                        </div>
                                    </div>
                                @endif
                                
                                <input 
                                    type="file" 
                                    wire:model="brand_logo_file"
                                    accept="image/*"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                <p class="text-xs text-gray-500 mt-1">Upload JPG, JPEG, PNG, or GIF files (max 10MB)</p>
                                
                                @if($brand_logo_file)
                                    <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                                        File selected: {{ $brand_logo_file->getClientOriginalName() }}
                                    </div>
                                @endif
                                
                                @error('brand_logo_file') 
                                    <span class="mt-2 text-sm text-red-600">{{ $errors->first('brand_logo_file') }}</span>
                                @enderror
                            </flux:field>
                        </div>

                        <!-- Brand Certificate -->
                        <div>
                            <flux:field>
                                <flux:label>{{ __('Brand Certificate') }}</flux:label>
                                <flux:description>{{ __('Upload your brand certificate or business document') }}</flux:description>
                                
                                @if($existing_brand_certificate)
                                    <div class="mb-3 p-3 bg-gray-50 dark:bg-zinc-800 rounded-lg">
                                        <div class="flex items-center justify-between">
                                            <div class="flex items-center gap-3">
                                                <svg class="w-8 h-8 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                                                </svg>
                                                <div>
                                                    <p class="text-sm font-medium text-gray-900 dark:text-white">Current Certificate</p>
                                                    <p class="text-xs text-gray-500">Business certificate document</p>
                                                </div>
                                            </div>
                                            <a href="{{ Storage::url($existing_brand_certificate) }}" target="_blank" class="text-blue-600 hover:text-blue-800 text-sm">View</a>
                                        </div>
                                    </div>
                                @endif
                                
                                <input 
                                    type="file" 
                                    wire:model="brand_certificate_file"
                                    accept=".pdf,.jpg,.jpeg,.png,.gif"
                                    class="w-full px-3 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent file:mr-4 file:py-1 file:px-3 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white"
                                >
                                <p class="text-xs text-gray-500 mt-1">Upload PDF, JPG, JPEG, PNG, or GIF files (max 10MB)</p>
                                
                                @if($brand_certificate_file)
                                    <div class="mt-2 p-2 bg-green-50 border border-green-200 rounded text-sm text-green-700">
                                        File selected: {{ $brand_certificate_file->getClientOriginalName() }}
                                    </div>
                                @endif
                                
                                @error('brand_certificate_file') 
                                    <span class="mt-2 text-sm text-red-600">{{ $errors->first('brand_certificate_file') }}</span>
                                @enderror
                            </flux:field>
                        </div>
                    </div>
                </div>
            @endif

            <div class="flex items-center gap-4">
                <div class="flex items-center justify-end">
                    <flux:button variant="primary" type="submit" class="w-full">{{ __('Save') }}</flux:button>
                </div>

                <x-action-message class="me-3" on="profile-updated">
                    {{ __('Saved.') }}
                </x-action-message>
            </div>
        </form>

        <livewire:settings.delete-user-form />
    </x-settings.layout>
</section>
