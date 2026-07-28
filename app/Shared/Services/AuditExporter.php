<?php

namespace App\Shared\Services;

use App\Shared\Models\AuditLog;
use Illuminate\Support\Facades\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AuditExporter
{
    /**
     * @param  array{from?: string, to?: string, action?: string}  $filters
     */
    public function csv(array $filters = []): StreamedResponse
    {
        $query = AuditLog::query()->orderByDesc('id');

        if (! empty($filters['from'])) {
            $query->where('created_at', '>=', $filters['from']);
        }
        if (! empty($filters['to'])) {
            $query->where('created_at', '<=', $filters['to']);
        }
        if (! empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }

        $filename = 'audit-export-'.now()->format('YmdHis').'.csv';

        return Response::streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'user_id', 'action', 'auditable_type', 'auditable_id', 'ip', 'created_at', 'old_values', 'new_values']);
            $query->chunk(200, function ($rows) use ($out): void {
                foreach ($rows as $row) {
                    fputcsv($out, [
                        $row->id,
                        $row->user_id,
                        $row->action,
                        $row->auditable_type,
                        $row->auditable_id,
                        $row->ip,
                        optional($row->created_at)?->toDateTimeString(),
                        json_encode($row->old_values),
                        json_encode($row->new_values),
                    ]);
                }
            });
            fclose($out);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }
}
