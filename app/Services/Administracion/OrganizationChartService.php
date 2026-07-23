<?php

namespace App\Services\Administracion;

use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrganizationChartService
{
    /**
     * @return array{tree: array<int, array>, unassigned: array<int, array>, stats: array<string, int>}
     */
    public function buildChartData(?int $physicalAreaId = null): array
    {
        // 1. Construir el árbol completo sin filtro (solo usuarios con relaciones)
        $fullTree = $this->buildFullTree();

        if (empty($physicalAreaId)) {
            // Sin filtro, devolver el árbol completo y los usuarios sin asignar
            return $this->prepareResult($fullTree);
        }

        // 2. Aplicar filtro por área: conservar nodos que coinciden + sus ancestros
        $filteredTree = $this->filterTreeByArea($fullTree, $physicalAreaId);

        // 3. Recalcular estadísticas y usuarios sin asignar basados en el árbol filtrado
        return $this->prepareResult($filteredTree, $physicalAreaId);
    }

    /**
     * Construye el árbol jerárquico completo, incluyendo **solo** usuarios que tienen
     * al menos una relación (como superior o subordinado). Los usuarios aislados
     * (sin jefe ni subordinados) no se incluyen en el árbol principal.
     *
     * @return array<int, array>
     */
    private function buildFullTree(): array
    {
        // Obtener todas las relaciones jerárquicas
        $relations = UserHierarchyRelation::all(['subordinate_id', 'superior_id']);

        // Construir mapas de IDs de usuarios que participan en relaciones
        $userIdsInRelations = collect();
        foreach ($relations as $rel) {
            $userIdsInRelations->push($rel->subordinate_id);
            $userIdsInRelations->push($rel->superior_id);
        }
        $userIdsInRelations = $userIdsInRelations->unique()->toArray();

        // Mapear superiores y subordinados por usuario
        $superiorIdsByUser = [];
        $subordinateIdsByUser = [];

        foreach ($relations as $relation) {
            $superiorIdsByUser[$relation->subordinate_id][] = $relation->superior_id;
            $subordinateIdsByUser[$relation->superior_id][] = $relation->subordinate_id;
        }

        // Obtener **solo los usuarios que están en relaciones** (tienen al menos un superior o subordinado)
        $users = User::query()
            ->with([
                'role:id,role',
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
            ])
            ->whereIn('id', $userIdsInRelations)
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $userMap = [];
        $unassigned = [];

        foreach ($users as $user) {
            $profile = $user->activeOrganizationalProfile;
            $superiorIds = $superiorIdsByUser[$user->id] ?? [];
            $subordinateIds = $subordinateIdsByUser[$user->id] ?? [];
            $missing = $this->resolveMissingAssignments($user, $superiorIds, $profile);

            // Solo considerar que está "sin asignar" si no tiene superior y no tiene subordinados
            // (es decir, es un nodo aislado que no debería estar en el árbol)
            $hasSuperior = !empty($superiorIds);
            $hasSubordinate = !empty($subordinateIds);

            $node = [
                'id' => $user->id,
                'name' => trim("{$user->name} {$user->last_name}"),
                'email' => $user->email,
                'role' => $user->role?->role,
                'job_position' => $profile?->jobPosition?->name,
                'physical_area' => $profile?->physicalArea?->name,
                'physical_area_id' => $profile?->physical_area_id,
                'superior_count' => count($superiorIds),
                'subordinate_count' => count($subordinateIds),
                'children' => [],
            ];

            $userMap[$user->id] = $node;

            // Un usuario "sin asignar" es aquel que tiene al menos un campo faltante
            // y además no está conectado a la jerarquía (no tiene superior ni subordinado)
            if (!empty($missing) && !$hasSuperior && !$hasSubordinate) {
                $unassigned[] = array_merge($node, ['missing' => $missing]);
            }
        }

        // Determinar el padre primario de cada usuario (el superior más pequeño, o null si es raíz)
        $primaryParentByUser = [];
        foreach ($userMap as $userId => $node) {
            $superiorIds = $superiorIdsByUser[$userId] ?? [];
            $primaryParentByUser[$userId] = empty($superiorIds) ? null : min($superiorIds);
        }

        // Construir nodos raíz: aquellos que no tienen superior (raíz) y tienen al menos un subordinado
        $roots = [];
        $placed = [];

        foreach ($userMap as $userId => $node) {
            // Solo son raíz si no tienen superior y tienen al menos un subordinado
            if ($primaryParentByUser[$userId] !== null) {
                continue;
            }
            if (empty($subordinateIdsByUser[$userId] ?? [])) {
                // No tiene subordinados → es un nodo aislado, no se muestra en el árbol
                continue;
            }

            $roots[] = $this->buildTreeNode(
                $userId,
                $userMap,
                $primaryParentByUser,
                $placed,
                null, // sin filtro de área
                $users,
                []
            );
        }

        // Nodos huérfanos que no fueron colocados (por si algún nodo raíz no se agregó)
        foreach ($userMap as $userId => $node) {
            if (isset($placed[$userId])) {
                continue;
            }

            // Si no tiene subordinados, no es parte del árbol
            if (empty($subordinateIdsByUser[$userId] ?? [])) {
                continue;
            }

            $roots[] = $this->buildTreeNode(
                $userId,
                $userMap,
                $primaryParentByUser,
                $placed,
                null,
                $users,
                []
            );
        }

        return [
            'tree' => $roots,
            'unassigned' => $unassigned,
            'stats' => [
                'total_users' => $users->count(),
                'in_tree' => count($placed),
                'unassigned' => count($unassigned),
                'relations' => $relations->count(),
                'cycles_detected' => $this->countExistingCycles($superiorIdsByUser),
            ],
        ];
    }

    /**
     * Filtra el árbol para quedarse solo con los nodos que coinciden con el área
     * y sus ancestros.
     *
     * @param array<int, array> $fullTree
     * @param int $areaId
     * @return array<int, array>
     */
    private function filterTreeByArea(array $fullTree, int $areaId): array
    {
        // 1. Obtener todos los nodos en una lista plana
        $allNodes = [];
        $this->flattenTree($fullTree['tree'] ?? [], $allNodes);

        // 2. IDs de nodos que tienen el área seleccionada o descendientes con esa área
        $matchingIds = [];
        foreach ($allNodes as $node) {
            if ($this->nodeOrDescendantHasArea($node, $areaId)) {
                $matchingIds[] = $node['id'];
            }
        }

        if (empty($matchingIds)) {
            // No hay nodos que coincidan con el área
            return [
                'tree' => [],
                'unassigned' => [],
                'stats' => [
                    'total_users' => 0,
                    'in_tree' => 0,
                    'unassigned' => 0,
                    'relations' => 0,
                    'cycles_detected' => 0,
                ],
            ];
        }

        // 3. Obtener todos los ancestros de esos nodos
        $ancestorIds = $this->getAncestors($allNodes, $matchingIds);

        // 4. Combinar ambos conjuntos
        $visibleIds = array_unique(array_merge($matchingIds, $ancestorIds));

        // 5. Filtrar el árbol completo manteniendo solo los nodos visibles
        $filteredTree = $this->filterTreeByIds($fullTree['tree'] ?? [], $visibleIds);

        // 6. Recalcular usuarios sin asignar para el árbol filtrado (solo los que están en el árbol filtrado y tienen missing)
        $unassigned = [];
        foreach ($fullTree['unassigned'] ?? [] as $user) {
            if (in_array($user['id'], $visibleIds)) {
                $unassigned[] = $user;
            }
        }

        return [
            'tree' => $filteredTree,
            'unassigned' => $unassigned,
            'stats' => [
                'total_users' => count($allNodes),
                'in_tree' => count($visibleIds),
                'unassigned' => count($unassigned),
                'relations' => $fullTree['stats']['relations'] ?? 0,
                'cycles_detected' => $fullTree['stats']['cycles_detected'] ?? 0,
            ],
        ];
    }

    /**
     * Aplana el árbol recursivamente en un array plano.
     *
     * @param array<int, array> $nodes
     * @param array<int, array> $result
     */
    private function flattenTree(array $nodes, array &$result): void
    {
        foreach ($nodes as $node) {
            $result[] = $node;
            if (!empty($node['children'])) {
                $this->flattenTree($node['children'], $result);
            }
        }
    }

    /**
     * Determina si el nodo o alguno de sus descendientes tiene el área dada.
     *
     * @param array $node
     * @param int $areaId
     * @return bool
     */
    private function nodeOrDescendantHasArea(array $node, int $areaId): bool
    {
        if (isset($node['physical_area_id']) && (int) $node['physical_area_id'] === $areaId) {
            return true;
        }

        foreach ($node['children'] ?? [] as $child) {
            if ($this->nodeOrDescendantHasArea($child, $areaId)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Devuelve los IDs de todos los ancestros de los nodos dados.
     *
     * @param array<int, array> $allNodes
     * @param array<int> $nodeIds
     * @return array<int>
     */
    private function getAncestors(array $allNodes, array $nodeIds): array
    {
        // Construir mapa hijo -> padre
        $parentMap = [];
        foreach ($allNodes as $node) {
            foreach ($node['children'] ?? [] as $child) {
                $parentMap[$child['id']] = $node['id'];
            }
        }

        $ancestors = [];
        foreach ($nodeIds as $id) {
            $current = $id;
            while (isset($parentMap[$current])) {
                $ancestors[] = $parentMap[$current];
                $current = $parentMap[$current];
            }
        }

        return $ancestors;
    }

    /**
     * Filtra el árbol quedándose solo con los nodos cuyos IDs estén en $visibleIds.
     *
     * @param array<int, array> $nodes
     * @param array<int> $visibleIds
     * @return array<int, array>
     */
    private function filterTreeByIds(array $nodes, array $visibleIds): array
    {
        $filtered = [];
        foreach ($nodes as $node) {
            if (in_array($node['id'], $visibleIds, true)) {
                $node['children'] = $this->filterTreeByIds($node['children'] ?? [], $visibleIds);
                $filtered[] = $node;
            }
        }
        return $filtered;
    }

    /**
     * Prepara el resultado final.
     *
     * @param array $treeData
     * @param int|null $areaId
     * @return array
     */
    private function prepareResult(array $treeData, ?int $areaId = null): array
    {
        return [
            'tree' => $treeData['tree'] ?? [],
            'unassigned' => $treeData['unassigned'] ?? [],
            'stats' => $treeData['stats'] ?? [
                'total_users' => 0,
                'in_tree' => 0,
                'unassigned' => 0,
                'relations' => 0,
                'cycles_detected' => 0,
            ],
        ];
    }

    /**
     * Construye el nodo del árbol recursivamente (sin filtro de área).
     *
     * @param int $userId
     * @param array<int, array> $userMap
     * @param array<int, int|null> $primaryParentByUser
     * @param array<int, bool> $placed
     * @param int|null $physicalAreaId
     * @param Collection $users
     * @param array<int, bool> $ancestors
     * @return array
     */
    private function buildTreeNode(
        int $userId,
        array $userMap,
        array $primaryParentByUser,
        array &$placed,
        ?int $physicalAreaId,
        Collection $users,
        array $ancestors
    ): array {
        if (isset($placed[$userId]) || isset($ancestors[$userId])) {
            return $userMap[$userId];
        }

        $placed[$userId] = true;
        $ancestors[$userId] = true;
        $node = $userMap[$userId];
        $node['children'] = [];

        foreach ($userMap as $childId => $childNode) {
            if (($primaryParentByUser[$childId] ?? null) !== $userId) {
                continue;
            }

            if ($physicalAreaId !== null) {
                $areaId = $users->get($childId)?->activeOrganizationalProfile?->physical_area_id;
                if ((int) $areaId !== (int) $physicalAreaId) {
                    continue;
                }
            }

            $node['children'][] = $this->buildTreeNode(
                $childId,
                $userMap,
                $primaryParentByUser,
                $placed,
                $physicalAreaId,
                $users,
                $ancestors
            );
        }

        unset($ancestors[$userId]);

        return $node;
    }

    /**
     * @param  array<int, array<int>>  $superiorIdsByUser
     */
    public function countExistingCycles(array $superiorIdsByUser): int
    {
        $cycles = 0;
        $visited = [];
        $stack = [];

        foreach (array_keys($superiorIdsByUser) as $userId) {
            if (! isset($visited[$userId])) {
                $cycles += $this->detectCyclesFromNode($userId, $superiorIdsByUser, $visited, $stack);
            }
        }

        return $cycles;
    }

    /**
     * @param  array<int, array<int>>  $superiorIdsByUser
     * @param  array<int, bool>  $visited
     * @param  array<int, bool>  $stack
     */
    private function detectCyclesFromNode(int $userId, array $superiorIdsByUser, array &$visited, array &$stack): int
    {
        $visited[$userId] = true;
        $stack[$userId] = true;
        $cycles = 0;

        foreach ($superiorIdsByUser[$userId] ?? [] as $superiorId) {
            if (! isset($visited[$superiorId])) {
                $cycles += $this->detectCyclesFromNode($superiorId, $superiorIdsByUser, $visited, $stack);
            } elseif (isset($stack[$superiorId])) {
                $cycles++;
            }
        }

        unset($stack[$userId]);

        return $cycles;
    }

    /**
     * @param  array<int>  $superiorIds
     * @return array<int, string>
     */
    private function resolveMissingAssignments(User $user, array $superiorIds, ?UserOrganizationalProfile $profile): array
    {
        $missing = [];

        if (empty($superiorIds)) {
            $missing[] = 'superior';
        }

        if (! $profile?->job_position_id) {
            $missing[] = 'job_position';
        }

        if (! $profile?->physical_area_id) {
            $missing[] = 'physical_area';
        }

        return $missing;
    }

    public function wouldCreateCycle(int $subordinateId, int $superiorId): bool
    {
        if ($subordinateId === $superiorId) {
            return true;
        }

        $relations = UserHierarchyRelation::query()
            ->get(['subordinate_id', 'superior_id']);

        $superiorsMap = [];
        $subordinatesMap = [];

        foreach ($relations as $relation) {
            $superiorsMap[$relation->subordinate_id][] = $relation->superior_id;
            $subordinatesMap[$relation->superior_id][] = $relation->subordinate_id;
        }

        if ($this->canReach($superiorId, $subordinateId, $superiorsMap)) {
            return true;
        }

        if ($this->canReach($subordinateId, $superiorId, $subordinatesMap)) {
            return true;
        }

        return false;
    }

    /**
     * @param  array<int, array<int>>  $adjacency
     */
    private function canReach(int $start, int $target, array $adjacency): bool
    {
        $visited = [];
        $queue = [$start];

        while (! empty($queue)) {
            $current = array_shift($queue);

            if ($current === $target) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }

            $visited[$current] = true;

            foreach ($adjacency[$current] ?? [] as $next) {
                if (! isset($visited[$next])) {
                    $queue[] = $next;
                }
            }
        }

        return false;
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    public function createRelation(array $attributes): UserHierarchyRelation
    {
        $subordinateId = (int) $attributes['subordinate_id'];
        $superiorId = (int) $attributes['superior_id'];

        if ($this->wouldCreateCycle($subordinateId, $superiorId)) {
            throw ValidationException::withMessages([
                'superior_id' => 'La relación jerárquica generaría un ciclo infinito.',
            ]);
        }

        return UserHierarchyRelation::create([
            'subordinate_id' => $subordinateId,
            'superior_id' => $superiorId,
            'job_position_id' => $attributes['job_position_id'] ?? null,
            'physical_area_id' => $attributes['physical_area_id'] ?? null,
        ]);
    }

    public function detachSubordinatesFromSuperior(int $superiorId): int
    {
        return UserHierarchyRelation::query()
            ->where('superior_id', $superiorId)
            ->delete();
    }

    public function detachAllRelationsForUser(int $userId): void
    {
        DB::transaction(function () use ($userId) {
            UserHierarchyRelation::query()
                ->where('subordinate_id', $userId)
                ->orWhere('superior_id', $userId)
                ->delete();
        });
    }
}