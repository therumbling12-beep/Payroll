<?php

declare(strict_types=1);

namespace App\Services\Compensation;

use App\Models\PayrollAuditTrail;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AuditTrailExportService
{
    /**
     * Stream CSV Export of Compliance and Compensation Audit Logs (Section 2.16)
     *
     * @param array<string, mixed> $filters
     */
    public function streamAuditTrailCsv(array $filters = []): StreamedResponse
    {
        $filename = 'TripWise_Compensation_Audit_Trail_' . now()->format('Ymd_His') . '.csv';

        $headers = [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
            'Pragma' => 'no-cache',
            'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
            'Expires' => '0',
        ];

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            if ($handle === false) {
                return;
            }

            // UTF-8 BOM for Excel compatibility
            fputs($handle, "\xEF\xBB\xBF");

            // Standard Header Row
            fputcsv($handle, [
                'Log ID',
                'Timestamp',
                'Action Code',
                'Actor / User',
                'Model / Entity',
                'Record ID',
                'IP Address',
                'Old Values JSON',
                'New Values JSON',
            ]);

            $query = PayrollAuditTrail::query()->latest();

            if (! empty($filters['action'])) {
                $query->where('action', $filters['action']);
            }

            if (! empty($filters['user_name'])) {
                $query->where('user_name', 'like', '%' . $filters['user_name'] . '%');
            }

            if (! empty($filters['start_date'])) {
                $query->whereDate('created_at', '>=', $filters['start_date']);
            }

            if (! empty($filters['end_date'])) {
                $query->whereDate('created_at', '<=', $filters['end_date']);
            }

            foreach ($query->lazy(200) as $log) {
                fputcsv($handle, [
                    $log->id,
                    $log->created_at ? $log->created_at->format('Y-m-d H:i:s') : '',
                    $log->action,
                    $log->user_name ?? 'System',
                    class_basename($log->model_type ?? 'Entity'),
                    $log->model_id,
                    $log->ip_address ?? '127.0.0.1',
                    is_array($log->old_values) ? json_encode($log->old_values) : ($log->old_values ?? ''),
                    is_array($log->new_values) ? json_encode($log->new_values) : ($log->new_values ?? ''),
                ]);
            }

            fclose($handle);
        }, $filename, $headers);
    }
}
