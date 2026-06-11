<div class="max-w-5xl mx-auto space-y-6">
    <div class="flex items-center justify-between">
        <h1 class="text-2xl font-semibold text-gray-800">Perfiles organizacionales</h1>
        <a href="{{ route('time.admin.dashboard') }}" class="text-sm text-blue-700 underline">Supervisión</a>
    </div>

    @if (session('success'))
        <div class="bg-green-100 text-green-700 px-4 py-2 rounded">{{ session('success') }}</div>
    @endif

    <div class="bg-white rounded shadow p-6">
        <h2 class="font-semibold text-gray-800 mb-3">Asignar / cambiar puesto y área</h2>
        <form wire:submit="assign" class="grid sm:grid-cols-4 gap-4 items-end">
            <div>
                <label class="block text-sm text-gray-700 mb-1">Usuario</label>
                <select wire:model="userId" class="w-full border-gray-300 rounded">
                    <option value="">— Selecciona —</option>
                    @foreach ($users as $u)
                        <option value="{{ $u->id }}">{{ trim($u->name.' '.$u->last_name) }}</option>
                    @endforeach
                </select>
                @error('userId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Puesto</label>
                <select wire:model="jobPositionId" class="w-full border-gray-300 rounded">
                    <option value="">— Selecciona —</option>
                    @foreach ($jobPositions as $jp)
                        <option value="{{ $jp->id }}">{{ $jp->name }}</option>
                    @endforeach
                </select>
                @error('jobPositionId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <label class="block text-sm text-gray-700 mb-1">Área</label>
                <select wire:model="physicalAreaId" class="w-full border-gray-300 rounded">
                    <option value="">— Selecciona —</option>
                    @foreach ($physicalAreas as $pa)
                        <option value="{{ $pa->id }}">{{ $pa->name }}</option>
                    @endforeach
                </select>
                @error('physicalAreaId') <span class="text-xs text-red-600">{{ $message }}</span> @enderror
            </div>
            <div>
                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded w-full">Asignar</button>
            </div>
        </form>
    </div>

    <div class="bg-white rounded shadow p-4">
        <h2 class="font-semibold text-gray-800 mb-3">Perfil activo por usuario</h2>
        <table class="w-full text-sm">
            <thead>
                <tr class="text-left text-gray-500 border-b">
                    <th class="py-2">Usuario</th>
                    <th class="py-2">Rol</th>
                    <th class="py-2">Puesto actual</th>
                    <th class="py-2">Área actual</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($users as $u)
                    <tr class="border-b">
                        <td class="py-2">{{ trim($u->name.' '.$u->last_name) }}</td>
                        <td class="py-2">{{ $u->role->role ?? '—' }}</td>
                        <td class="py-2">{{ $u->activeOrganizationalProfile->jobPosition->name ?? '—' }}</td>
                        <td class="py-2">{{ $u->activeOrganizationalProfile->physicalArea->name ?? '—' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
