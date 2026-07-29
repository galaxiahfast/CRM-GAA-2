@props(['columns' => [], 'groups' => []])

<div {{ $attributes->merge(['class' => 'space-y-6']) }}>
    @forelse ($groups as $group)
        <div>
            <h3 class="font-semibold text-gray-800 mb-2">{{ $group['date'] }}</h3>
            <div class="overflow-x-auto">
                <table class="w-full text-[15px]">
                    <thead>
                        <tr class="text-left text-gray-500 border-b">
                            @foreach ($columns as $column)
                                <th class="py-2 {{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'text-right' : '' }}">{{ $column }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($group['rows'] as $row)
                            <tr class="border-b">
                                @foreach ($row as $i => $cell)
                                    @php $column = $columns[$i] ?? ''; @endphp
                                    <td class="py-2 {{ in_array($column, ['Inicio', 'Fin', 'Tiempo efectivo'], true) ? 'text-right font-mono' : '' }}">{{ $cell }}</td>
                                @endforeach
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @empty
        <p class="text-gray-500 text-[15px]">Sin registros en el periodo.</p>
    @endforelse
</div>
