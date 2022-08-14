<?php

namespace App\Reports;

use App\Contracts\AppReport;
use App\Enums\DashboardSortBy;
use App\Enums\SortDirection;
use App\Helpers\ReportHelper;
use Carbon\Carbon;
use Carbon\CarbonInterval;
use Exception;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class DashboardExport extends AppReport implements FromCollection, WithMapping, ShouldAutoSize, WithHeadings, WithStyles
{
    use Exportable;

    public function __construct(
        private readonly ?array $users,
        private readonly ?array $projects,
        private readonly Carbon $startAt,
        private readonly Carbon $endAt,
        private readonly DashboardSortBy|null $sortBy = null,
        private readonly SortDirection|null $sortDirection = null,
    ) {
    }

    public function collection(): Collection
    {
        $reportCollection = $this->queryReport()->map(static function ($el) {
            $start = Carbon::make($el->start_at);

            $el->duration = Carbon::make($el->end_at)?->diffInSeconds($start);
            $el->from_midnight = $start?->diffInSeconds($start?->copy()->startOfDay());

            return $el;
        })->groupBy('user_id');

        if ($this->sortBy && $this->sortDirection) {
            $sortBy = match ($this->sortBy) {
                DashboardSortBy::USER_NAME => 'user_name',
                DashboardSortBy::WORKED => 'duration',
            };
            $sortDirection = match ($this->sortDirection) {
                SortDirection::ASC => false,
                SortDirection::DESC => true,
            };

            if ($this->sortBy === DashboardSortBy::USER_NAME) {
                $reportCollection = $reportCollection->sortBy(
                    fn ($interval) => $interval[0][$sortBy],
                    SORT_NATURAL,
                    $sortDirection
                );
            } else {
                $reportCollection = $reportCollection->sortBy(
                    fn ($interval) => $interval->sum($sortBy),
                    SORT_NATURAL,
                    $sortDirection
                );
            }
        }

        return $reportCollection;
    }

    /**
     * @param $row
     * @return array
     * @throws Exception
     */
    public function map($row): array
    {
        return $row->groupBy('user_id')->map(
            static function ($collection) {
                $interval = CarbonInterval::seconds($collection->sum('duration'));

                return array_merge(
                    array_values($collection->first()->only(['user_name'])),
                    [
                        $interval->cascade()->forHumans(),
                        round($interval->totalHours, 3)
                    ]
                );
            }
        )->all();
    }

    private function queryReport(): Collection
    {
        return ReportHelper::getBaseQuery(
            $this->users,
            $this->startAt,
            $this->endAt,
            [
                'time_intervals.start_at',
                'time_intervals.activity_fill',
                'time_intervals.mouse_fill',
                'time_intervals.keyboard_fill',
                'time_intervals.end_at',
                'time_intervals.is_manual',
                'users.email as user_email',
            ]
        )->whereIn('project_id', $this->projects)->get();
    }

    public function headings(): array
    {
        return [
            'User Name',
            'Hours',
            'Hours (decimal)',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true], 'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER]],
        ];
    }

    public function getReportId(): string
    {
        return 'dashboard_report';
    }

    public function getLocalizedReportName(): string
    {
        return __('Dashboard_Report');
    }
}
