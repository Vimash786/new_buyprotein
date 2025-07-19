@props([
    'label' => '',
    'wireModel' => '',
    'options' => [],
    'selected' => [],
    'placeholder' => 'Choose options...',
    'description' => '',
    'removeMethod' => '',
    'optionValue' => 'id',
    'optionLabel' => 'name',
    'optionDescription' => 'description',
    'required' => false,
    'showDescription' => true
])

<div class="space-y-3" x-data="{ open: false }">
    @if($label)
        <label class="text-sm font-medium text-gray-900 dark:text-gray-100">
            {{ $label }}
        </label>
    @endif
    
    <!-- Selected Items Display -->
    @if(!empty($selected))
        <div class="flex flex-wrap gap-2 mb-3">
            @foreach($selected as $item)
                <span class="inline-flex items-center gap-x-1.5 py-2 px-3 rounded-full text-sm font-medium bg-gradient-to-r text-accent-content bg-accent-foreground border-0 shadow-sm hover:shadow-md transition-all duration-200">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                    </svg>
                    {{ $item }}
                    @if($removeMethod)
                        <button 
                            type="button" 
                            wire:click="{{ $removeMethod }}('{{ $item }}')"
                            class="ml-1 inline-flex items-center justify-center w-5 h-5 text-accent-content hover:text-gray-200 hover:bg-white/20 rounded-full transition-colors duration-150"
                        >
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    @endif
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
                    @if(empty($selected))
                        <span class="text-gray-500 dark:text-gray-400">{{ $placeholder }}</span>
                    @elseif(count($selected) <= 2)
                        {{ implode(', ', $selected) }}
                    @else
                        {{ $selected[0] }}, {{ $selected[1] }} 
                        <span class="text-blue-600 font-medium">+{{ count($selected) - 2 }} more</span>
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
                @foreach($options as $option)
                    @php
                        $value = is_object($option) ? $option->{$optionValue} : (is_array($option) ? $option[$optionValue] : $option);
                        $labelText = is_object($option) ? $option->{$optionLabel} : (is_array($option) ? $option[$optionLabel] : $option);
                        $descriptionText = null;
                        if ($showDescription) {
                            if (is_object($option) && isset($option->{$optionDescription})) {
                                $descriptionText = $option->{$optionDescription};
                            } elseif (is_array($option) && isset($option[$optionDescription])) {
                                $descriptionText = $option[$optionDescription];
                            }
                        }
                    @endphp
                    <label class="flex items-center px-4 py-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer transition-colors duration-150">
                        <input 
                            type="checkbox" 
                            wire:model.live="{{ $wireModel }}" 
                            value="{{ $labelText }}"
                            class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-gray-800 focus:ring-2 dark:bg-gray-700 dark:border-gray-600"
                        >
                        <div class="ml-3 flex-1">
                            <div class="flex items-center justify-between">
                                <span class="text-sm font-medium text-gray-900 dark:text-gray-100">{{ $labelText }}</span>
                                @if(in_array($labelText, $selected))
                                    <svg class="w-4 h-4 text-blue-600" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"></path>
                                    </svg>
                                @endif
                            </div>
                            @if($descriptionText && $showDescription)
                                <p class="text-xs text-gray-500 dark:text-gray-400 mt-1">{{ Str::limit($descriptionText, 60) }}</p>
                            @endif
                        </div>
                    </label>
                @endforeach
            </div>
        </div>
    </div>
    
    @if($description)
        <p class="text-sm text-gray-600 dark:text-gray-400">
            {{ $description }}
        </p>
    @endif
    
    @error($wireModel) 
        <span class="text-red-500 text-sm">{{ $message }}</span> 
    @enderror
</div>
