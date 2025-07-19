# Automatic Payout Generation System

## Overview
The BuyProtein platform now includes an automatic payout generation system that creates payouts for sellers every 15 days from their registration/approval date.

## How it Works

### 1. Seller Registration
- When a seller registers and gets approved, their `next_payout_date` is set to 15 days from the approval date
- This creates a unique 15-day cycle for each seller

### 2. Automatic Generation
- The system runs daily at 9:00 AM (`php artisan payouts:generate`)
- It checks all approved sellers to see if their `next_payout_date` has passed
- For each due seller, it calculates earnings for the 15-day period ending on their `next_payout_date`
- Only periods with actual sales will generate payouts

### 3. Payout Details
- **Period**: 15 days ending on the seller's `next_payout_date`
- **Due Date**: 5 days after the period end
- **Payout Date**: 15 days after the period end
- **Status**: Initially set to "unpaid"

### 4. Next Cycle
- After generating a payout, the seller's `next_payout_date` is updated to the next 15-day interval
- This ensures continuous 15-day cycles

## Manual Commands

### Generate Payouts (Automatic)
```bash
php artisan payouts:generate
```

### Force Generate Payouts
```bash
php artisan payouts:generate --force
```

## Database Changes

### New Migration
- Added `next_payout_date` (datetime, nullable) to the `sellers` table

### Model Updates
- **Sellers Model**: Added `next_payout_date` to fillable fields and casts
- **Sellers Model**: Added boot method to set initial payout date on approval
- **PayoutService**: Added `generateAutomaticPayouts()` method

## Scheduling
The command is scheduled to run daily at 9:00 AM in `routes/console.php`:

```php
Schedule::command('payouts:generate')
    ->daily()
    ->at('09:00')
    ->withoutOverlapping()
    ->runInBackground();
```

## Benefits

1. **Consistency**: Each seller gets payouts exactly every 15 days
2. **Automation**: No manual intervention required
3. **Reliability**: Built-in checks prevent duplicate payouts
4. **Efficiency**: Only generates payouts when there are actual sales
5. **Transparency**: Clear tracking of each seller's payout schedule

## Example Timeline

**Seller Registration**: January 1, 2025
- **First Payout Period**: January 1-15, 2025 (generated on January 16)
- **Second Payout Period**: January 16-30, 2025 (generated on January 31)
- **Third Payout Period**: January 31-February 14, 2025 (generated on February 15)
- And so on...

## UI Updates
- Added informational banner explaining the automatic system
- Changed "Generate Payouts" button to "Manual Generation" for clarity
- Added context about the 15-day automatic cycle

## Notes
- The manual generation feature is still available for custom periods
- The system gracefully handles edge cases (no sales, existing payouts, etc.)
- All existing manual payout functionality remains unchanged
