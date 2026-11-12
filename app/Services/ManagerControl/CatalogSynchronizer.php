<?php

namespace App\Services\ManagerControl;

use App\Models\Customer;
use App\Models\Service;
use App\Models\SubService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/** Importación incremental: reutiliza coincidencias seguras y nunca modifica catálogos originales. */
class CatalogSynchronizer
{
    public function __construct(
        private readonly ManagerControlRepository $repository,
        private readonly CatalogInsertConnection $writer,
    ) {}

    /** @return array{counts: array<string, array<string, int>>, issues: list<array{kind: string, crm_id: int, reason: string}>} */
    public function run(bool $apply = false): array
    {
        $companyId = (int) config('managercontrol.company_id');
        $exerciseId = (int) config('managercontrol.exercise_id');
        if ($companyId < 1 || $exerciseId < 1) {
            throw new ManagerControlException('Configura empresa y ejercicio antes de importar catálogos.');
        }
        if ($apply) {
            $this->writer->assertReady(); // Verifica los tres permisos antes de la primera escritura.
        }

        $links = DB::table('managercontrol_catalog_links')
            ->where('company_id', $companyId)->where('exercise_id', $exerciseId)
            ->get()->keyBy(fn (object $row): string => $row->kind.':'.$row->crm_id);
        $counts = array_fill_keys(['customers', 'sections', 'activities'],
            ['linked' => 0, 'matched' => 0, 'created' => 0, 'would_create' => 0, 'pending_section' => 0, 'conflict' => 0]);
        $issues = [];

        foreach (['customers', 'sections', 'activities'] as $kind) {
            $this->repository->forgetCatalog($kind);
        }
        $remoteCustomers = $this->repository->catalog('customers');
        $remoteSections = $this->repository->catalog('sections');
        $remoteActivities = $this->repository->catalog('activities');
        foreach (['clientes' => $remoteCustomers, 'cuentas' => $remoteSections, 'actividades' => $remoteActivities] as $label => $rows) {
            if (count($rows) >= ManagerControlRepository::CATALOG_LIMIT) {
                throw new ManagerControlException("La consulta de {$label} alcanzó el límite de ".ManagerControlRepository::CATALOG_LIMIT.' filas; amplía el catálogo antes de sincronizar para evitar duplicados.');
            }
        }
        $departmentIds = $this->repository->departmentIds();

        foreach (Customer::query()->whereNull('deleted_at')->orderBy('id')->get() as $customer) {
            $id = (int) $customer->id;
            $name = trim(implode(' ', array_filter([$customer->name, $customer->last_name, $customer->maternal_last_name])));
            $rfc = $this->rfc((string) $customer->rfc);
            $mapped = $this->mapped($links, 'customers', $id, $remoteCustomers);
            if ($mapped === false) {
                $this->issue($counts, $issues, 'customers', $id, 'El vínculo guardado ya no existe en ManagerControl.');

                continue;
            }
            if (is_array($mapped)) {
                $counts['customers']['linked']++;

                continue;
            }
            if (! in_array(strlen($rfc), [12, 13], true) || mb_strlen($name) > 200 || $name === '') {
                $this->issue($counts, $issues, 'customers', $id, 'Nombre o RFC inválido para el catálogo remoto.');

                continue;
            }
            $byRfc = array_values(array_filter($remoteCustomers, fn (array $row): bool => $this->validRfc((string) ($row['rfc'] ?? '')) && $this->rfc($row['rfc']) === $rfc));
            $byName = array_values(array_filter($remoteCustomers, fn (array $row): bool => $this->normalize($row['name']) === $this->normalize($name)));
            if (count($byRfc) > 1 || ($byRfc === [] && count($byName) > 1)) {
                $this->issue($counts, $issues, 'customers', $id, 'Varias coincidencias remotas; requiere revisión.');

                continue;
            }
            $match = $byRfc[0] ?? $byName[0] ?? null;
            if ($match !== null && $byRfc === [] && $this->validRfc((string) ($match['rfc'] ?? '')) && $this->rfc($match['rfc']) !== $rfc) {
                $this->issue($counts, $issues, 'customers', $id, 'El nombre coincide, pero el RFC remoto es distinto.');

                continue;
            }
            if ($match !== null) {
                $counts['customers']['matched']++;
                $this->saveLink($apply, 'customers', $id, $match['id'], $match['source'] === 'crm' ? 'imported' : 'matched');

                continue;
            }
            $phone = trim((string) $customer->phone);
            $email = trim((string) $customer->email);
            $address = trim((string) $customer->address);
            if (mb_strlen($phone) > 50 || mb_strlen($email) > 50 || mb_strlen($address) > 200) {
                $this->issue($counts, $issues, 'customers', $id, 'Teléfono, correo o domicilio excede el tamaño permitido por ManagerControl.');

                continue;
            }
            if (! $apply) {
                $counts['customers']['would_create']++;

                continue;
            }
            $remoteId = $this->writer->insert('customers', [
                'Nombre' => $name, 'RFC' => $rfc, 'CURP' => '', 'TipoPersona' => strlen($rfc) === 12 ? 'MORAL' : 'FISICA',
                'Pais' => '.', 'Estado' => '.', 'Ciudad' => '.', 'Direccion' => $address !== '' ? $address : '.',
                'CP' => '', 'Telefonos' => $phone, 'Celular' => '', 'Fax' => '', 'Email' => $email, 'RepresentanteLegal' => '',
            ]);
            $this->saveLink(true, 'customers', $id, $remoteId, 'imported');
            $remoteCustomers[] = ['id' => $remoteId, 'name' => $name, 'rfc' => $rfc, 'source' => 'crm'];
            $counts['customers']['created']++;
        }

        $sectionIds = [];
        foreach (Service::query()->orderBy('id')->get() as $service) {
            $id = (int) $service->id;
            $name = trim((string) $service->service);
            $departmentId = (int) (config('managercontrol.catalog_departments')[$name] ?? 0);
            $mapped = $this->mapped($links, 'sections', $id, $remoteSections);
            if ($mapped === false) {
                $this->issue($counts, $issues, 'sections', $id, 'El vínculo guardado ya no existe en ManagerControl.');

                continue;
            }
            if (is_array($mapped)) {
                $sectionIds[$id] = $mapped['id'];
                $counts['sections']['linked']++;

                continue;
            }
            if ($departmentId < 1 || ! in_array($departmentId, $departmentIds, true) || $name === '' || mb_strlen($name) > 200) {
                $this->issue($counts, $issues, 'sections', $id, 'Falta un departamento válido o el nombre supera 200 caracteres.');

                continue;
            }
            $sameName = array_values(array_filter($remoteSections, fn (array $row): bool => $this->normalize($row['name']) === $this->normalize($name)));
            $matches = array_values(array_filter($sameName, fn (array $row): bool => (int) ($row['department_id'] ?? 0) === $departmentId));
            if (count($matches) > 1) {
                $this->issue($counts, $issues, 'sections', $id, 'La cuenta está duplicada dentro del mismo departamento.');

                continue;
            }
            if ($matches !== []) {
                $sectionIds[$id] = $matches[0]['id'];
                $counts['sections']['matched']++;
                $this->saveLink($apply, 'sections', $id, $matches[0]['id'], $matches[0]['source'] === 'crm' ? 'imported' : 'matched');

                continue;
            }
            if (! $apply) {
                $sectionIds[$id] = null;
                $counts['sections']['would_create']++;

                continue;
            }
            $remoteId = $this->writer->insert('sections', ['IDDepartamento' => $departmentId, 'Nombre' => $name, 'Descripcion' => trim((string) $service->description)]);
            $this->saveLink(true, 'sections', $id, $remoteId, 'imported');
            $remoteSections[] = ['id' => $remoteId, 'name' => $name, 'department_id' => $departmentId, 'source' => 'crm'];
            $sectionIds[$id] = $remoteId;
            $counts['sections']['created']++;
        }

        foreach (SubService::query()->orderBy('id')->get() as $activity) {
            $id = (int) $activity->id;
            $name = trim((string) $activity->sub_service);
            $mapped = $this->mapped($links, 'activities', $id, $remoteActivities);
            if ($mapped === false) {
                $this->issue($counts, $issues, 'activities', $id, 'El vínculo guardado ya no existe en ManagerControl.');

                continue;
            }
            if (is_array($mapped)) {
                $counts['activities']['linked']++;

                continue;
            }
            if (! array_key_exists((int) $activity->service_id, $sectionIds)) {
                $this->issue($counts, $issues, 'activities', $id, 'La cuenta de esta actividad necesita revisión.');

                continue;
            }
            $sectionId = $sectionIds[(int) $activity->service_id];
            if ($name === '' || mb_strlen($name) > 200) {
                $this->issue($counts, $issues, 'activities', $id, 'Nombre inválido para el catálogo remoto.');

                continue;
            }
            if ($sectionId === null) {
                $counts['activities']['pending_section']++;

                continue;
            }
            $matches = array_values(array_filter($remoteActivities, fn (array $row): bool => (int) ($row['section_id'] ?? 0) === $sectionId && $this->normalize($row['name']) === $this->normalize($name)));
            if (count($matches) > 1) {
                $this->issue($counts, $issues, 'activities', $id, 'La actividad está duplicada en la cuenta remota.');

                continue;
            }
            if ($matches !== []) {
                $counts['activities']['matched']++;
                $this->saveLink($apply, 'activities', $id, $matches[0]['id'], $matches[0]['source'] === 'crm' ? 'imported' : 'matched');

                continue;
            }
            if (! $apply) {
                $counts['activities']['would_create']++;

                continue;
            }
            $remoteId = $this->writer->insert('activities', ['IDSeccion' => $sectionId, 'Nombre' => $name, 'Descripcion' => trim((string) $activity->description), 'Concluido' => 0]);
            $this->saveLink(true, 'activities', $id, $remoteId, 'imported');
            $remoteActivities[] = ['id' => $remoteId, 'name' => $name, 'section_id' => $sectionId, 'source' => 'crm'];
            $counts['activities']['created']++;
        }

        if ($apply) {
            foreach (['customers', 'sections', 'activities'] as $kind) {
                $this->repository->forgetCatalog($kind);
            }
        }

        return compact('counts', 'issues');
    }

