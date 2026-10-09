<section class="server">
    <table class="server-head"><tr><td><h2>{{ mb_strtoupper($server['name']) }}</h2></td><td>{{ $server['ip'] }}</td></tr></table>
    <table class="detail">
        <thead><tr><th class="profile">Perfil</th><th class="date">Última ejecución</th><th class="done">Hecho</th><th class="result">Resultado</th></tr></thead>
        @foreach ($server['profiles'] as $profile)
            @php $bad = $profile['failed'] || $profile['errors'] !== [] || $profile['issues'] !== []; @endphp
            <tbody><tr><td class="profile">{{ $profile['name'] }}</td><td class="date">{{ $profile['date'] ?: 'Sin fecha' }}</td><td class="done">{{ $profile['processed'] ?: '—' }}</td><td class="result {{ $bad ? 'error' : '' }}">{{ $bad ? 'Error' : ($profile['processed'] === '0/0' ? 'Sin cambios' : 'Todo realizado') }}</td></tr>
                @if ($bad)<tr><td colspan="4" class="incident"><strong>Pendientes / incidencias:</strong>@if (($profile['failedFiles'] ?? []) !== []) @foreach ($profile['failedFiles'] as $file)<div>- {{ $file['path'] }}</div>@endforeach @else @foreach ($profile['errors'] as $error)<div>- {{ $error }}</div>@endforeach @endif @foreach ($profile['issues'] as $issue)<div>- {{ $issue }}</div>@endforeach</td></tr>@endif
            </tbody>
        @endforeach
    </table>
</section>
