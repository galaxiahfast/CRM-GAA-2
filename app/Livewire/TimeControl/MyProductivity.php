<?php

namespace App\Livewire\TimeControl;

use App\Models\TimeEntry;
use App\Services\Reports\ReportExportManager;
use App\Services\TimeControl\TimeReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyProductivity extends Component
{
    public string $from;

    public string $to;

    public function mount(): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
        $latestActivityDate = TimeEntry::query()
            ->where('user_id', auth()->id())
            ->max('entry_date');
        $defaultDate = $latestActivityDate
            ? Carbon::parse($latestActivityDate, $this->moduleTimezone())->toDateString()
            : $this->localToday();

        $this->from = $defaultDate;
        $this->to = $defaultDate;
    }

    /**
     * Aplica los filtros de fecha y actualiza los datos
     */
    public function applyFilters(): void
    {
        // Este método solo dispara la actualización de los datos
        // Los datos se actualizan automáticamente en render()
        // porque usa $this->from y $this->to
    }

    public function export(string $format, TimeReportService $reports, ReportExportManager $exporter): StreamedResponse
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);

        $report = $reports->userReport(auth()->user(), $this->from, $this->to);

        return $exporter->download($format, $report);
    }

    public function render(TimeReportService $reports, ReportExportManager $exporter)
    {
        $data = $reports->userProductivity(auth()->id(), $this->from, $this->to);
        $activityDetail = $reports->activityDetailByDay($data['entries']);

        return view('livewire.time-control.my-productivity', [
            'entries' => $data['entries'],
            'totalSeconds' => $data['totalSeconds'],
            'byCustomer' => $data['byCustomer'],
            'byActivity' => $data['byActivity'],
            'activityDetail' => $activityDetail,
            'exportFormats' => $exporter->formats(),
        ])->layout('layouts.app');
    }

    private function localToday(): string
    {
        return Carbon::now($this->moduleTimezone())->toDateString();
    }

    private function moduleTimezone(): string
    {
        $timezone = (string) config('app.timezone', 'America/Mexico_City');

        return $timezone === 'UTC' ? 'America/Mexico_City' : $timezone;
    }
}
