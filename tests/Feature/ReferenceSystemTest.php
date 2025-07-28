<?php

namespace Tests\Feature;

use App\Models\Reference;
use App\Models\ReferenceAssign;
use App\Models\ReferenceUsage;
use App\Models\User;
use App\Models\products;
use App\Models\orders;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use Carbon\Carbon;

class ReferenceSystemTest extends TestCase
{
    use RefreshDatabase;

    protected $user;
    protected $product;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create a test user
        $this->user = User::factory()->create([
            'role' => 'Super'
        ]);
        
        // Create a test product
        $this->product = products::factory()->create();
    }

    /** @test */
    public function it_can_create_a_reference()
    {
        $referenceData = [
            'code' => 'TEST-REF-001',
            'name' => 'Test Reference',
            'description' => 'Test reference for unit testing',
            'type' => 'percentage',
            'value' => 10.00,
            'minimum_amount' => 100.00,
            'maximum_discount' => 50.00,
            'usage_limit' => 100,
            'user_usage_limit' => 1,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
            'applicable_to' => 'all',
            'user_types' => ['User', 'Seller'],
        ];

        $reference = Reference::create($referenceData);

        $this->assertDatabaseHas('reference', [
            'code' => 'TEST-REF-001',
            'name' => 'Test Reference',
            'type' => 'percentage',
            'value' => 10.00,
        ]);

        $this->assertTrue($reference->isValid());
        $this->assertFalse($reference->isExpired());
        $this->assertFalse($reference->isUpcoming());
    }

    /** @test */
    public function it_can_calculate_percentage_discount()
    {
        $reference = Reference::create([
            'code' => 'PERCENT-10',
            'name' => 'Ten Percent Off',
            'type' => 'percentage',
            'value' => 10.00,
            'minimum_amount' => 50.00,
            'maximum_discount' => 100.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        // Test discount calculation
        $this->assertEquals(10.00, $reference->calculateDiscount(100.00)); // 10% of 100
        $this->assertEquals(0, $reference->calculateDiscount(30.00)); // Below minimum
        $this->assertEquals(100.00, $reference->calculateDiscount(2000.00)); // Capped at maximum
    }

    /** @test */
    public function it_can_calculate_fixed_discount()
    {
        $reference = Reference::create([
            'code' => 'FIXED-25',
            'name' => 'Twenty Five Off',
            'type' => 'fixed',
            'value' => 25.00,
            'minimum_amount' => 50.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        // Test discount calculation
        $this->assertEquals(25.00, $reference->calculateDiscount(100.00)); // Fixed 25
        $this->assertEquals(0, $reference->calculateDiscount(30.00)); // Below minimum
        $this->assertEquals(30.00, $reference->calculateDiscount(30.00)); // Can't exceed total
    }

    /** @test */
    public function it_can_assign_reference_to_user()
    {
        $reference = Reference::create([
            'code' => 'USER-REF',
            'name' => 'User Reference',
            'type' => 'percentage',
            'value' => 15.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        $assignment = $reference->assignTo($this->user);

        $this->assertInstanceOf(ReferenceAssign::class, $assignment);
        $this->assertTrue($reference->isAssignedTo($this->user));
        
        $this->assertDatabaseHas('reference_assign', [
            'reference_id' => $reference->id,
            'assignable_type' => 'user',
            'assignable_id' => $this->user->id,
        ]);
    }

    /** @test */
    public function it_can_remove_assignment()
    {
        $reference = Reference::create([
            'code' => 'REMOVE-REF',
            'name' => 'Remove Reference',
            'type' => 'percentage',
            'value' => 20.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        $reference->assignTo($this->user);
        $this->assertTrue($reference->isAssignedTo($this->user));

        $reference->removeAssignmentFrom($this->user);
        $this->assertFalse($reference->isAssignedTo($this->user));
    }

    /** @test */
    public function it_can_track_usage()
    {
        $reference = Reference::create([
            'code' => 'USAGE-REF',
            'name' => 'Usage Reference',
            'type' => 'percentage',
            'value' => 10.00,
            'usage_limit' => 5,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        // Create usage record
        $usage = ReferenceUsage::create([
            'reference_id' => $reference->id,
            'user_id' => $this->user->id,
            'order_id' => 1,
            'discount_amount' => 10.00,
            'order_total' => 100.00,
        ]);

        $this->assertInstanceOf(ReferenceUsage::class, $usage);
        $this->assertEquals($reference->id, $usage->reference_id);
        $this->assertEquals($this->user->id, $usage->user_id);
    }

    /** @test */
    public function it_can_check_validity_based_on_dates()
    {
        // Expired reference
        $expiredRef = Reference::create([
            'code' => 'EXPIRED-REF',
            'name' => 'Expired Reference',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->subDays(1),
            'status' => 'active',
        ]);

        // Upcoming reference
        $upcomingRef = Reference::create([
            'code' => 'UPCOMING-REF',
            'name' => 'Upcoming Reference',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now()->addDays(1),
            'expires_at' => Carbon::now()->addDays(10),
            'status' => 'active',
        ]);

        $this->assertTrue($expiredRef->isExpired());
        $this->assertFalse($expiredRef->isValid());

        $this->assertTrue($upcomingRef->isUpcoming());
        $this->assertFalse($upcomingRef->isValid());
    }

    /** @test */
    public function it_has_correct_status_attributes()
    {
        // Active reference
        $activeRef = Reference::create([
            'code' => 'ACTIVE-REF',
            'name' => 'Active Reference',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        $this->assertEquals('green', $activeRef->status_color);
        $this->assertEquals('Active', $activeRef->human_status);

        // Expired reference
        $expiredRef = Reference::create([
            'code' => 'EXPIRED-REF-2',
            'name' => 'Expired Reference 2',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->subDays(1),
            'status' => 'active',
        ]);

        $this->assertEquals('red', $expiredRef->status_color);
        $this->assertEquals('Expired', $expiredRef->human_status);
    }

    /** @test */
    public function it_can_scope_active_references()
    {
        // Create active reference
        Reference::create([
            'code' => 'ACTIVE-SCOPE',
            'name' => 'Active Scope',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'active',
        ]);

        // Create inactive reference
        Reference::create([
            'code' => 'INACTIVE-SCOPE',
            'name' => 'Inactive Scope',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now(),
            'expires_at' => Carbon::now()->addDays(30),
            'status' => 'inactive',
        ]);

        // Create expired reference
        Reference::create([
            'code' => 'EXPIRED-SCOPE',
            'name' => 'Expired Scope',
            'type' => 'percentage',
            'value' => 10.00,
            'starts_at' => Carbon::now()->subDays(10),
            'expires_at' => Carbon::now()->subDays(1),
            'status' => 'active',
        ]);

        $activeReferences = Reference::active()->get();
        $this->assertEquals(1, $activeReferences->count());
        $this->assertEquals('ACTIVE-SCOPE', $activeReferences->first()->code);
    }
}
