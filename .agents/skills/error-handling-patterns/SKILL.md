---
name: error-handling-patterns
description: >-
  Build resilient Laravel 11 applications with robust error handling strategies tailored
  to payroll computations, statutory formulas, DB transactions, Form Requests, and Pest tests.
---

# Error Handling Patterns (Laravel 11 & TNVS Payroll System)

This skill provides comprehensive error handling standards and patterns tailored specifically for Laravel 11, PHP 8.2+, Eloquent ORM, database transactions, statutory calculations, and Pest testing in this Payroll & Benefits application.

---

## When to Use This Skill

- Implementing error handling in new controllers, services, actions, or models.
- Designing error-resilient payroll computations, batch releases, and benefit allocations.
- Handling database transaction rollbacks and data consistency across multi-table writes.
- Creating clear, non-technical error notifications and validation feedback for users in Blade and Alpine.js.
- Writing Pest feature and unit tests to verify exception handling, invalid inputs, and boundary caps.
- Debugging production exceptions and preventing unhandled crashes or silent failures.

---

## Core Concepts & Philosophy

### 1. Error Handling Philosophies in Laravel

| Type | When to Use | Mechanism in System |
| :--- | :--- | :--- |
| **Validation Exceptions** | Invalid user input, out-of-bound percentages, missing required fields | Laravel Form Requests (`php artisan make:request`) returning HTTP 422 (JSON) or redirecting `back()->withErrors()` |
| **Domain Exceptions** | Business rule violations (e.g., locked payroll cutoff, exceeding loan balance, negative salary floor) | Dedicated Domain Exceptions extending `\Exception` or `\RuntimeException` |
| **Database Exceptions** | Foreign key constraints, deadlock, unique collision | Managed inside `DB::transaction(...)` with automatic rollback |
| **Fallback Values** | Optional configurations, missing settings | `CompanySetting::getValue('key', $fallbackDefault)` |

### 2. Error Categories

- **Recoverable Errors**:
  - Missing setting (use safe fallback default).
  - Form validation failure (return friendly validation error message to user).
  - Backdated effective date (automatically calculate retroactive adjustment).
- **Unrecoverable Errors**:
  - Data corruption during payroll batch commit (abort and rollback transaction immediately).
  - Unauthorized approval attempt (log security audit trail and abort with HTTP 403).

---

## Best Practices & Rules

1. **Strict Types**: Always declare strict types (`declare(strict_types=1);`) at the top of every PHP file to catch type mismatches at runtime.
2. **Database Transactions**: Wrap all multi-step write operations in `DB::transaction(function () { ... })` so that if any step throws an exception, the entire operation rolls back atomically.
3. **Never Swallow Errors**: Avoid empty `catch (\Exception $e) {}` blocks. If caught, log appropriately or wrap into a meaningful domain exception.
4. **Clean User-Facing Messages**:
   - Provide plain English explanations without internal jargon or database error strings.
   - Use `session('error')` with clear instructions on how to correct the issue.
5. **No Emojis**: Never use emoji characters in error messages, logs, or UI alert components; use SVG icons or styled Tailwind alert banners.
6. **Form Request First**: Never perform validation manually in controllers. Delegate to Form Requests.

---

## Concrete Code Patterns

### Pattern 1: Domain Exception with DB Transaction Rollback

```php
<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Exceptions\PayrollLockedException;
use App\Models\PayrollAuditTrail;
use App\Models\SalaryComputation;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrollFinalizationService
{
    /**
     * Finalize and release payroll batch atomically.
     *
     * @throws PayrollLockedException
     * @throws \Throwable
     */
    public function releaseBatch(int $cutoffId, int $adminUserId): bool
    {
        return DB::transaction(function () use ($cutoffId, $adminUserId) {
            $computations = SalaryComputation::where('cutoff_id', $cutoffId)->get();

            if ($computations->isEmpty()) {
                throw new \InvalidArgumentException("No salary computations found for cutoff ID {$cutoffId}.");
            }

            // Check if already released
            if ($computations->first()->status === 'released') {
                throw new PayrollLockedException("Cutoff ID {$cutoffId} has already been released and cannot be modified.");
            }

            try {
                foreach ($computations as $computation) {
                    $computation->update([
                        'status' => 'released',
                        'released_at' => now(),
                        'released_by' => $adminUserId,
                    ]);
                }

                PayrollAuditTrail::create([
                    'action' => 'PAYROLL_BATCH_RELEASED',
                    'model_type' => SalaryComputation::class,
                    'model_id' => $cutoffId,
                    'user_name' => 'Finance Admin',
                    'ip_address' => request()->ip() ?? '127.0.0.1',
                    'new_values' => ['cutoff_id' => $cutoffId, 'count' => $computations->count()],
                ]);

                return true;
            } catch (\Throwable $e) {
                Log::error("Failed to release payroll batch for cutoff {$cutoffId}: {$e->getMessage()}", [
                    'cutoff_id' => $cutoffId,
                    'admin_user_id' => $adminUserId,
                    'trace' => $e->getTraceAsString(),
                ]);

                throw $e; // Re-throw to trigger DB::transaction rollback
            }
        });
    }
}
```

