<?php

use App\Models\User;
use Livewire\WithPagination;
use Livewire\Volt\Component;
use Illuminate\Support\Facades\Storage;
use App\Models\Sellers;

new class extends Component
{
    use WithPagination;

    public $search = '';
    public $statusFilter = 'pending';
    public $showViewModal = false;
    public $showStatusModal = false;
    public $viewingUser = null;
    public $userToUpdate = null;
    public $newStatus = null;
    public $rejectReason = '';

    public function with()
    {
        $approvalRoles = ['Seller', 'Gym Owner/Trainer/Influencer/Dietitian', 'Shop Owner'];

        $query = User::whereIn('role', $approvalRoles)
            ->whereNotNull('approval_status');

        if ($this->search) {
            $query->where(function ($q) {
                $q->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('email', 'like', '%' . $this->search . '%');
            });
        }

        if ($this->statusFilter) {
            $query->where('approval_status', $this->statusFilter);
        }

        return [
            'users'         => $query->latest()->paginate(10),
            'totalRequests' => User::whereIn('role', $approvalRoles)->whereNotNull('approval_status')->count(),
            'pendingCount'  => User::whereIn('role', $approvalRoles)->where('approval_status', 'pending')->count(),
            'approvedCount' => User::whereIn('role', $approvalRoles)->where('approval_status', 'approved')->count(),
            'rejectedCount' => 0,
        ];
    }

    public function openViewModal($id)
    {
        $this->viewingUser = User::findOrFail($id);
        $this->showViewModal = true;
    }

    public function closeViewModal()
    {
        $this->showViewModal = false;
        $this->viewingUser = null;
    }

    public function confirmStatusChange($id, $status)
    {
        $this->userToUpdate = User::findOrFail($id);
        $this->newStatus = $status;
        $this->rejectReason = '';
        $this->showStatusModal = true;
    }

    public function closeStatusModal()
    {
        $this->showStatusModal = false;
        $this->userToUpdate = null;
        $this->newStatus = null;
        $this->rejectReason = '';
    }

    public function updateStatus()
    {
        if (!$this->userToUpdate || !$this->newStatus) return;

        if ($this->newStatus === 'approved') {
            $this->userToUpdate->update(['approval_status' => 'approved']);

            // Send approval notification email
            try {
                \Mail::to($this->userToUpdate->email)->send(new \App\Mail\WelcomeMail($this->userToUpdate));
            } catch (\Exception $e) {
                \Log::error('Failed to send approval email: ' . $e->getMessage());
            }

            session()->flash('message', "User account has been approved successfully!");
        } else {
            // Rejection: delete the user and associated data
            $user = $this->userToUpdate;

            // Delete seller record if exists
            $seller = Sellers::where('user_id', $user->id)->first();
            if ($seller) {
                // Clean up seller uploaded files
                if ($seller->brand_logo) {
                    Storage::disk('public')->delete($seller->brand_logo);
                }
                if ($seller->brand_certificate) {
                    Storage::disk('public')->delete($seller->brand_certificate);
                }
                $seller->delete();
            }

            // Clean up user uploaded files
            if ($user->document_proof) {
                Storage::disk('public')->delete($user->document_proof);
            }
            if ($user->business_images) {
                $images = json_decode($user->business_images, true) ?? [];
                foreach ($images as $img) {
                    Storage::disk('public')->delete($img);
                }
            }

            $user->delete();

            session()->flash('message', "User account has been rejected and deleted successfully!");
        }

        $this->closeStatusModal();
    }

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatusFilter()
    {
        $this->resetPage();
    }
}; ?>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-800 py-8">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white">Account Approval Requests</h1>
            <p class="mt-2 text-sm text-gray-600 dark:text-gray-300">Review and manage Seller, Shop Owner & Gym Owner / Trainer / Influencer / Dietitian account requests</p>
        </div>

        <!-- Stats -->
        <div class="grid grid-cols-2 md:grid-cols-3 gap-4 mb-8">
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Total</p>
                    <p class="text-2xl font-bold text-gray-900 dark:text-white">{{ $totalRequests }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-10 h-10 bg-yellow-100 dark:bg-yellow-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-yellow-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Pending</p>
                    <p class="text-2xl font-bold text-yellow-600">{{ $pendingCount }}</p>
                </div>
            </div>
            <div class="bg-white dark:bg-zinc-900 rounded-lg shadow p-5 flex items-center gap-4">
                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/50 rounded-lg flex items-center justify-center">
                    <svg class="w-5 h-5 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                </div>
                <div>
                    <p class="text-xs text-gray-500 dark:text-gray-400">Approved</p>
                    <p class="text-2xl font-bold text-green-600">{{ $approvedCount }}</p>
                </div>
            </div>
        </div>

        <!-- Filters -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow mb-6 p-4">
            <div class="flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1">
                    <input type="text" wire:model.live="search" placeholder="Search by name or email..."
                        class="w-full pl-10 pr-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                </div>
                <select wire:model.live="statusFilter"
                    class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg focus:ring-2 focus:ring-blue-500 bg-white dark:bg-zinc-800 text-gray-900 dark:text-white">
                    <option value="">All Status</option>
                    <option value="pending">Pending</option>
                    <option value="approved">Approved</option>
                </select>
            </div>
        </div>

        <!-- Flash Message -->
        @if (session()->has('message'))
            <div class="bg-green-50 dark:bg-green-900/50 border border-green-200 dark:border-green-800 text-green-700 dark:text-green-300 px-4 py-3 rounded-lg mb-6">
                {{ session('message') }}
            </div>
        @endif

        <!-- Table -->
        <div class="bg-white dark:bg-zinc-900 rounded-lg shadow overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full">
                    <thead class="bg-gray-50 dark:bg-zinc-800">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Actions</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Role</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Documents</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Status</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Registered</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($users as $user)
                            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                <td class="px-6 py-4 whitespace-nowrap text-sm font-medium">
                                    <div class="flex items-center gap-2">
                                        <!-- View -->
                                        <button wire:click="openViewModal({{ $user->id }})"
                                            class="text-blue-600 dark:text-blue-400 hover:text-blue-900" title="View Details">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                            </svg>
                                        </button>
                                        @if($user->approval_status !== 'approved')
                                        <!-- Approve -->
                                        <button wire:click="confirmStatusChange({{ $user->id }}, 'approved')"
                                            class="text-green-600 dark:text-green-400 hover:text-green-900" title="Approve">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                        @if($user->approval_status !== 'rejected')
                                        <!-- Reject -->
                                        <button wire:click="confirmStatusChange({{ $user->id }}, 'rejected')"
                                            class="text-red-600 dark:text-red-400 hover:text-red-900" title="Reject">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</div>
                                    <div class="text-sm text-gray-500 dark:text-gray-400">{{ $user->email }}</div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 dark:text-gray-300">
                                    {{ $user->role }}
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm">
                                    <div class="flex flex-col gap-1">
                                        @if($user->document_proof)
                                            <a href="{{ Storage::url($user->document_proof) }}" target="_blank"
                                                class="text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.586-6.586a2 2 0 00-2.828-2.828z" /></svg>
                                                Document Proof
                                            </a>
                                        @endif
                                        @if($user->social_media_link)
                                            <a href="{{ $user->social_media_link }}" target="_blank"
                                                class="text-purple-600 dark:text-purple-400 hover:underline flex items-center gap-1 truncate max-w-xs">
                                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1" /></svg>
                                                Social Link
                                            </a>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @php
                                        $badgeClass = match($user->approval_status) {
                                            'pending'  => 'bg-yellow-100 dark:bg-yellow-900/50 text-yellow-800 dark:text-yellow-300',
                                            'approved' => 'bg-green-100 dark:bg-green-900/50 text-green-800 dark:text-green-300',
                                            'rejected' => 'bg-red-100 dark:bg-red-900/50 text-red-800 dark:text-red-300',
                                            default    => 'bg-gray-100 text-gray-800',
                                        };
                                    @endphp
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium {{ $badgeClass }}">
                                        {{ ucfirst($user->approval_status) }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400">
                                    {{ $user->created_at->format('M d, Y') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-6 py-10 text-center text-gray-500 dark:text-gray-400">
                                    No requests found.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-3 border-t border-gray-200 dark:border-gray-700">
                {{ $users->links() }}
            </div>
        </div>
    </div>

    <!-- View Modal -->
    @if($showViewModal && $viewingUser)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-lg w-full max-h-[90vh] overflow-y-auto">
                <div class="p-6">
                    <div class="flex items-center justify-between mb-5">
                        <h2 class="text-xl font-bold text-gray-900 dark:text-white">User Details</h2>
                        <button wire:click="closeViewModal" class="text-gray-400 hover:text-gray-600 dark:hover:text-gray-200">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
                        </button>
                    </div>
                    <div class="space-y-4 text-sm text-gray-700 dark:text-gray-300">
                        <div><span class="font-semibold">Name:</span> {{ $viewingUser->name }}</div>
                        <div><span class="font-semibold">Email:</span> {{ $viewingUser->email }}</div>
                        <div><span class="font-semibold">Role:</span> {{ $viewingUser->role }}</div>
                        <div>
                            <span class="font-semibold">Approval Status:</span>
                            <span class="ml-1 inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $viewingUser->approval_status === 'approved' ? 'bg-green-100 text-green-800' : ($viewingUser->approval_status === 'rejected' ? 'bg-red-100 text-red-800' : 'bg-yellow-100 text-yellow-800') }}">
                                {{ ucfirst($viewingUser->approval_status) }}
                            </span>
                        </div>
                        @if($viewingUser->social_media_link)
                            <div><span class="font-semibold">Social Media:</span>
                                <a href="{{ $viewingUser->social_media_link }}" target="_blank" class="text-blue-500 hover:underline ml-1">{{ $viewingUser->social_media_link }}</a>
                            </div>
                        @endif
                        @if($viewingUser->document_proof)
                            <div>
                                <span class="font-semibold">Document Proof:</span>
                                <a href="{{ Storage::url($viewingUser->document_proof) }}" target="_blank"
                                    class="ml-1 text-blue-600 dark:text-blue-400 hover:underline">View Document</a>
                            </div>
                        @endif
                        @if($viewingUser->business_images)
                            @php $images = json_decode($viewingUser->business_images, true) ?? []; @endphp
                            @if(count($images) > 0)
                                <div>
                                    <span class="font-semibold">Business Images:</span>
                                    <div class="mt-2 grid grid-cols-3 gap-2">
                                        @foreach($images as $img)
                                            <a href="{{ Storage::url($img) }}" target="_blank">
                                                <img src="{{ Storage::url($img) }}" class="w-full h-20 object-cover rounded-lg" alt="Business Image">
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endif
                        <div><span class="font-semibold">Registered:</span> {{ $viewingUser->created_at->format('M d, Y h:i A') }}</div>
                    </div>

                    <div class="flex gap-3 mt-6">
                        @if($viewingUser->approval_status !== 'approved')
                            <button wire:click="confirmStatusChange({{ $viewingUser->id }}, 'approved')"
                                class="flex-1 bg-green-600 hover:bg-green-700 text-white py-2 px-4 rounded-lg font-medium text-sm">
                                Approve
                            </button>
                        @endif
                        @if($viewingUser->approval_status !== 'rejected')
                            <button wire:click="confirmStatusChange({{ $viewingUser->id }}, 'rejected')"
                                class="flex-1 bg-red-600 hover:bg-red-700 text-white py-2 px-4 rounded-lg font-medium text-sm">
                                Reject
                            </button>
                        @endif
                        <button wire:click="closeViewModal"
                            class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg font-medium text-sm">
                            Close
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- Confirm Status Modal -->
    @if($showStatusModal && $userToUpdate)
        <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
            <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-sm w-full p-6">
                <div class="text-center mb-5">
                    @if($newStatus === 'approved')
                        <div class="w-14 h-14 bg-green-100 dark:bg-green-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Approve Account?</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            This will approve <strong>{{ $userToUpdate->name }}</strong>'s account and grant them access.
                        </p>
                    @else
                        <div class="w-14 h-14 bg-red-100 dark:bg-red-900/50 rounded-full flex items-center justify-center mx-auto mb-3">
                            <svg class="w-7 h-7 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" /></svg>
                        </div>
                        <h3 class="text-lg font-bold text-gray-900 dark:text-white">Reject & Delete Account?</h3>
                        <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">
                            This will <strong>permanently delete</strong> <strong>{{ $userToUpdate->name }}</strong>'s account and all associated data. This action cannot be undone.
                        </p>
                    @endif
                </div>
                <div class="flex gap-3">
                    <button wire:click="updateStatus"
                        class="flex-1 {{ $newStatus === 'approved' ? 'bg-green-600 hover:bg-green-700' : 'bg-red-600 hover:bg-red-700' }} text-white py-2 px-4 rounded-lg font-medium text-sm">
                        Confirm {{ ucfirst($newStatus) }}
                    </button>
                    <button wire:click="closeStatusModal"
                        class="flex-1 bg-gray-200 hover:bg-gray-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-gray-700 dark:text-gray-300 py-2 px-4 rounded-lg font-medium text-sm">
                        Cancel
                    </button>
                </div>
            </div>
        </div>
    @endif
</div>
