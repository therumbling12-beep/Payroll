<?php

declare(strict_types=1);

namespace App\Services\Claims;

use App\Models\ClaimCategory;
use App\Models\Employee;
use Illuminate\Support\Str;

class ClaimCategoryManagementService
{
    /**
     * Create a new claim category with sanitized parameters
     *
     * @param array<string, mixed> $data
     */
    public function createCategory(array $data): ClaimCategory
    {
        $code = ! empty($data['code'])
            ? strtoupper(trim((string) $data['code']))
            : $this->generateNextCode($data['type'] ?? 'reimbursement');

        $taxClassification = $data['tax_classification'] ?? 'non_taxable';
        $deMinimisCap = $taxClassification === 'de_minimis'
            ? (float) ($data['de_minimis_annual_cap'] ?? ClaimTaxabilityService::DEFAULT_MEDICAL_DE_MINIMIS_CAP)
            : null;

        return ClaimCategory::create([
            'name' => trim((string) $data['name']),
            'code' => $code,
            'type' => $data['type'] ?? 'reimbursement',
            'tax_classification' => $taxClassification,
            'color_tag' => $data['color_tag'] ?? 'blue',
            'max_amount' => ! empty($data['max_amount']) ? (float) $data['max_amount'] : null,
            'de_minimis_annual_cap' => $deMinimisCap,
            'is_active' => (bool) ($data['is_active'] ?? true),
            'requires_receipt' => (bool) ($data['requires_receipt'] ?? true),
            'applicable_to' => $data['applicable_to'] ?? 'all',
            'spending_limit_period' => $data['spending_limit_period'] ?? 'per_claim',
            'description' => ! empty($data['description']) ? trim((string) $data['description']) : null,
        ]);
    }

    /**
     * Update an existing claim category
     *
     * @param array<string, mixed> $data
     */
    public function updateCategory(ClaimCategory $category, array $data): ClaimCategory
    {
        $taxClassification = $data['tax_classification'] ?? $category->tax_classification;
        $deMinimisCap = $taxClassification === 'de_minimis'
            ? (float) ($data['de_minimis_annual_cap'] ?? ($category->de_minimis_annual_cap ?: ClaimTaxabilityService::DEFAULT_MEDICAL_DE_MINIMIS_CAP))
            : null;

        $updates = [
            'name' => isset($data['name']) ? trim((string) $data['name']) : $category->name,
            'type' => $data['type'] ?? $category->type,
            'tax_classification' => $taxClassification,
            'color_tag' => $data['color_tag'] ?? $category->color_tag,
            'max_amount' => array_key_exists('max_amount', $data)
                ? (! empty($data['max_amount']) ? (float) $data['max_amount'] : null)
                : $category->max_amount,
            'de_minimis_annual_cap' => $deMinimisCap,
            'is_active' => array_key_exists('is_active', $data) ? (bool) $data['is_active'] : $category->is_active,
            'requires_receipt' => array_key_exists('requires_receipt', $data) ? (bool) $data['requires_receipt'] : $category->requires_receipt,
            'applicable_to' => $data['applicable_to'] ?? $category->applicable_to,
            'spending_limit_period' => $data['spending_limit_period'] ?? $category->spending_limit_period,
            'description' => array_key_exists('description', $data)
                ? (! empty($data['description']) ? trim((string) $data['description']) : null)
                : $category->description,
        ];

        if (! empty($data['code'])) {
            $updates['code'] = strtoupper(trim((string) $data['code']));
        }

        $category->update($updates);

        return $category;
    }

    /**
     * Toggle the active status of a category
     */
    public function toggleCategoryStatus(ClaimCategory $category): bool
    {
        $newStatus = ! $category->is_active;
        $category->update(['is_active' => $newStatus]);

        return $newStatus;
    }

    /**
     * Verify if a category is eligible to be claimed by a specific employee
     */
    public function isCategoryApplicableToEmployee(ClaimCategory $category, Employee $employee): bool
    {
        $applicableTo = strtolower((string) $category->applicable_to);

        if ($applicableTo === 'all') {
            return true;
        }

        $isDriver = str_contains(strtolower($employee->position ?? ''), 'driver')
            || str_contains(strtolower($employee->position ?? ''), 'chauffeur')
            || str_contains(strtolower($employee->department?->name ?? ''), 'fleet');

        if ($applicableTo === 'driver') {
            return $isDriver;
        }

        if (in_array($applicableTo, ['regular', 'staff', 'office'], true)) {
            return ! $isDriver;
        }

        return true;
    }

    /**
     * Auto-generate a unique category code
     */
    public function generateNextCode(string $type): string
    {
        $prefix = match (strtolower($type)) {
            'incentive' => 'CAT-INC',
            'maternity' => 'CAT-MAT',
            default => 'CAT-EXP',
        };

        $count = ClaimCategory::where('code', 'like', "{$prefix}%")->count() + 1;

        return sprintf('%s-%03d', $prefix, $count);
    }

    /**
     * Compute summary statistics for category management dashboard
     *
     * @return array{
     *     total_categories: int,
     *     active_categories: int,
     *     non_taxable_count: int,
     *     de_minimis_count: int,
     *     taxable_count: int,
     *     driver_only_count: int,
     *     staff_only_count: int
     * }
     */
    public function getSummaryStats(): array
    {
        $all = ClaimCategory::all();

        return [
            'total_categories' => $all->count(),
            'active_categories' => $all->where('is_active', true)->count(),
            'non_taxable_count' => $all->where('tax_classification', 'non_taxable')->count(),
            'de_minimis_count' => $all->where('tax_classification', 'de_minimis')->count(),
            'taxable_count' => $all->where('tax_classification', 'taxable')->count(),
            'driver_only_count' => $all->where('applicable_to', 'driver')->count(),
            'staff_only_count' => $all->whereIn('applicable_to', ['regular', 'staff'])->count(),
        ];
    }
}
