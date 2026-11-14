@props(['prefix', 'from', 'to', 'consult', 'guide', 'guideTitle', 'tourSet' => null])

{{-- Periodo de consulta del Reloj Checador: mismo panel que Cronómetro, Manager Control y Gráficos generales.
     Cada cuadro tiene el suyo (fechas, botón, guía y tutorial independientes). Las fechas viven en inputs ocultos
     con id "{prefix}_inicio" y "{prefix}_fin", que son los que lee el JavaScript de la página. --}}
<aside class="ac-aside">
    <div class="panel-surface panel-border box-border h-[87px] shrink-0 border-b p-[20px]">
        <h2 class="panel-accent leading-none">Periodo de consulta</h2>
        <p class="prod-lines mt-[5px]">Seleccione periodo de fechas</p>
    </div>
    <div class="ac-form">
        <div id="ac-{{ $prefix }}-period" class="ac-dates">
            @foreach (["{$prefix}_inicio" => ['Desde', $from], "{$prefix}_fin" => ['Hasta', $to]] as $field => [$label, $value])
                <div x-data="productivityDatePicker(@js($value))" @keydown.escape.window="open = false" @click.window="if (open && !$el.contains($event.target) && !$event.target.closest('[data-prod-calendar]')) open = false">
                    <span class="ac-label" id="{{ $field }}_label">{{ $label }}</span>
                    <input type="hidden" id="{{ $field }}" value="{{ $value }}" :value="value">
                    <button type="button" x-ref="trigger" @click="toggle()" :aria-expanded="open" aria-haspopup="dialog" aria-labelledby="{{ $field }}_label" class="ac-date-trigger panel-field w-full rounded-xl border px-[20px] py-[12px] focus:outline-none focus:ring-0">
                        <span x-html="formattedHtml" class="whitespace-nowrap">{!! preg_replace('/\d/', '<span class="prod-dg">$0</span>', e(\Illuminate\Support\Carbon::parse($value)->format('d/m/y'))) !!}</span>
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"/><path d="M7 3v4M17 3v4M3 10h18"/></svg>
                    </button>
                    <template x-teleport="body">
                        <div x-cloak data-panel data-productivity data-prod-calendar :style="`left:${left}px;top:${top}px`" :class="[open ? '' : 'hidden', quickTourStep > 0 ? 'prod-calendar-over-tour' : '']" class="app-zoom-overlay prod-calendar panel-surface panel-border panel-text panel-scroll fixed rounded-xl border p-[20px] text-[15px]" role="dialog" aria-label="Elegir fecha {{ $label }}">
                            <div class="mb-[15px] flex items-center justify-between gap-[10px]">
                                <strong class="panel-accent font-medium" x-text="monthLabel"></strong>
                                <div class="flex gap-[5px]">
                                    <button type="button" @click="changeMonth(-1)" aria-label="Mes anterior" class="panel-border hover:bg-[color:var(--panel-hover)] h-9 w-9 rounded-lg border">‹</button>
                                    <button type="button" @click="changeMonth(1)" aria-label="Mes siguiente" class="panel-border hover:bg-[color:var(--panel-hover)] h-9 w-9 rounded-lg border">›</button>
                                </div>
                            </div>
                            <div class="mb-[5px] grid grid-cols-7 text-center text-xs panel-muted"><span>Lu</span><span>Ma</span><span>Mi</span><span>Ju</span><span>Vi</span><span>Sá</span><span>Do</span></div>
                            <div class="grid grid-cols-7 gap-[2px]">
                                <template x-for="day in days" :key="day.key">
                                    <button type="button" @click="selectDate(day.date)" class="prod-calendar-day text-sm" :class="{ 'is-outside': day.outside, 'is-today': isToday(day.date), 'is-selected': value === day.key }" :aria-label="day.date.toLocaleDateString('es-MX', { dateStyle: 'full' })" x-text="day.date.getDate()"></button>
                                </template>
                            </div>
                        </div>
                    </template>
                </div>
            @endforeach
        </div>
        <div id="ac-{{ $prefix }}-consult" class="ac-consult">
            <span class="ac-label">Consulta</span>
            <button id="btn_{{ $prefix }}" type="button" onclick="{{ $consult }}()" class="prod-primary w-full focus:outline-none focus:ring-0">
                <svg id="carga_{{ $prefix }}" class="hidden h-5 w-5 animate-spin" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                    <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                    <path class="opacity-90" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                </svg>
                <span id="texto_{{ $prefix }}">Consultar periodo</span>
            </button>
        </div>
        <div class="panel-border border-t" aria-hidden="true"></div>
        <div class="ac-guide">
            <a href="{{ route('section.guide', $guide) }}" title="{{ $guideTitle }}" class="prod-link leading-none">Descargar guía PDF</a>
            <div class="panel-border border-t" aria-hidden="true"></div>
            <button type="button" @click="startQuickTour({{ $tourSet ? "'".$tourSet."'" : '' }})" class="prod-primary w-full focus:outline-none focus:ring-0">Tutorial rápido</button>
        </div>
    </div>
</aside>
