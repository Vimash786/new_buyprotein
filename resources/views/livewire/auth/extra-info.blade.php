<?php

use App\Models\User;
use App\Models\Sellers;
use App\Models\Category;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules;
use Livewire\Attributes\Layout;
use Livewire\Volt\Component;
use Livewire\WithFileUploads;

new #[Layout('components.layouts.auth')] class extends Component {
    use WithFileUploads;
    
    public string $role = '';
    public $attachments = [];
    public string $company_name = '';
    public string $gst_number = '';
    public array $product_category = [];
    public string $contact_person = '';
    public $brand_certificate = null;
    public $document_proof = null;
    public string $social_media_link = '';
    public string $social_link = '';
    public $business_certificate = null;
    public $business_images = [];

    public function mount()
    {
        $this->role = request()->get('role', Auth::user()->role);
    }
    
    public function removeCategory($category)
    {
        $this->product_category = array_values(array_filter($this->product_category, function($cat) use ($category) {
            return $cat !== $category;
        }));
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
            // Base validation rules for all user types
            $rules = [
                'role' => ['required', 'string', 'in:User,Gym Owner/Trainer/Influencer,Shop Owner'],
            ];
            
            // Add conditional validation based on role
            if ($this->role === 'Gym Owner/Trainer/Influencer') {
                $rules['document_proof'] = ['required', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:10240'];
                $rules['social_link'] = ['required', 'string', 'url', 'max:255'];
                $rules['business_images'] = ['required', 'array', 'min:1'];
                $rules['business_images.*'] = ['file', 'image', 'max:10240'];
            } elseif ($this->role === 'Shop Owner') {
                $rules['business_images'] = ['required', 'array', 'min:1'];
                $rules['business_images.*'] = ['file', 'image', 'max:10240'];
            }
            
            $validated = $this->validate($rules);
            
            // Update user with additional info
            $user->update([
                'role' => $validated['role'],
                'profile_completed' => true,
            ]);

            // Save document proof for Gym Owner/Trainer/Influencer
            if (isset($validated['document_proof'])) {
                $documentProofPath = $validated['document_proof']->store('user_documents', 'public');
                $user->update(['document_proof' => $documentProofPath]);
            }

            // Save social media link for Gym Owner/Trainer/Influencer
            if (isset($validated['social_link'])) {
                $user->update(['social_media_link' => $validated['social_link']]);
            }

            // Save business images for both Gym Owner/Trainer/Influencer and Shop Owner
            if (isset($validated['business_images']) && is_array($validated['business_images'])) {
                $businessImagePaths = [];
                foreach ($validated['business_images'] as $image) {
                    $businessImagePaths[] = $image->store('business_images', 'public');
                }
                $user->update(['business_images' => json_encode($businessImagePaths)]);
            }
           
            
        } elseif ($this->role === 'Seller') {
            $validated = $this->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'gst_number' => ['required', 'string', 'max:50'],
                'product_category' => ['required', 'array', 'min:1'],
                'product_category.*' => ['string'],
                'contact_person' => ['required', 'string', 'max:255'],
                'brand_certificate' => ['required', 'file', 'max:10240'], // 10MB max
            ]);
            
            // Create seller record
            Sellers::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'gst_number' => $validated['gst_number'],
                'product_category' => implode(', ', $validated['product_category']), // Convert array to comma-separated string
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
                @if($role === 'Gym Owner/Trainer/Influencer')
                    <flux:input 
                        type="file" 
                        wire:model="document_proof" 
                        label="Document Proof" 
                        accept="image/*,application/pdf"
                        required
                        description="Upload document proof (Business license, certification, etc.)"
                    />
                    <flux:input 
                        type="text" 
                        wire:model="social_link" 
                        label="Social Media link" 
                        placeholder="Enter your social media link"
                        required
                        description="Provide a link to your social media profile or website"
                    />
                @endif
                    <!-- Business images -->
                    <flux:input 
                        type="file" 
                        wire:model="business_images" 
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
            <div class="space-y-3" x-data="{ open: false }">
                <flux:label for="product_category" class="text-sm font-medium text-gray-900 dark:text-gray-100">Product Categories</flux:label>
                
                <!-- Selected Categories Display -->
                @if(!empty($product_category))
                    <div class="flex flex-wrap gap-2 mb-3">
                        @foreach($product_category as $category)
                            <span class="inline-flex items-center gap-x-1.5 py-2 px-3 rounded-full text-sm font-medium bg-gradient-to-r text-accent-content bg-accent-foreground border-0 shadow-sm hover:shadow-md transition-all duration-200">
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                </svg>
                                {{ $category }}
                                <button 
                                    type="button" 
                                    wire:click="removeCategory('{{ $category }}')"
                                    class="ml-1 inline-flex items-center justify-center w-5 h-5 text-accent-content hover:text-gray-200 hover:bg-white/20 rounded-full transition-colors duration-150"
                                >
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                                    </svg>
                                </button>
                            </span>
                        @endforeach
                    </div>
                @endif

                <!-- Custom Multiselect Dropdown -->
                <div class="relative">
                    <button 
                        type="button"
                        @click="open = !open"
                        class="w-full px-4 py-3 text-left bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-sm hover:border-gray-400 dark:hover:border-gray-500 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all duration-200"
                    >
                        <div class="flex items-center justify-between">
                            <span class="block truncate text-gray-700 dark:text-gray-200">
                                @if(empty($product_category))
                                    <span class="text-gray-500 dark:text-gray-400">Choose product categories...</span>
                                @elseif(count($product_category) <= 2)
                                    {{ implode(', ', $product_category) }}
                                @else
                                    {{ $product_category[0] }}, {{ $product_category[1] }} 
                                    <span class="text-blue-600 font-medium">+{{ count($product_category) - 2 }} more</span>
                                @endif
                            </span>
                            <svg class="w-5 h-5 text-gray-400 transition-transform duration-200" :class="{ 'rotate-180': open }" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                            </svg>
                        </div>
                    </button>

                    <!-- Dropdown Options -->
                    <div 
                        x-show="open" 
                        x-transition:enter="transition ease-out duration-100"
                        x-transition:enter-start="transform opacity-0 scale-95"
                        x-transition:enter-end="transform opacity-100 scale-100"
                        x-transition:leave="transition ease-in duration-75"
                        x-transition:leave-start="transform opacity-100 scale-100"
                        x-transition:leave-end="transform opacity-0 scale-95"
                        @click.away="open = false"
                        class="absolute z-10 w-full mt-1 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg shadow-lg max-h-60 overflow-auto"
                    >
                        <div class="py-1">
                            @foreach($categories as $category)
                                <label class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150">
                                    <input 
                                        type="checkbox" 
                                        wire:model.live="product_category" 
                                        value="{{ $category->name }}"
                                        class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                                    >
                                    <div class="ml-3 flex-1">
                                        <div class="flex items-center justify-between">
                                            <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $category->name }}</span>
                                            @if(in_array($category->name, $product_category))
                                                <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                                </svg>
                                            @endif
                                        </div>
                                        @if($category->description)
                                            <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($category->description, 60) }}</p>
                                        @endif
                                    </div>
                                </label>
                            @endforeach
                        </div>
                    </div>
                </div>
                
                <flux:description class="text-sm text-gray-600 dark:text-gray-400">
                    Select one or more categories that best describe your products. You can choose multiple categories to reach a broader audience.
                </flux:description>
            </div>

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