    /** @param \Illuminate\Support\Collection<string, object> $links @param list<array<string, mixed>> $remote */
    private function mapped($links, string $kind, int $crmId, array $remote): array|bool|null
    {
        $link = $links->get($kind.':'.$crmId);
        if ($link === null) {
            return null;
        }

        return collect($remote)->firstWhere('id', (int) $link->legacy_id) ?? false;
    }

    private function saveLink(bool $apply, string $kind, int $crmId, int $remoteId, string $origin): void
    {
        if (! $apply) {
            return;
        }
        DB::table('managercontrol_catalog_links')->updateOrInsert([
            'kind' => $kind, 'crm_id' => $crmId,
            'company_id' => (int) config('managercontrol.company_id'), 'exercise_id' => (int) config('managercontrol.exercise_id'),
        ], ['legacy_id' => $remoteId, 'origin' => $origin, 'created_at' => now(), 'updated_at' => now()]);
    }

    /** @param array<string, array<string, int>> $counts @param list<array{kind: string, crm_id: int, reason: string}> $issues */
    private function issue(array &$counts, array &$issues, string $kind, int $id, string $reason): void
    {
        $counts[$kind]['conflict']++;
        $issues[] = ['kind' => $kind, 'crm_id' => $id, 'reason' => $reason];
    }

    private function normalize(string $value): string
    {
        return preg_replace('/[^a-z0-9]/', '', mb_strtolower(Str::ascii($value))) ?? '';
    }

    private function rfc(string $value): string
    {
        return preg_replace('/[^A-Z0-9]/', '', strtoupper($value)) ?? '';
    }

    private function validRfc(string $value): bool
    {
        return in_array(strlen($this->rfc($value)), [12, 13], true);
    }
}
