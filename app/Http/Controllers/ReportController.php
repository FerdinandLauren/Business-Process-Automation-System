<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\RequestType;
use App\Services\ReportService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function index(Request $request, ReportService $reports): View
    {
        $filters = $this->filters($request, $reports);

        return view('reports.index', [
            'filters' => $filters,
            'catalogue' => ReportService::REPORTS,
            'payload' => $reports->payload($filters['report'], $filters),
            'statusOptions' => $reports->statusOptions($filters['report']),
            'departments' => Department::query()->orderBy('name')->get(['id', 'name']),
            'types' => RequestType::query()->orderBy('name')->get(['id', 'name', 'code']),
            'exportUrl' => route('reports.export', array_filter(
                $filters,
                fn ($value) => $value !== null && $value !== '',
            )),
        ]);
    }

    public function export(Request $request, ReportService $reports): StreamedResponse
    {
        $filters = $this->filters($request, $reports);
        $header = $reports->csvHeader($filters['report']);
        $rows = $reports->csvRows($filters['report'], $filters);

        $filename = sprintf(
            'arunika-%s-%s-to-%s.csv',
            $filters['report'],
            $filters['from'],
            $filters['to'],
        );

        return response()->streamDownload(function () use ($header, $rows) {
            $handle = fopen('php://output', 'w');

            // UTF-8 BOM so Excel opens accented names and en dashes correctly.
            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, $header);

            foreach ($rows as $row) {
                fputcsv($handle, array_map($this->sanitise(...), $row));
            }

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function filters(Request $request, ReportService $reports): array
    {
        $data = $request->validate([
            'report' => ['nullable', 'string', Rule::in(array_keys(ReportService::REPORTS))],
            'from' => ['nullable', 'date_format:Y-m-d'],
            'to' => ['nullable', 'date_format:Y-m-d'],
            'department' => ['nullable', 'integer', 'exists:departments,id'],
            'type' => ['nullable', 'string', 'max:64'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        if (($data['from'] ?? null) !== null && ($data['to'] ?? null) !== null && $data['to'] < $data['from']) {
            throw ValidationException::withMessages([
                'to' => 'The end date must be on or after the start date.',
            ]);
        }

        return $reports->filters($data);
    }

    /**
     * Neutralise spreadsheet formula injection without altering ordinary values.
     */
    private function sanitise(mixed $value): string
    {
        $value = (string) $value;

        return preg_match('/^[=+\-@\t\r]/', $value) === 1 ? "'".$value : $value;
    }
}
