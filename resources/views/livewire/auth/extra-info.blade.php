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
    public string $social_media_link = '';
    public string $social_link = '';
    public $business_certificate = null;
    public $business_images = [];
    public string $contact_message = '';

    public function mount()
    {
        $this->role = request()->get('role', Auth::user()->role);
    }
    
    public function removeCategory($category)
    {
        $this->product_category = array_values(array_filter($this->product_category, function($cat) use ($category) {
            // Use loose comparison to handle string/int type differences
            return $cat != $category;
        }));
    }
    
    public function updatedContactNo($value)
    {
        // Reset message
        $this->contact_message = '';
        
        // Remove any existing +91 prefix to avoid duplication
        $value = preg_replace('/^\+91/', '', $value);
        
        // Remove any non-numeric characters
        $cleanValue = preg_replace('/[^0-9]/', '', $value);
        
        // Check if non-numeric characters were removed
        if ($value !== $cleanValue && !empty($value)) {
            $this->contact_message = 'Only numbers are allowed. Letters and special characters have been removed.';
        }
        
        $value = $cleanValue;
        
        // Check if trying to enter more than 10 digits
        if (strlen($value) > 10) {
            $this->contact_message = 'Mobile number can only have 10 digits. Extra digits have been removed.';
            $value = substr($value, 0, 10);
        }
        
        // If the user starts typing a number and it doesn't already have +91, add it
        if (!empty($value) && is_numeric($value[0])) {
            $this->contact_no = '+91' . $value;
            
            // Show helpful messages based on length
            if (strlen($value) < 10) {
                $remaining = 10 - strlen($value);
                $this->contact_message = "Enter {$remaining} more digit" . ($remaining > 1 ? 's' : '') . " to complete your mobile number.";
            } elseif (strlen($value) === 10) {
                // Check if it starts with valid digits (6-9)
                if (in_array($value[0], ['6', '7', '8', '9'])) {
                    $this->contact_message = '✓ Valid mobile number format!';
                } else {
                    $this->contact_message = 'Mobile number should start with 6, 7, 8, or 9.';
                }
            }
        } else {
            $this->contact_no = $value;
            if (!empty($value)) {
                $this->contact_message = 'Mobile number should start with a digit.';
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
            // Base validation rules for all user types
            $rules = [
                'role' => ['required', 'string', 'in:User,Gym Owner/Trainer/Influencer/Dietitian,Shop Owner'],
            ];
            
            // Add conditional validation based on role
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
            
            // Update user with additional info
            $user->update([
                'role' => $validated['role'],
                'profile_completed' => true,
            ]);

            // Save document proof for Gym Owner/Trainer/Influencer/Dietitian
            if (isset($validated['document_proof'])) {
                $documentProofPath = $validated['document_proof']->store('user_documents', 'public');
                $user->update(['document_proof' => $documentProofPath]);
            }

            // Save social media link for Gym Owner/Trainer/Influencer/Dietitian
            if (isset($validated['social_link'])) {
                $user->update(['social_media_link' => $validated['social_link']]);
            }

            // Save business images for both Gym Owner/Trainer/Influencer/Dietitian and Shop Owner
            if (isset($validated['business_images']) && is_array($validated['business_images'])) {
                $businessImagePaths = [];
                foreach ($validated['business_images'] as $image) {
                    $businessImagePaths[] = $image->store('business_images', 'public');
                }
                $user->update(['business_images' => json_encode($businessImagePaths)]);
            }
            
            // Send welcome email for non-seller users
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                // Log the error but don't fail the registration
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
           
            
        } elseif ($this->role === 'Seller') {
            $validated = $this->validate([
                'company_name' => ['required', 'string', 'max:255'],
                'gst_number' => ['required', 'string', 'max:50'],
                'product_category' => ['required', 'array', 'min:1'],
                'product_category.*' => ['numeric'],
                'contact_no' => ['required', 'string', 'regex:/^\+91[6-9]\d{9}$/'],
                'brand' => ['required', 'string', 'max:255'],
                'brand_logo' => ['nullable', 'file', 'image', 'max:10240'],
                'brand_certificate' => ['required', 'file', 'max:10240'], // 10MB max
            ]);
            
            // Get category names from IDs
            $categoryNames = [];
            $categories = Category::whereIn('id', $validated['product_category'])->get();
            foreach($categories as $category) {
                $categoryNames[] = $category->name;
            }
            
            // Create seller record
            Sellers::create([
                'user_id' => $user->id,
                'company_name' => $validated['company_name'],
                'gst_number' => $validated['gst_number'],
                'product_category' => implode(', ', $categoryNames), // Convert category names to comma-separated string
                'contact_no' => $validated['contact_no'],
                'brand' => $validated['brand'],
                'brand_logo' => isset($validated['brand_logo']) ? $validated['brand_logo']->store('brand_logos', 'public') : null,
                // Store brand certificate path
                'brand_certificate' => $validated['brand_certificate']->store('seller_certificates', 'public'),
            ]);
            
            // Update user profile completion status
            $user->update(['profile_completed' => true]);
            
            // Send welcome email for sellers
            try {
                Mail::to($user->email)->send(new WelcomeMail($user));
            } catch (\Exception $e) {
                // Log the error but don't fail the registration
                Log::error('Failed to send welcome email: ' . $e->getMessage());
            }
        }

        $completed = $user->profile_completed;

        if($completed && $user->role == 'Super') {
            // Clear any intended URL and redirect directly to dashboard
            session()->forget('url.intended');
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        }elseif($completed && $user->role == 'Seller'){
            // Clear any intended URL and redirect directly to dashboard
            session()->forget('url.intended');
            $this->redirect(route('dashboard', absolute: false), navigate: true);
        }elseif($completed){
            $this->redirectIntended(route('user.account', absolute: false));
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
            <flux:select.option value="Gym Owner/Trainer/Influencer/Dietitian/">Gym Owner/Trainer/Influencer/Dietitian</flux:select.option>
            <flux:select.option value="Shop Owner">Shop Owner</flux:select.option>
            </flux:select>
            
            <!-- Document proof and Business images - only show for Gym Owner/Trainer/Influencer/Dietitian and Shop Owner -->
            @if(in_array($role, ['Gym Owner/Trainer/Influencer/Dietitian', 'Shop Owner']))
                <!-- Document proof -->
                @if($role === 'Gym Owner/Trainer/Influencer/Dietitian')
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
                placeholder="Enter your company name"
            />

            <!-- GST Number -->
            <flux:input
                wire:model="gst_number"
                label="GST Number"
                type="text"
                placeholder="Enter your GST number"
            />
            
            <!-- Product Category -->
            <x-multiselect
                label="Product Categories"
                wire-model="product_category"
                :options="$categories"
                :selected="$product_category"
                placeholder="Choose product categories..."
                description="Select one or more categories that best describe your products. You can choose multiple categories to reach a broader audience."
                remove-method="removeCategory"
                option-value="id"
                option-label="name"
                option-description="description"
                required
                :show-description="true"
            />

            <!-- Contact No -->
            <div>
                <flux:input
                    wire:model.live="contact_no"
                    label="Contact No"
                    type="text"
                    placeholder="6789012345"
                    description="Enter your 10-digit mobile number (country code +91 will be added automatically)"
                />
                @if($contact_message)
                    <div class="mt-1 text-sm {{ str_contains($contact_message, '✓') ? 'text-green-600' : (str_contains($contact_message, 'removed') || str_contains($contact_message, 'should start') ? 'text-red-500' : 'text-blue-600') }}">
                        {{ $contact_message }}
                    </div>
                @endif
            </div>

            <!-- Brand Name -->
            <flux:input
                wire:model="brand"
                label="Brand Name"
                type="text"
                placeholder="Enter your brand name"
            />

            <!-- Brand Logo -->
            <flux:input 
                type="file" 
                wire:model="brand_logo" 
                label="Brand Logo" 
                accept="image/*"
                description="Upload your brand logo (optional)"
            />

            <!-- Brand Certificate -->
            <flux:input 
                type="file" 
                wire:model="brand_certificate" 
                label="Brand Certificate" 
                accept="image/*,application/pdf"
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