---

### Pattern 2: Dynamic Settings with Safe Fallback Default

```php
// Always provide fallback constant when reading company configuration
$workingDays = (float) CompanySetting::getValue('standard_working_days_per_month', 26.0);
$driverSharePct = (float) CompanySetting::getValue('driver_insurance_rate_percentage', 1.0);
```

---

### Pattern 3: Form Request Error Handling & Friendly User Alerts

In Controller:
```php
public function storeClaim(StoreClaimRequest $request): RedirectResponse
{
    try {
        $claim = $this->claimService->submitClaim($request->validated());

        return redirect()->route('claims.expenses')
            ->with('status', 'Expense claim submitted successfully for HR review.');
    } catch (\DomainException $e) {
        return redirect()->back()
            ->withInput()
            ->with('error', $e->getMessage());
    } catch (\Throwable $e) {
        Log::error("Unexpected error submitting claim: {$e->getMessage()}");

        return redirect()->back()
            ->withInput()
            ->with('error', 'Unable to process claim submission at this time. Please try again or contact support.');
    }
}
```

In Blade (`resources/views/...`):
```blade
@if(session('error'))
    <div class="mb-6 p-4 bg-rose-50 border border-rose-200 text-rose-900 text-xs rounded-2xl font-bold flex items-center gap-2.5 shadow-2xs">
        <svg class="w-4 h-4 text-rose-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <span>{{ session('error') }}</span>
    </div>
@endif
```

---

## Pest Testing Error Patterns

### Testing Expected Exceptions in Pest

```php
test('cannot release an already released payroll cutoff', function () {
    $service = app(PayrollFinalizationService::class);

    // Seed released computation
    SalaryComputation::factory()->create([
        'cutoff_id' => 10,
        'status' => 'released',
    ]);

    expect(fn () => $service->releaseBatch(10, $this->adminUser->id))
        ->toThrow(PayrollLockedException::class, 'Cutoff ID 10 has already been released');
});
```

### Testing Form Request Validation Rejection in Pest

```php
test('rejects claim submission when receipt amount is negative or exceeds policy ceiling', function () {
    $response = $this->actingAs($this->driverUser)
        ->post(route('claims.expenses.store'), [
            'category_id' => $this->fuelCategory->id,
            'amount' => -500.00,
        ]);

    $response->assertSessionHasErrors(['amount']);
});
```

### Testing DB Rollback on Calculation Failure

```php
test('database rolls back all state modifications if an unexpected exception occurs during batch commit', function () {
    $initialSalary = $this->employee->monthly_rate;

    try {
        DB::transaction(function () {
            $this->employee->update(['monthly_rate' => 99999.00]);
            throw new \RuntimeException('Simulated processing failure');
        });
    } catch (\RuntimeException) {
        // Handled
    }

    $this->employee->refresh();
    expect($this->employee->monthly_rate)->toBe($initialSalary);
});
```

---

## Common Pitfalls to Avoid

1. **Raw Database Dumps to Users**: Never display raw SQL error strings (`SQLSTATE[23000]`) to users; catch and format into friendly explanations.
2. **Double Logging**: Avoid logging an error and then re-throwing it to a handler that logs it a second time.
3. **Ignoring Unsaved Models in Transactions**: Ensure all mutations occur inside the `DB::transaction` closure.
4. **Hardcoded Error Codes**: Use descriptive exception classes (`InvalidCalculationException`, `SalaryGradeMismatchException`) rather than numeric status codes in service logic.
