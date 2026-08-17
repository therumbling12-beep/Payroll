<?php

declare(strict_types=1);

use App\Models\BudgetRequisition;
use App\Models\Department;
use App\Models\Employee;
use App\Models\HmoEnrollment;
use Database\Seeders\HmoPlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed(HmoPlanSeeder::class);

    $this->dept = Department::create(['name' => 'Logistics Operations']);

    $this->emp = Employee::create([
        'employee_code' => 'EMP-FIN-01',
        'first_name' => 'Danilo',
        'last_name' => 'Aquino',
        'email' => 'danilo.aquino@tripwise.com',
        'department_id' => $this->dept->id,
        'position' => 'Logistics Officer',
        'employment_status' => 'regular',
        'monthly_rate' => 35000.00,
        'hire_date' => now()->subYears(2),
    ]);

    HmoEnrollment::create([
        'employee_id' => $this->emp->id,
        'hmo_card_number' => 'MED-TCE-1001',
        'hmo_provider' => 'Maxicare',
        'provider_plan' => 'Corporate Care',
        'coverage_tier' => 'Basic',
        'annual_limit' => 100000.00,
        'mbl_amount' => 100000.00,
        'monthly_premium' => 1500.00,
        'dependent_count' => 0,
        'coverage_start_date' => now()->toDateString(),
        'coverage_end_date' => now()->addYear()->toDateString(),
        'status' => 'active',
    ]);
});

test('GET /hmo-benefits/cost-tracking renders TCE tab with zero emojis', function () {
    $response = $this->get(route('hmo.cost-tracking'));

    $response->assertOk()
        ->assertSee('Benefits Cost')
        ->assertSee('Corporate Budget Hub')
        ->assertSee('Total Cost of Employment (TCE)')
        ->assertSee('Corporate Budget Requisitions')
        ->assertSee('PHP 35,000.00')
        ->assertSee('Danilo Aquino');
});

test('GET /hmo-benefits/cost-tracking?tab=budget renders Budget Requisitions tab', function () {
    BudgetRequisition::create([
        'requisition_code' => 'REQ-2026-901',
        'category' => 'HMO Healthcare Coverage',
        'amount' => 250000.00,
        'justification' => 'Q3 Enterprise Healthcare Premium allocation for corporate staff.',
        'status' => 'awaiting_approval',
    ]);

    $response = $this->get(route('hmo.cost-tracking', ['tab' => 'budget']));

    $response->assertOk()
        ->assertSee('REQ-2026-901')
        ->assertSee('HMO Healthcare Coverage')
        ->assertSee('PHP 250,000.00')
        ->assertSee('awaiting approval');
});

test('POST /hmo-benefits/budget-requests submits new requisition and redirects to cost-tracking?tab=budget', function () {
    $response = $this->post(route('hmo.submit-request'), [
        'category' => 'Corporate Wellness Programs',
        'amount' => 75000.00,
        'justification' => 'Annual Physical Exam batch booking for regional staff.',
    ]);

    $response->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']))
        ->assertSessionHas('status');

    $this->assertDatabaseHas('budget_requisitions', [
        'category' => 'Corporate Wellness Programs',
        'amount' => 75000.00,
        'status' => 'awaiting_approval',
    ]);
});

test('POST /hmo-benefits/budget-requests/{requisition}/status updates lifecycle status', function () {
    $req = BudgetRequisition::create([
        'requisition_code' => 'REQ-2026-902',
        'category' => 'Group Life Policies',
        'amount' => 120000.00,
        'justification' => 'Corporate Life Coverage annual premium.',
        'status' => 'awaiting_approval',
    ]);

    $approveResp = $this->post(route('hmo.update-budget-status', $req), [
        'status' => 'approved',
    ]);

    $approveResp->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']))
        ->assertSessionHas('status');

    $req->refresh();
    expect($req->status)->toBe('approved');

    $releaseResp = $this->post(route('hmo.update-budget-status', $req), [
        'status' => 'released',
    ]);

    $releaseResp->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']))
        ->assertSessionHas('status');

    $req->refresh();
    expect($req->status)->toBe('released');
});

test('GET /hmo-benefits/budget-requests redirects seamlessly to cost-tracking?tab=budget', function () {
    $response = $this->get(route('hmo.budget-requests'));

    $response->assertRedirect(route('hmo.cost-tracking', ['tab' => 'budget']));
});

test('Phase 2: GET /hmo-benefits/cost-tracking/export-tce streams CSV master export', function () {
    $response = $this->get(route('hmo.cost-tracking.export-tce'));

    $response->assertOk()
        ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
});

test('Phase 2: GET /hmo-benefits/cost-tracking renders Department Summary cards and Budget Tracker', function () {
    BudgetRequisition::create([
        'requisition_code' => 'REQ-2026-TCE-01',
        'category' => 'HMO Healthcare Coverage',
        'amount' => 500000.00,
        'justification' => 'Annual Healthcare Allocation',
        'status' => 'approved',
    ]);

    // Check Tab 1 for Department Summary & Download button
    $tceResponse = $this->get(route('hmo.cost-tracking', ['tab' => 'tce']));
    $tceResponse->assertOk()
        ->assertSee('Logistics Operations')
        ->assertSee('Dept Total')
        ->assertSee('Download Cost Report (CSV)');

    // Check Tab 2 for Budget Tracker
    $budgetResponse = $this->get(route('hmo.cost-tracking', ['tab' => 'budget']));
    $budgetResponse->assertOk()
        ->assertSee('Finance Health Budget Tracker')
        ->assertSee('Total Health Budget Given vs. Money Spent')
        ->assertSee('PHP 500,000.00');
});
