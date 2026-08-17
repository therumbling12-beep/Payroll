<?php

declare(strict_types=1);

namespace App\Services\Payroll;

use App\Models\SalaryComputation;
use Illuminate\Support\Collection;

class SecurityBankExportService
{
    /**
     * Generate Security Bank Corporation (SBC) Payroll Manager CSV content string.
     *
     * @param Collection<int, SalaryComputation> $computations
     */
    public function generateCsv(Collection $computations, string $cutoffPeriod): string
    {
        $handle = fopen('php://temp', 'r+');

        // Write Official SBC Header
        fputcsv($handle, [
            'SEQ_NO',
            'EMPLOYEE_ID',
            'ACCOUNT_NAME',
            'ACCOUNT_NUMBER',
            'AMOUNT',
            'REFERENCE_NUMBER',
            'REMARKS',
        ]);

        $datePart = str_replace(['-', '_'], '', substr($cutoffPeriod, 0, 10));
        if (strlen($datePart) < 8) {
            $datePart = date('Ymd');
        }
        $batchRef = "PR-{$datePart}-001";
        $seq = 1;

        foreach ($computations as $comp) {
            $emp = $comp->employee;
            if (! $emp || $emp->payment_mode === 'cash') {
                continue;
            }

            $fullName = strtoupper(trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')));
            $accountNo = $emp->bank_account_number ?: ($emp->bank_account_no ?: '0000000000');
            $cleanAccountNo = preg_replace('/[^0-9]/', '', $accountNo) ?: $accountNo;

            fputcsv($handle, [
                $seq++,
                $emp->employee_code,
                $fullName,
                $cleanAccountNo,
                number_format((float) $comp->net_pay, 2, '.', ''),
                $batchRef,
                "PAYROLL SALARY {$cutoffPeriod}",
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }

    /**
     * Generate Cash Voucher Register CSV for unbanked/cash-paid personnel.
     *
     * @param Collection<int, SalaryComputation> $computations
     */
    public function generateCashVoucherCsv(Collection $computations, string $cutoffPeriod): string
    {
        $handle = fopen('php://temp', 'r+');

        fputcsv($handle, [
            'VOUCHER_NO',
            'EMPLOYEE_CODE',
            'EMPLOYEE_NAME',
            'DEPARTMENT',
            'GROSS_PAY',
            'TOTAL_DEDUCTIONS',
            'NET_CASH_DISBURSEMENT',
            'PAYOUT_PERIOD',
            'CASHIER_VERIFICATION',
            'RECIPIENT_ACKNOWLEDGMENT',
        ]);

        $seq = 1;

        foreach ($computations as $comp) {
            $emp = $comp->employee;
            if (! $emp || $emp->payment_mode !== 'cash') {
                continue;
            }

            $fullName = strtoupper(trim(($emp->first_name ?? '') . ' ' . ($emp->last_name ?? '')));
            $voucherNo = sprintf('CV-%s-%04d', str_replace(['-', '_'], '', $cutoffPeriod), $seq);

            fputcsv($handle, [
                $voucherNo,
                $emp->employee_code,
                $fullName,
                $emp->department?->name ?? 'General',
                number_format((float) $comp->gross_pay, 2, '.', ''),
                number_format((float) $comp->total_deductions, 2, '.', ''),
                number_format((float) $comp->net_pay, 2, '.', ''),
                $cutoffPeriod,
                'VERIFIED BY CASHIER',
                'RECEIVED IN FULL',
            ]);
            $seq++;
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv !== false ? $csv : '';
    }
}
