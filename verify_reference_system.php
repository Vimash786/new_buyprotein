<?php

/**
 * Reference System Verification Script
 * 
 * This script can be run via artisan tinker to verify that the Reference system is working correctly.
 * Usage: php artisan tinker --execute="require_once 'verify_reference_system.php';"
 */

use App\Models\Reference;
use App\Models\ReferenceAssign;
use App\Models\ReferenceUsage;
use App\Models\User;
use Carbon\Carbon;

echo "=== Reference System Verification ===\n";

try {
    // Test 1: Check if Reference model can be instantiated
    echo "1. Testing Reference model instantiation... ";
    $reference = new Reference();
    echo "✓ Success\n";

    // Test 2: Check database connection and table structure
    echo "2. Testing database connection and table structure... ";
    $count = Reference::count();
    echo "✓ Success (Found {$count} references)\n";

    // Test 3: Test scopes
    echo "3. Testing model scopes... ";
    $activeCount = Reference::active()->count();
    $expiredCount = Reference::expired()->count();
    $upcomingCount = Reference::upcoming()->count();
    echo "✓ Success (Active: {$activeCount}, Expired: {$expiredCount}, Upcoming: {$upcomingCount})\n";

    // Test 4: Test relationships
    echo "4. Testing model relationships... ";
    $reference = Reference::with(['assignments', 'usages'])->first();
    if ($reference) {
        $assignmentsCount = $reference->assignments->count();
        $usagesCount = $reference->usages->count();
        echo "✓ Success (Reference ID: {$reference->id}, Assignments: {$assignmentsCount}, Usages: {$usagesCount})\n";
    } else {
        echo "⚠ No references found to test relationships\n";
    }

    // Test 5: Test discount calculation
    echo "5. Testing discount calculation methods... ";
    $testReference = new Reference([
        'type' => 'percentage',
        'value' => 10,
        'minimum_amount' => 50,
        'maximum_discount' => 100
    ]);
    
    $discount1 = $testReference->calculateDiscount(100); // Should be 10
    $discount2 = $testReference->calculateDiscount(30);  // Should be 0 (below minimum)
    $discount3 = $testReference->calculateDiscount(2000); // Should be 100 (capped)
    
    if ($discount1 == 10 && $discount2 == 0 && $discount3 == 100) {
        echo "✓ Success (Percentage calculations working)\n";
    } else {
        echo "✗ Failed (Expected: 10, 0, 100 | Got: {$discount1}, {$discount2}, {$discount3})\n";
    }

    // Test 6: Test fixed discount calculation
    echo "6. Testing fixed discount calculation... ";
    $fixedReference = new Reference([
        'type' => 'fixed',
        'value' => 25,
        'minimum_amount' => 50
    ]);
    
    $fixedDiscount1 = $fixedReference->calculateDiscount(100); // Should be 25
    $fixedDiscount2 = $fixedReference->calculateDiscount(30);  // Should be 0 (below minimum)
    $fixedDiscount3 = $fixedReference->calculateDiscount(20);  // Should be 0 (below minimum)
    
    if ($fixedDiscount1 == 25 && $fixedDiscount2 == 0 && $fixedDiscount3 == 0) {
        echo "✓ Success (Fixed calculations working)\n";
    } else {
        echo "✗ Failed (Expected: 25, 0, 0 | Got: {$fixedDiscount1}, {$fixedDiscount2}, {$fixedDiscount3})\n";
    }

    // Test 7: Test validity checks
    echo "7. Testing validity checks... ";
    $validReference = new Reference([
        'status' => 'active',
        'starts_at' => Carbon::now()->subDay(),
        'expires_at' => Carbon::now()->addDay(),
        'usage_limit' => 10,
        'used_count' => 5
    ]);
    
    $expiredReference = new Reference([
        'status' => 'active',
        'starts_at' => Carbon::now()->subDays(10),
        'expires_at' => Carbon::now()->subDay(),
        'usage_limit' => 10,
        'used_count' => 5
    ]);
    
    if ($validReference->isValid() && !$expiredReference->isValid()) {
        echo "✓ Success (Validity checks working)\n";
    } else {
        echo "✗ Failed (Validity checks not working properly)\n";
    }

    // Test 8: Test model casts
    echo "8. Testing model casts... ";
    if ($reference && is_array($reference->user_types)) {
        echo "✓ Success (user_types cast to array)\n";
    } else {
        echo "⚠ Warning (No reference with user_types found or cast not working)\n";
    }

    echo "\n=== Verification Complete ===\n";
    echo "If all tests show ✓ Success, your Reference system is working correctly!\n";
    echo "If you see ✗ Failed or ⚠ Warning, please check the specific issues mentioned.\n\n";

} catch (\Exception $e) {
    echo "✗ Error occurred during verification: " . $e->getMessage() . "\n";
    echo "Please check your database connection and ensure migrations have been run.\n";
}
