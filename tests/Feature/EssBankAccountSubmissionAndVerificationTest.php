<?php

declare(strict_types=1);

use App\Models\BankAccountSubmission;
use App\Models\Department;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->department = Department::firstOrCreate(['name' => 'Fleet Logistics', 'code' => 'FLT']);
    Storage::fake('public');
});

test('employee can submit security bank details with atm photo proof via ess', function () {
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-DRIVER-01',
        'first_name' => 'Danilo',
        'last_name' => 'Cruz',
        'email' => 'danilo.c@tripwise.com',
        'position' => 'Senior Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 28000.00,
    ]);

    $file = UploadedFile::fake()->image('security_bank_atm.jpg');

    $response = $this->post(route('ess.bank-account.submit'), [
        'employee_id' => $employee->id,
        'bank_name' => 'Security Bank Corporation',
        'account_number' => '0012-3456-7890',
        'proof_document' => $file,
    ]);

    $response->assertRedirect();

    $submission = BankAccountSubmission::where('employee_id', $employee->id)->first();

    expect($submission)->not->toBeNull()
        ->and($submission->status)->toBe('pending')
        ->and($submission->bank_name)->toBe('Security Bank Corporation')
        ->and($submission->account_number)->toBe('0012-3456-7890')
        ->and($submission->proof_attachment_path)->not->toBeNull();

    // Employee remains on cash until HR approves
    $employee->refresh();
    expect($employee->payment_mode)->toBe('cash');
});

test('hr can review and approve pending security bank submission automatically switching employee to bank', function () {
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-DRIVER-02',
        'first_name' => 'Nestor',
        'last_name' => 'Ramos',
        'email' => 'nestor.r@tripwise.com',
        'position' => 'Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 26000.00,
    ]);

    $submission = BankAccountSubmission::create([
        'employee_id' => $employee->id,
        'bank_name' => 'Security Bank Corporation',
        'account_number' => '0099-8877-6655',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->post(route('payroll.bank-verifications.approve', $submission->id));
    $response->assertRedirect();

    $submission->refresh();
    $employee->refresh();

    expect($submission->status)->toBe('approved')
        ->and($submission->reviewed_at)->not->toBeNull()
        ->and($employee->payment_mode)->toBe('bank')
        ->and($employee->bank_name)->toBe('Security Bank Corporation')
        ->and($employee->bank_account_number)->toBe('0099-8877-6655');
});

test('hr can reject invalid submission with reason leaving employee safely on cash', function () {
    $employee = Employee::create([
        'department_id' => $this->department->id,
        'employee_code' => 'EMP-DRIVER-03',
        'first_name' => 'Mario',
        'last_name' => 'Lopez',
        'email' => 'mario.l@tripwise.com',
        'position' => 'Driver',
        'payment_mode' => 'cash',
        'monthly_rate' => 25000.00,
    ]);

    $submission = BankAccountSubmission::create([
        'employee_id' => $employee->id,
        'bank_name' => 'Security Bank Corporation',
        'account_number' => '0011-2233-4455',
        'status' => 'pending',
    ]);

    $response = $this->actingAs($this->user)->post(route('payroll.bank-verifications.reject', $submission->id), [
        'rejection_reason' => 'ATM card photo is blurry. Please re-upload a clearer image.',
    ]);

    $response->assertRedirect();

    $submission->refresh();
    $employee->refresh();

    expect($submission->status)->toBe('rejected')
        ->and($submission->rejection_reason)->toContain('blurry')
        ->and($employee->payment_mode)->toBe('cash');
});
