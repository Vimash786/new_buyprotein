<?php

use App\Models\User;
use App\Models\Sellers;
use App\Models\Category;
use App\Mail\WelcomeMail;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Rule;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithFileUploads;
    
    public string $role = '';
    public $attachments = [];
    public string $company_name = '';
    public string $gst_number = '';
    public array $product_category = [];
    public string $contact_no = '';
    public string $brand = '';
    public $brand_logo = null;
    public $brand_certificate = null;
    public $document_proof = null;
    public string $social_link = '';
    public $business_images = [];
    public string $contact_message = '';

    // Track which fields have been touched for live validation
    public bool $touched_company_name = false;
    public bool $touched_gst_number = false;
    public bool $touched_contact_no = false;
    public bool $touched_brand = false;
    public bool $touched_social_link = false;

    public function mount()
    {
        $this->role = request()->get('role', Auth::user()->role);
    }
    
    public function removeCategory($category)
    {
        $this->product_category = array_values(array_filter($this->product_category, function($cat) use ($category) {
            return $cat != $category;
        }));
    }

    // ─── Live field validators (trigger on blur-equivalent: wire:model.blur) ──

    public function updatedCompanyName($value)
    {
        $this->touched_company_name = true;
        $this->validateOnly('company_name', [
            'company_name' => ['required', 'string', 'min:2', 'max:255'],
        ]);
    }

    public function updatedGstNumber($value)
    {
        $this->touched_gst_number = true;
        $this->validateOnly('gst_number', [
            'gst_number' => ['required', 'string', 'max:50'],
        ]);
    }

    public function updatedBrand($value)
    {
        $this->touched_brand = true;
        $this->validateOnly('brand', [
            'brand' => ['required', 'string', 'min:2', 'max:255'],
        ]);
    }

    public function updatedSocialLink($value)
    {
        $this->touched_social_link = true;
        $this->validateOnly('social_link', [
            'social_link' => ['required', 'string', 'url', 'max:255'],
        ]);
    }
    
    public function updatedContactNo($value)
    {
        $this->touched_contact_no = true;
        $this->contact_message = '';
        
        // Remove any existing +91 prefix to avoid duplication
        $value = preg_replace('/^\+91/', '', $value);
        
        // Remove any non-numeric characters
        $cleanValue = preg_replace('/[^0-9]/', '', $value);
        
        if ($value !== $cleanValue && !empty($value)) {
            $this->contact_message = 'warning:Only numbers are allowed. Non-numeric characters have been removed.';
        }
        
        $value = $cleanValue;
        
        if (strlen($value) > 10) {
            $this->contact_message = 'warning:Mobile number can only have 10 digits.';
            $value = substr($value, 0, 10);
        }
        
        if (!empty($value) && is_numeric($value[0])) {
            $this->contact_no = '+91' . $value;
            
            if (strlen($value) < 10) {
                $remaining = 10 - strlen($value);
                $this->contact_message = "info:Enter {$remaining} more digit" . ($remaining > 1 ? 's' : '') . " to complete your mobile number.";
            } elseif (strlen($value) === 10) {
                if (in_array($value[0], ['6', '7', '8', '9'])) {
                    $this->contact_message = 'success:✓ Valid mobile number!';
                    $this->validateOnly('contact_no', [
                        'contact_no' => ['required', 'string', 'regex:/^\+91[6-9]\d{9}$/'],
                    ]);
                } else {
                    $this->contact_message = 'error:Mobile number must start with 6, 7, 8, or 9.';
                }
            }
        } else {
            $this->contact_no = $value;
            if (!empty($value)) {
                $this->contact_message = 'error:Mobile number should start with a digit (6–9).';
            }
        }
    }
    
    public function with()
    {
        return [
            'categories' => Category::all(),
        ];
    }

    /**
     * Handle completion of user/seller registration process
     */
    public function completeRegistration(): void
    {
        $user = Auth::user();
        
        if ($this->role !== 'Seller') {
            $rules = [
                'role' => ['required', 'string', 'in:User,Gym Owner/Trainer/Influencer/Dietitian,Shop Owner'],
            ];
            
            if ($this->role === 'Gym Owner/Trainer/Influencer/Dietitian') {
                $rules['document_proof'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
                $rules['social_link'] = ['required', 'string', 'url', 'max:255'];
                $rules['business_images'] = ['required', 'array', 'min:1'];
                $rules['business_images.*'] = ['file', 'image', 'max:10240'];
            } elseif ($this->role === 'Shop Owner') {
                $rules['business_images'] = ['required', 'array', 'min:1'];
                $rules['business_images.*'] = ['file', 'image', 'max:10240'];
            }
            
            $validated = $this->validate($rules);
            
            $user->update([
                'role' => $validated['role'],
                'profile_completed' => true,
            ]);

            if (isset($validated['document_proof'])) {
                $documentProofPath = $validated['document_proof']->store('user_documents', 'public');
                $user->update(['document_proof' => $documentProofPath]);
            }

            if (isset($validated['social_link'])) {
                $user->update(['social_media_link' => $validated['social_link']]);
            }

            if (isset($validated['business_images']) && is_array($validated['business_images'])) {
                $businessImagePaths = [];
                foreach ($validated['business_images'] as $image) {
                    $businessImagePaths[] = $image->store('business_images', 'public');
                }
                $user->update(['business_images' => json_encode($businessImagePaths)]);
            }

            if ($this->role === 'Gym Owner/Trainer/Influencer/Dietitian') {
                $user->update(['approval_status' => 'pending']);
                try {
                    Mail::to($user->email)->send(new WelcomeMail($user));
                } catch (\Exception $e) {
                    Log::error('Failed to send welcome email: ' . $e->getMessage());
                }
                $this->redirect(route('approval.pending', absolute: false), navigate: true);
                return;
            }
            
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
            
        } elseif ($this->role === 'Seller') {
            $validated = $this->validate([
                'company_name'     => ['required', 'string', 'max:255'],
                'gst_number'       => ['required', 'string', 'max:50'],
                'product_category' => ['required', 'array', 'min:1'],
                'product_category.*' => ['numeric'],
                'contact_no'       => ['required', 'string', 'regex:/^\+91[6-9]\d{9}$/'],
                'brand'            => ['required', 'string', 'max:255'],
                'brand_logo'       => ['nullable', 'file', 'image', 'max:10240'],
                'brand_certificate'=> ['required', 'file', 'max:10240'],
            ]);
            
            $categoryNames = [];
            $categories = Category::whereIn('id', $validated['product_category'])->get();
            foreach ($categories as $category) {
                $categoryNames[] = $category->name;
            }
            
            Sellers::create([
                'user_id'          => $user->id,
                'company_name'     => $validated['company_name'],
                'gst_number'       => $validated['gst_number'],
                'product_category' => implode(', ', $categoryNames),
                'contact_no'       => $validated['contact_no'],
                'brand'            => $validated['brand'],
                'brand_logo'       => isset($validated['brand_logo']) ? $validated['brand_logo']->store('brand_logos', 'public') : null,
                'brand_certificate'=> $validated['brand_certificate']->store('seller_certificates', 'public'),
            ]);
            
            $user->update(['profile_completed' => true]);
            
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
        }

        $user->refresh();
        $completed = $user->profile_completed;

        if ($completed && $user->role == 'Super') {
            session()->forget('url.intended');
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        } elseif ($completed && $user->role == 'Seller') {
            session()->forget('url.intended');
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        } elseif ($completed) {
            $this->redirectIntended(route('user.account', absolute: false));
        }
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-auth-header 
        :title="$role === 'Seller' ? __('Complete Seller Registration') : __('Complete Your Profile')" 
        :description="$role === 'Seller' ? __('Provide your business details to start selling') : __('Tell us a bit about yourself to get started')" 
    />

    <!-- Session Status -->
    <x-auth-session-status class="text-center" :status="session('status')" />

    <form wire:submit="completeRegistration" class="flex flex-col gap-5">

        {{-- ─── NON-SELLER SECTION ─── --}}
        @if ($role !== 'Seller')
            {{-- Role Selector --}}
            <div class="space-y-1">
                <flux:select wire:model.live="role" placeholder="Select your role..." label="I am a...">
                    <flux:select.option value="User">Regular User</flux:select.option>
                    <flux:select.option value="Gym Owner/Trainer/Influencer/Dietitian">Gym Owner / Trainer / Influencer / Dietitian</flux:select.option>
                    <flux:select.option value="Shop Owner">Shop Owner</flux:select.option>
                </flux:select>
                @error('role')
                    <p class="text-xs text-red-500 mt-1 flex items-center gap-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Gym / Shop Owner extra fields --}}
            @if(in_array($role, ['Gym Owner/Trainer/Influencer/Dietitian', 'Shop Owner']))

                @if($role === 'Gym Owner/Trainer/Influencer/Dietitian')
                    {{-- Info banner --}}
                    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl p-4 flex gap-3">
                        <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <p class="text-sm text-amber-800 dark:text-amber-300">
                            Your account will require <strong>admin approval</strong> before you can access all features. Please upload clear documents.
                        </p>
                    </div>

                    {{-- Document Proof --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Document Proof <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="file"
                            wire:model="document_proof"
                            accept="image/*,application/pdf"
                            class="w-full text-sm text-gray-600 dark:text-gray-400
                                   file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                                   file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                                   dark:file:bg-blue-900/40 dark:file:text-blue-300
                                   hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60
                                   border border-gray-300 dark:border-gray-600 rounded-lg p-1
                                   bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400">Business license, certification, or any valid ID (PDF/JPG/PNG, max 10MB)</p>
                        @error('document_proof')
                            <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                        @if($document_proof && !$errors->has('document_proof'))
                            <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                File selected: {{ is_object($document_proof) ? $document_proof->getClientOriginalName() : '' }}
                            </p>
                        @endif
                        <div wire:loading wire:target="document_proof" class="text-xs text-blue-600 flex items-center gap-1">
                            <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                            Uploading...
                        </div>
                    </div>

                    {{-- Social Link --}}
                    <div class="space-y-1">
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                            Social Media Link <span class="text-red-500">*</span>
                        </label>
                        <input
                            type="url"
                            wire:model.blur="social_link"
                            placeholder="https://instagram.com/yourprofile"
                            class="w-full px-3 py-2 rounded-lg border text-sm
                                   bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                                   placeholder-gray-400 dark:placeholder-gray-500
                                   focus:outline-none focus:ring-2 focus:ring-blue-500
                                   {{ $errors->has('social_link') ? 'border-red-400 dark:border-red-500' : ($touched_social_link && !$errors->has('social_link') && $social_link ? 'border-green-400' : 'border-gray-300 dark:border-gray-600') }}"
                        />
                        <p class="text-xs text-gray-500 dark:text-gray-400">Instagram, YouTube, LinkedIn or any public profile URL</p>
                        @error('social_link')
                            <p class="text-xs text-red-500 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                                {{ $message }}
                            </p>
                        @enderror
                        @if($touched_social_link && !$errors->has('social_link') && $social_link)
                            <p class="text-xs text-green-600 flex items-center gap-1">
                                <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                Valid URL ✓
                            </p>
                        @endif
                    </div>
                @endif

                {{-- Business Images --}}
                <div class="space-y-1">
                    <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                        Business Images <span class="text-red-500">*</span>
                    </label>
                    <input
                        type="file"
                        wire:model="business_images"
                        accept="image/*"
                        multiple
                        class="w-full text-sm text-gray-600 dark:text-gray-400
                               file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                               file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                               dark:file:bg-blue-900/40 dark:file:text-blue-300
                               hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60
                               border border-gray-300 dark:border-gray-600 rounded-lg p-1
                               bg-white dark:bg-zinc-800 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    />
                    <p class="text-xs text-gray-500 dark:text-gray-400">Upload at least 1 image of your {{ $role === 'Shop Owner' ? 'shop' : 'gym/workspace' }} (JPG/PNG, max 10MB each)</p>
                    @error('business_images')
                        <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                            {{ $message }}
                        </p>
                    @enderror
                    @error('business_images.*')
                        <p class="text-xs text-red-500 mt-1">{{ $message }}</p>
                    @enderror
                    @if(is_array($business_images) && count($business_images) > 0 && !$errors->has('business_images'))
                        <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            {{ count($business_images) }} image(s) selected
                        </p>
                    @endif
                    <div wire:loading wire:target="business_images" class="text-xs text-blue-600 flex items-center gap-1">
                        <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                        Uploading images...
                    </div>
                </div>
            @endif
        @endif

        {{-- ─── SELLER SECTION ─── --}}
        @if ($role === 'Seller')

            {{-- Company Name --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Company Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model.blur="company_name"
                    placeholder="e.g. Fitness World Pvt. Ltd."
                    class="w-full px-3 py-2 rounded-lg border text-sm
                           bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           {{ $errors->has('company_name') ? 'border-red-400 dark:border-red-500' : ($touched_company_name && !$errors->has('company_name') && $company_name ? 'border-green-400' : 'border-gray-300 dark:border-gray-600') }}"
                />
                @error('company_name')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
                @if($touched_company_name && !$errors->has('company_name') && $company_name)
                    <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Looks good ✓
                    </p>
                @endif
            </div>

            {{-- GST Number --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    GST Number <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model.blur="gst_number"
                    placeholder="e.g. 29ABCDE1234F1Z5"
                    class="w-full px-3 py-2 rounded-lg border text-sm uppercase
                           bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           {{ $errors->has('gst_number') ? 'border-red-400 dark:border-red-500' : ($touched_gst_number && !$errors->has('gst_number') && $gst_number ? 'border-green-400' : 'border-gray-300 dark:border-gray-600') }}"
                />
                @error('gst_number')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
                @if($touched_gst_number && !$errors->has('gst_number') && $gst_number)
                    <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Looks good ✓
                    </p>
                @endif
            </div>

            {{-- Product Category --}}
            <x-multiselect
                label="Product Categories"
                wire-model="product_category"
                :options="$categories"
                :selected="$product_category"
                placeholder="Choose product categories..."
                description="Select one or more categories that best describe your products."
                remove-method="removeCategory"
                option-value="id"
                option-label="name"
                option-description="description"
                required
                :show-description="true"
            />

            {{-- Contact Number --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Contact Number <span class="text-red-500">*</span>
                </label>
                <div class="relative">
                    <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-sm text-gray-500 dark:text-gray-400 font-medium pointer-events-none">+91</span>
                    <input
                        type="tel"
                        wire:model.live="contact_no"
                        placeholder="9876543210"
                        inputmode="numeric"
                        maxlength="13"
                        class="w-full pl-12 pr-3 py-2 rounded-lg border text-sm
                               bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                               placeholder-gray-400 dark:placeholder-gray-500
                               focus:outline-none focus:ring-2 focus:ring-blue-500
                               @if($contact_message)
                                   @if(str_starts_with($contact_message, 'success:')) border-green-400
                                   @elseif(str_starts_with($contact_message, 'error:')) border-red-400
                                   @else border-gray-300 dark:border-gray-600 @endif
                               @else border-gray-300 dark:border-gray-600 @endif"
                    />
                </div>
                @if($contact_message)
                    @php
                        [$msgType, $msgText] = explode(':', $contact_message, 2);
                        $msgClass = match($msgType) {
                            'success' => 'text-green-600',
                            'error'   => 'text-red-500',
                            'warning' => 'text-amber-600',
                            default   => 'text-blue-600',
                        };
                        $msgIcon = match($msgType) {
                            'success' => '<path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>',
                            'error', 'warning' => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>',
                            default => '<path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>',
                        };
                    @endphp
                    <p class="text-xs {{ $msgClass }} flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20">{!! $msgIcon !!}</svg>
                        {{ $msgText }}
                    </p>
                @endif
                @error('contact_no')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
            </div>

            {{-- Brand Name --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Brand Name <span class="text-red-500">*</span>
                </label>
                <input
                    type="text"
                    wire:model.blur="brand"
                    placeholder="e.g. ProNutrition"
                    class="w-full px-3 py-2 rounded-lg border text-sm
                           bg-white dark:bg-zinc-800 text-gray-900 dark:text-white
                           placeholder-gray-400 dark:placeholder-gray-500
                           focus:outline-none focus:ring-2 focus:ring-blue-500
                           {{ $errors->has('brand') ? 'border-red-400 dark:border-red-500' : ($touched_brand && !$errors->has('brand') && $brand ? 'border-green-400' : 'border-gray-300 dark:border-gray-600') }}"
                />
                @error('brand')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
                @if($touched_brand && !$errors->has('brand') && $brand)
                    <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Looks good ✓
                    </p>
                @endif
            </div>

            {{-- Brand Logo --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Brand Logo <span class="text-gray-400 font-normal">(optional)</span></label>
                <input
                    type="file"
                    wire:model="brand_logo"
                    accept="image/*"
                    class="w-full text-sm text-gray-600 dark:text-gray-400
                           file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                           dark:file:bg-blue-900/40 dark:file:text-blue-300
                           hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60
                           border border-gray-300 dark:border-gray-600 rounded-lg p-1
                           bg-white dark:bg-zinc-800 focus:outline-none"
                />
                <p class="text-xs text-gray-500">JPG/PNG, max 10MB</p>
                @error('brand_logo')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
                @if($brand_logo && !$errors->has('brand_logo'))
                    <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Logo selected ✓
                    </p>
                @endif
                <div wire:loading wire:target="brand_logo" class="text-xs text-blue-600 flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Uploading...
                </div>
            </div>

            {{-- Brand Certificate --}}
            <div class="space-y-1">
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">
                    Brand Certificate <span class="text-red-500">*</span>
                </label>
                <input
                    type="file"
                    wire:model="brand_certificate"
                    accept="image/*,application/pdf"
                    class="w-full text-sm text-gray-600 dark:text-gray-400
                           file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0
                           file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                           dark:file:bg-blue-900/40 dark:file:text-blue-300
                           hover:file:bg-blue-100 dark:hover:file:bg-blue-900/60
                           border border-gray-300 dark:border-gray-600 rounded-lg p-1
                           bg-white dark:bg-zinc-800 focus:outline-none"
                />
                <p class="text-xs text-gray-500">PDF/JPG/PNG, max 10MB</p>
                @error('brand_certificate')
                    <p class="text-xs text-red-500 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                        {{ $message }}
                    </p>
                @enderror
                @if($brand_certificate && !$errors->has('brand_certificate'))
                    <p class="text-xs text-green-600 flex items-center gap-1 mt-1">
                        <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                        Certificate selected ✓
                    </p>
                @endif
                <div wire:loading wire:target="brand_certificate" class="text-xs text-blue-600 flex items-center gap-1">
                    <svg class="w-3 h-3 animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                    Uploading...
                </div>
            </div>
        @endif

        {{-- Submit Button --}}
        <div class="pt-1">
            <button
                type="submit"
                class="w-full flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-sm font-semibold
                       bg-blue-600 hover:bg-blue-700 active:bg-blue-800
                       text-white transition-colors duration-150 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
            >
                <span wire:loading.remove wire:target="completeRegistration">
                    {{ $role === 'Seller' ? 'Complete Seller Registration' : 'Complete Profile' }}
                </span>
                <span wire:loading wire:target="completeRegistration" class="flex items-center gap-2">
                    <svg class="w-4 h-4 animate-spin" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    Saving...
                </span>
            </button>
        </div>
    </form>
</div>
