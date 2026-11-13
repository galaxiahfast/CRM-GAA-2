<?php

namespace App\Livewire\TimeControl;

use App\Services\ManagerControl\HoursSummary;
use App\Services\ManagerControl\TotalHoursPdf;
use App\Services\TimeControl\TimeReportService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Gate;
use Livewire\Attributes\Locked;
use Livewire\Component;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MyProductivity extends Component
{
    public string $from;

    public string $to;

    // Periodo propio del detalle por día: los botones de arriba no lo afectan.
    public string $detailFrom;

    public string $detailTo;

    /** Se muestra dentro de la página Cronómetro y productividad: sin título ni alto mínimo propios. */
    #[Locked]
    public bool $embedded = false;

    public function mount(bool $embedded = false): void
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
        $this->embedded = $embedded;

        // Periodo inicial de 15 días: hoy y los 14 días anteriores (los dos extremos cuentan).
        $to = $this->localToday();
        $from = $this->defaultFrom();

        $this->from = $from;
        $this->to = $to;
        $this->detailFrom = $from;
        $this->detailTo = $to;
    }

    /** Una fecha inválida (por ejemplo manipulada desde el navegador) vuelve al valor inicial del periodo de 15 días. */
    public function updatedFrom(): void
    {
        $this->from = $this->validDate($this->from, $this->defaultFrom());
    }

    public function updatedTo(): void
    {
        $this->to = $this->validDate($this->to, $this->localToday());
    }

    public function updatedDetailFrom(): void
    {
        $this->detailFrom = $this->validDate($this->detailFrom, $this->defaultFrom());
    }

    public function updatedDetailTo(): void
    {
        $this->detailTo = $this->validDate($this->detailTo, $this->localToday());
    }

    /**
     * Aplica los filtros de fecha y actualiza los datos
     */
    public function applyFilters(): void
    {
        // Si Desde quedó después de Hasta, se intercambian para que el encabezado y los datos coincidan.
        if ($this->from > $this->to) {
            [$this->from, $this->to] = [$this->to, $this->from];
        }

        // Este método solo dispara la actualización de los datos
        // Los datos se actualizan automáticamente en render()
        // porque usa $this->from y $this->to
    }

    /**
     * Aplica el periodo del detalle por día (independiente del periodo de arriba).
     */
    public function applyDetailFilters(): void
    {
        if ($this->detailFrom > $this->detailTo) {
            [$this->detailFrom, $this->detailTo] = [$this->detailTo, $this->detailFrom];
        }

        // Los datos se actualizan en render() con $detailFrom y $detailTo.
    }

    public function exportDetail(string $format, TimeReportService $reports): StreamedResponse
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
        abort_unless($format === 'pdf', 404);

        return $this->downloadHoursPdf($reports, $this->detailFrom, $this->detailTo);
    }

    public function export(string $format, TimeReportService $reports): StreamedResponse
    {
        abort_unless(Gate::allows('operate-time-tracking'), 403);
        abort_unless($format === 'pdf', 404);

        return $this->downloadHoursPdf($reports, $this->from, $this->to);
    }

    /**
     * Los dos reportes (productividad del periodo y detalle por día) usan el mismo formato del PDF de
     * «Registro de horas por día»: todo agrupado por día, sin descripciones, con subtotal diario y total.
     */
    private function downloadHoursPdf(TimeReportService $reports, string $from, string $to): StreamedResponse
    {
        $user = auth()->user();
        $entries = $reports->userProductivity($user->id, $from, $to)['entries'];

        $rows = $entries->map(fn ($entry) => [
            'date' => $entry->entry_date->format('Y-m-d'),
            'customer' => (string) ($entry->customer->name ?? '—'),
            'activity' => (string) ($entry->subService->sub_service ?? '—'),
            'description' => '',
            'seconds' => (int) $entry->total_duration_seconds,
        ])->values()->all();

        $department = $user->activeOrganizationalProfile()->with('physicalArea')->first()?->physicalArea?->name;
        $content = app(TotalHoursPdf::class)->renderOriginal(
            ['id' => (int) $user->id, 'name' => trim($user->name.' '.($user->last_name ?? ''))],
            $department,
            $from,
            $to,
            HoursSummary::fromRows($rows),
        );

        return response()->streamDownload(fn () => print ($content), 'mi-productividad_'.$from.'_'.$to.'.pdf', ['Content-Type' => 'application/pdf']);
    }

    public function render(TimeReportService $reports)
    {
        $data = $reports->userProductivity(auth()->id(), $this->from, $this->to);
        $detail = $reports->userProductivity(auth()->id(), $this->detailFrom, $this->detailTo);
        $activityDetail = $reports->activityDetailByDay($detail['entries']);

        return view('livewire.time-control.my-productivity', [
            'entries' => $data['entries'],
            'totalSeconds' => $data['totalSeconds'],
            'byCustomer' => $data['byCustomer'],
            'byActivity' => $data['byActivity'],
            'activityDetail' => $activityDetail,
            'detailEntries' => $detail['entries'],
        ])->layout('layouts.app');
    }

    private function defaultFrom(): string
    {
        return Carbon::parse($this->localToday(), $this->moduleTimezone())->subDays(14)->toDateString();
    }

    private function validDate(string $value, string $fallback): string
    {
        return Carbon::hasFormat($value, 'Y-m-d') ? $value : $fallback;
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
