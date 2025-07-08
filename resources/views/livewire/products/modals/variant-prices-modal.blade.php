<!-- Variant Prices Modal -->
@if($showVariantPricesModal && $selectedProduct)
    <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
        <div class="bg-white dark:bg-zinc-900 rounded-lg max-w-6xl w-full max-h-[80vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex items-center justify-between mb-6">
                    <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                        Variant Prices for {{ $selectedProduct->name }}
                    </h2>
                    <button wire:click="closeVariantPricesModal" class="text-gray-400 hover:text-gray-600 dark:text-gray-300 dark:hover:text-gray-100">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gray-50 dark:bg-zinc-800">
                            <tr>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Variant</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">User Type Prices</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Discounts</th>
                                <th class="px-4 py-2 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase tracking-wider">Final Prices</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white dark:bg-zinc-900 divide-y divide-gray-200 dark:divide-gray-700">
                            @foreach($variantPrices as $variant)
                                <tr class="hover:bg-gray-50 dark:hover:bg-zinc-800">
                                    <td class="px-4 py-2 whitespace-nowrap text-sm text-gray-900 dark:text-white font-medium">
                                        {{ $variant['variant_name'] }}
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        <div class="flex flex-col gap-1">
                                            @if($variant['gym_owner_price'])
                                                <div><span class="font-medium text-blue-600">Gym Owner:</span> ₹{{ number_format($variant['gym_owner_price'], 2) }}</div>
                                            @endif
                                            @if($variant['regular_user_price'])
                                                <div><span class="font-medium text-green-600">Regular User:</span> ₹{{ number_format($variant['regular_user_price'], 2) }}</div>
                                            @endif
                                            @if($variant['shop_owner_price'])
                                                <div><span class="font-medium text-purple-600">Shop Owner:</span> ₹{{ number_format($variant['shop_owner_price'], 2) }}</div>
                                            @endif
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        <div class="flex flex-col gap-1">
                                            <div><span class="font-medium text-blue-600">Gym Owner:</span> 
                                                @if($variant['gym_owner_discount'] > 0)
                                                    <span class="text-red-600">{{ $variant['gym_owner_discount'] }}%</span>
                                                @else
                                                    <span class="text-gray-500">-</span>
                                                @endif
                                            </div>
                                            <div><span class="font-medium text-green-600">Regular User:</span> 
                                                @if($variant['regular_user_discount'] > 0)
                                                    <span class="text-red-600">{{ $variant['regular_user_discount'] }}%</span>
                                                @else
                                                    <span class="text-gray-500">-</span>
                                                @endif
                                            </div>
                                            <div><span class="font-medium text-purple-600">Shop Owner:</span> 
                                                @if($variant['shop_owner_discount'] > 0)
                                                    <span class="text-red-600">{{ $variant['shop_owner_discount'] }}%</span>
                                                @else
                                                    <span class="text-gray-500">-</span>
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-2 text-sm text-gray-900 dark:text-white">
                                        <div class="flex flex-col gap-1">
                                            @if($variant['gym_owner_final_price'])
                                                <div><span class="font-medium text-blue-600">Gym Owner:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['gym_owner_final_price'], 2) }}</span></div>
                                            @endif
                                            @if($variant['regular_user_final_price'])
                                                <div><span class="font-medium text-green-600">Regular User:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['regular_user_final_price'], 2) }}</span></div>
                                            @endif
                                            @if($variant['shop_owner_final_price'])
                                                <div><span class="font-medium text-purple-600">Shop Owner:</span> <span class="font-bold text-green-600">₹{{ number_format($variant['shop_owner_final_price'], 2) }}</span></div>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endif
