<!-- Assign Modal -->
@if($showAssignModal)
<div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        <div class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity" wire:click="closeAssignModal" aria-hidden="true"></div>
        
        <!-- This element is to trick the browser into centering the modal contents. -->
        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
        
        <div class="relative inline-block align-bottom bg-white dark:bg-gray-800 rounded-lg px-4 pt-5 pb-4 text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-6xl sm:w-full sm:p-6">
            <div class="flex items-center justify-between pb-4 mb-4 border-b border-gray-200 dark:border-gray-600">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white" id="modal-title">
                    Assign Reference: <span class="text-blue-600 dark:text-blue-400">{{ $selectedReference ? $selectedReference->code : '' }}</span>
                </h3>
                <button wire:click="closeAssignModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300 transition-colors">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                    </svg>
                </button>
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                <!-- Assignment Form -->
                <div class="space-y-6">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-3">
                            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path>
                            </svg>
                            Assign Reference To (Select One or More)
                        </label>
                        
                        <!-- Error Message for Validation -->
                        @if($assignmentError)
                            <div class="mb-3 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-md">
                                <div class="flex items-center">
                                    <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                    </svg>
                                    <span class="text-sm text-red-800 dark:text-red-300">{{ $assignmentError }}</span>
                                </div>
                            </div>
                        @endif

                        <div class="space-y-3 bg-gray-50 dark:bg-gray-700 p-4 rounded-lg">
                            <!-- Global Assignments Section -->
                            @php
                                $hasSpecificAssignments = in_array('specific_shop_user', $assignmentType) || in_array('specific_gym', $assignmentType);
                                $hasGlobalAssignments = in_array('all_gym', $assignmentType) || in_array('all_shop', $assignmentType);
                            @endphp

                            @if(!$hasSpecificAssignments)
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3.055 11H5a2 2 0 012 2v1a2 2 0 002 2 2 2 0 012 2v2.945M8 3.935V5.5A2.5 2.5 0 0010.5 8h.5a2 2 0 012 2 2 2 0 104 0 2 2 0 012-2h1.064M15 20.488V18a2 2 0 012-2h3.064M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                    </svg>
                                    Global Assignments:
                                </div>
                                <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded transition-colors">
                                    <input type="checkbox" wire:model.live="assignmentType" value="all_gym" 
                                           class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">All Gym Owner/Trainer/Influencer/Dietitian</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Apply to all gym-related users</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded transition-colors">
                                    <input type="checkbox" wire:model.live="assignmentType" value="all_shop" 
                                           class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">All Shop Owner</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Apply to all shop owners</div>
                                    </div>
                                </label>
                                
                                @if(!$hasGlobalAssignments)
                                    <hr class="border-gray-300 dark:border-gray-600">
                                    <div class="text-center py-2">
                                        <span class="text-xs text-gray-500 dark:text-gray-400">OR</span>
                                    </div>
                                @endif
                            @endif

                            <!-- Specific Assignments Section -->
                            @if(!$hasGlobalAssignments)
                                <div class="text-sm font-medium text-gray-700 dark:text-gray-300 mb-2 flex items-center">
                                    <svg class="w-4 h-4 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                    </svg>
                                    Specific Assignments:
                                </div>
                                <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded transition-colors">
                                    <input type="checkbox" wire:model.live="assignmentType" value="specific_shop_user" 
                                           wire:click="validateSpecificSelection"
                                           class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Specific Shop Owner</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Choose individual shop owners</div>
                                    </div>
                                </label>
                                <label class="flex items-center p-2 hover:bg-gray-100 dark:hover:bg-gray-600 rounded transition-colors">
                                    <input type="checkbox" wire:model.live="assignmentType" value="specific_gym" 
                                           wire:click="validateSpecificSelection"
                                           class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500 focus:ring-offset-0">
                                    <div class="flex-1">
                                        <span class="text-sm font-medium text-gray-700 dark:text-gray-300">Specific Gym Owner/Trainer/Influencer/Dietitian</span>
                                        <div class="text-xs text-gray-500 dark:text-gray-400">Choose individual gym users</div>
                                    </div>
                                </label>
                            @endif

                            <!-- Show currently selected type info -->
                            @if($hasGlobalAssignments)
                                <div class="mt-3 p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-700 rounded-md">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm text-green-800 dark:text-green-300">Global assignments selected. Specific assignments are hidden.</span>
                                    </div>
                                </div>
                            @elseif($hasSpecificAssignments)
                                <div class="mt-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
                                    <div class="flex items-center">
                                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                        </svg>
                                        <span class="text-sm text-blue-800 dark:text-blue-300">Specific assignments selected. Global assignments are hidden.</span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    </div>

                    @if(in_array('all_gym', $assignmentType) || in_array('all_shop', $assignmentType))
                        <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-700 rounded-md">
                            <div class="flex items-center">
                                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                </svg>
                                <div>
                                    <p class="text-sm font-medium text-blue-800 dark:text-blue-300">
                                        Assignment to:
                                        @if(in_array('all_gym', $assignmentType))
                                            All Gym Owner/Trainer/Influencer/Dietitian
                                        @endif
                                        @if(in_array('all_gym', $assignmentType) && in_array('all_shop', $assignmentType))
                                            and 
                                        @endif
                                        @if(in_array('all_shop', $assignmentType))
                                            All Shop Owner
                                        @endif
                                    </p>
                                    <p class="text-xs text-blue-600 dark:text-blue-400">This reference will be available for the selected user type(s).</p>
                                </div>
                            </div>
                        </div>
                    @elseif(in_array('specific_shop_user', $assignmentType) || in_array('specific_gym', $assignmentType))
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Search Users
                            </label>
                            <input 
                                wire:model.live="searchItems" 
                                type="text" 
                                placeholder="Search users..." 
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white"
                            >
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                                Select Users
                            </label>
                            <div class="border border-gray-300 dark:border-gray-600 rounded-md max-h-48 overflow-y-auto">
                                @foreach($this->getAssignableItems() as $item)
                                    <label class="flex items-center p-3 hover:bg-gray-50 dark:hover:bg-gray-700 cursor-pointer border-b border-gray-100 dark:border-gray-600 last:border-b-0">
                                        <input 
                                            type="checkbox" 
                                            wire:model.live="selectedItems" 
                                            value="{{ $item->id }}" 
                                            class="mr-3 rounded border-gray-300 text-blue-600 focus:ring-blue-500"
                                        >
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                {{ $item->name }}
                                            </div>
                                            <div class="text-xs text-gray-500 dark:text-gray-400">
                                                {{ $item->email ?? 'ID: ' . $item->id }}
                                            </div>
                                        </div>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endif

                    <div class="flex justify-end space-x-3 pt-4">
                        <button wire:click="closeAssignModal" class="px-4 py-2 text-gray-600 bg-gray-200 rounded-md hover:bg-gray-300 dark:bg-gray-600 dark:text-gray-300 dark:hover:bg-gray-500">
                            Cancel
                        </button>
                        @if(!empty($assignmentType) && (in_array('all_gym', $assignmentType) || in_array('all_shop', $assignmentType) || (!empty($selectedItems) && (in_array('specific_shop_user', $assignmentType) || in_array('specific_gym', $assignmentType)))))
                            <button wire:click="assignReference" class="px-4 py-2 text-white bg-blue-600 rounded-md hover:bg-blue-700">
                                @if($assignmentType === 'all_users')
                                    Assign to All Users
                                @elseif($assignmentType === 'gym_user')
                                    Assign to All Gym Owner/Trainer/Influencer/Dietitian
                                @elseif($assignmentType === 'shop_user')
                                    Assign to All Shop Owner
                                @else
                                    Assign Reference
                                @endif
                            </button>
                        @endif
                    </div>
                </div>

                <!-- Current Assignments -->
                <div class="border-l border-gray-200 dark:border-gray-600 pl-6">
                    <h4 class="text-md font-medium text-gray-900 dark:text-white mb-4">Current Assignments</h4>
                    
                    @if($selectedReference)
                        <!-- Show applicable_to assignments -->
                        @if($selectedReference->applicable_to && is_array($selectedReference->applicable_to))
                            <div class="space-y-2 mb-4">
                                @foreach($selectedReference->applicable_to as $applicableType)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                Global Assignment
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                @if($applicableType === 'all')
                                                    All Users
                                                @elseif($applicableType === 'all_users')
                                                    All Users
                                                @elseif($applicableType === 'all_gym')
                                                    All Gym Owner/Trainer/Influencer/Dietitian
                                                @elseif($applicableType === 'all_shop')
                                                    All Shop Owner
                                                @else
                                                    {{ ucfirst(str_replace('_', ' ', $applicableType)) }}
                                                @endif
                                            </div>
                                        </div>
                                        <button 
                                            wire:click="removeGlobalAssignment('{{ $applicableType }}')" 
                                            class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300"
                                        >
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif

                        <!-- Show specific user assignments -->
                        @if($selectedReference->assignments && $selectedReference->assignments->count() > 0)
                            <div class="space-y-2 max-h-96 overflow-y-auto">
                                @foreach($selectedReference->assignments as $assignment)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-md">
                                        <div class="flex-1">
                                            <div class="text-sm font-medium text-gray-900 dark:text-white">
                                                Specific Assignment
                                            </div>
                                            <div class="text-sm text-gray-500 dark:text-gray-400">
                                                @if($assignment->assignable_type === 'user' && $assignment->assignable)
                                                    {{ $assignment->assignable->name ?? 'N/A' }} ({{ $assignment->assignable->email ?? 'N/A' }})
                                                @else
                                                    N/A
                                                @endif
                                            </div>
                                        </div>
                                        <button wire:click="removeAssignment({{ $assignment->id }})" class="text-red-600 hover:text-red-800 dark:text-red-400 dark:hover:text-red-300">
                                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                                <path fill-rule="evenodd" d="M9 2a1 1 0 000 2h2a1 1 0 100-2H9z" clip-rule="evenodd"></path>
                                                <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path>
                                            </svg>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @elseif(!$selectedReference->applicable_to || empty($selectedReference->applicable_to))
                            <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                                <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                                </svg>
                                <p>No assignments yet</p>
                            </div>
                        @endif
                    @else
                        <div class="text-center text-gray-500 dark:text-gray-400 py-8">
                            <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path>
                            </svg>
                            <p>No assignments yet</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endif
