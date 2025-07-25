<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
    <head>
        @include('partials.head')
    </head>
    <body class="min-h-screen bg-white dark:bg-zinc-800">
        <flux:sidebar sticky stashable class="border-e border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-900">
            <flux:sidebar.toggle class="lg:hidden" icon="x-mark" />

            <a href="{{ route('dashboard') }}" class="me-5 flex items-center space-x-2 rtl:space-x-reverse" wire:navigate>
                <x-app-logo />
            </a>

            <flux:navlist variant="outline">
                <flux:navlist.group :heading="__('Platform')" class="grid">
                    <!-- Dashboard -->
                    <flux:navlist.item icon="home" :href="route('dashboard')" :current="request()->routeIs('dashboard')" wire:navigate>{{ __('Dashboard') }}</flux:navlist.item>
                    
                    @if(auth()->user()->role === 'Super')
                    <!-- Sellers -->
                    <flux:navlist.group expandable :heading="__('Sellers')" class="grid">
                        <flux:navlist.item icon="users" :href="route('sellers.manage')" :current="request()->routeIs('sellers.manage')" wire:navigate>{{ __('All Sellers') }}</flux:navlist.item>
                        <flux:navlist.item icon="user-plus" :href="route('sellers.requests')" :current="request()->routeIs('sellers.requests')" wire:navigate>{{ __('New Seller Requests') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endif
                    
                    <!-- Products -->
                    @php
                        $user = auth()->user();
                        $seller = null;
                        $isApprovedSeller = false;
                        
                        if ($user && $user->role === 'Seller') {
                            $seller = \App\Models\Sellers::where('user_id', $user->id)->first();
                            $isApprovedSeller = $seller && $seller->status === 'approved';
                        }
                    @endphp
                    
                    @if($user->role === 'Super' || $isApprovedSeller)
                    <flux:navlist.group expandable :heading="__('Products')" class="grid">
                        <flux:navlist.item icon="cube" :href="route('products.manage')" :current="request()->routeIs('products.manage')" >{{ __('All Products') }}</flux:navlist.item>
                        @if(auth()->user()->role === 'Super')
                        <flux:navlist.item icon="plus-circle" :href="route('products.requests')" :current="request()->routeIs('products.requests')" wire:navigate>{{ __('New Product Requests') }}</flux:navlist.item>
                        <flux:navlist.item icon="square-3-stack-3d" :href="route('categories.manage')" :current="request()->routeIs('categories.manage')" >{{ __('Categories') }}</flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                    @endif
                    
                    <!-- Orders -->
                    @if($user->role === 'Super' || $isApprovedSeller)
                    <flux:navlist.group expandable :heading="__('Orders')" class="grid">
                        <flux:navlist.item icon="shopping-bag" :href="route('orders.manage')" :current="request()->routeIs('orders.manage')" wire:navigate>{{ __('All Orders') }}</flux:navlist.item>
                        @if($isApprovedSeller && $user->role === 'Seller')
                        <flux:navlist.item icon="shopping-cart" :href="route('bulk-orders.seller')" :current="request()->routeIs('bulk-orders.seller')" wire:navigate>{{ __('Bulk Orders') }}</flux:navlist.item>
                        @endif
                    </flux:navlist.group>
                    @endif
                    
                    <!-- Payouts for Sellers -->
                    @if($isApprovedSeller && $user->role === 'Seller')  
                    <flux:navlist.group expandable :heading="__('Payouts')" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('payouts.sellers')" :current="request()->routeIs('payouts.sellers')" wire:navigate>{{ __('My Payouts') }}</flux:navlist.item>
                    </flux:navlist.group>
                     <flux:navlist.group expandable :heading="__('Coupons')" class="grid">
                        <flux:navlist.item icon="ticket" :href="route('coupons.manage')" :current="request()->routeIs('coupons.manage')" wire:navigate>{{ __('Coupons') }}</flux:navlist.item>
                    </flux:navlist.group>
                    @endif
                    
                    @if(auth()->user()->role === 'Super')
                    <!-- Payout & Commission -->
                    <flux:navlist.group expandable :heading="__('Payout & Commission')" class="grid">
                        <flux:navlist.item icon="banknotes" :href="route('payouts.sellers')" :current="request()->routeIs('payouts.sellers')" wire:navigate>{{ __('Seller Payouts') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('transactions.manage')" :current="request()->routeIs('transactions.manage')" wire:navigate>{{ __('Transaction Management') }}</flux:navlist.item>
                        <flux:navlist.item icon="gift" href="#" :current="request()->routeIs('rewards.influencer')" wire:navigate>{{ __('Influencer Rewards') }}</flux:navlist.item>
                    </flux:navlist.group>
                    
                    <!-- Coupons & Reference Code -->
                    <flux:navlist.group expandable :heading="__('Reference Code')" class="grid">
                        <flux:navlist.item icon="hashtag" href="#" :current="request()->routeIs('reference.codes')" wire:navigate>{{ __('Reference Code') }}</flux:navlist.item>
                    </flux:navlist.group>
                    
                    <!-- Reports & Analytics -->
                    <flux:navlist.group expandable :heading="__('Reports & Analytics')" class="grid">
                        <flux:navlist.item icon="chart-bar" href="#" :current="request()->routeIs('reports.analytics')" wire:navigate>{{ __('Analytics Dashboard') }}</flux:navlist.item>
                    </flux:navlist.group>
                    
                    <!-- Site Settings -->
                    <flux:navlist.group expandable :heading="__('Site Settings')" class="grid">
                        <flux:navlist.item icon="photo" :href="route('banners.manage')" :current="request()->routeIs('banners.manage')" wire:navigate>{{ __('Banners') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-text" :href="route('blogs.manage')" :current="request()->routeIs('blogs.manage')" wire:navigate>{{ __('Blogs') }}</flux:navlist.item>
                        <flux:navlist.item icon="document-duplicate" :href="route('policies.manage')" :current="request()->routeIs('policies.*')" >{{ __('Policy Management') }}</flux:navlist.item>
                        <flux:navlist.item icon="currency-dollar" :href="route('settings.commission')" :current="request()->routeIs('settings.commission')" wire:navigate>{{ __('Global Commission') }}</flux:navlist.item>
                    </flux:navlist.group>
                    
                    <!-- Users (only for Super users) -->
                    <flux:navlist.item icon="users" :href="route('users.manage')" :current="request()->routeIs('users.manage')" wire:navigate>{{ __('Users') }}</flux:navlist.item>
                    @endif
                </flux:navlist.group>
            </flux:navlist>
            

            <flux:spacer />


            <!-- Desktop User Menu -->
            <flux:dropdown class="hidden lg:block" position="bottom" align="start">
                <flux:profile
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    icon:trailing="chevrons-up-down"
                />

                <flux:menu class="w-[220px]">
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:sidebar>

        <!-- Mobile User Menu -->
        <flux:header class="lg:hidden">
            <flux:sidebar.toggle class="lg:hidden" icon="bars-2" inset="left" />

            <flux:spacer />

            <flux:dropdown position="top" align="end">
                <flux:profile
                    :initials="auth()->user()->initials()"
                    icon-trailing="chevron-down"
                />

                <flux:menu>
                    <flux:menu.radio.group>
                        <div class="p-0 text-sm font-normal">
                            <div class="flex items-center gap-2 px-1 py-1.5 text-start text-sm">
                                <span class="relative flex h-8 w-8 shrink-0 overflow-hidden rounded-lg">
                                    <span
                                        class="flex h-full w-full items-center justify-center rounded-lg bg-neutral-200 text-black dark:bg-neutral-700 dark:text-white"
                                    >
                                        {{ auth()->user()->initials() }}
                                    </span>
                                </span>

                                <div class="grid flex-1 text-start text-sm leading-tight">
                                    <span class="truncate font-semibold">{{ auth()->user()->name }}</span>
                                    <span class="truncate text-xs">{{ auth()->user()->email }}</span>
                                </div>
                            </div>
                        </div>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <flux:menu.radio.group>
                        <flux:menu.item :href="route('settings.profile')" icon="cog" wire:navigate>{{ __('Settings') }}</flux:menu.item>
                    </flux:menu.radio.group>

                    <flux:menu.separator />

                    <form method="POST" action="{{ route('logout') }}" class="w-full">
                        @csrf
                        <flux:menu.item as="button" type="submit" icon="arrow-right-start-on-rectangle" class="w-full">
                            {{ __('Log Out') }}
                        </flux:menu.item>
                    </form>
                </flux:menu>
            </flux:dropdown>
        </flux:header>

        {{ $slot }}

        @fluxScripts
    </body>
</html>
