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
        // Obtener todas las relaciones (sin filtrar por área todavía)
        $relations = UserHierarchyRelation::all(['subordinate_id', 'superior_id']);

        $superiorIdsByUser = [];
        $subordinateIdsByUser = [];

        foreach ($relations as $relation) {
            $superiorIdsByUser[$relation->subordinate_id][] = $relation->superior_id;
            $subordinateIdsByUser[$relation->superior_id][] = $relation->subordinate_id;
        }

        // Obtener todos los usuarios con sus perfiles
        $users = User::query()
            ->with([
                'role:id,role',
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        // Identificar usuarios que tienen al menos una relación (son parte del árbol)
        $usersWithRelations = array_unique(array_merge(
            array_keys($superiorIdsByUser),
            array_keys($subordinateIdsByUser)
        ));

        // Construir mapa de nodos para todos los usuarios
        $userMap = [];
        $allUnassigned = [];

        foreach ($users as $user) {
            $profile = $user->activeOrganizationalProfile;
            $superiorIds = $superiorIdsByUser[$user->id] ?? [];
            $subordinateIds = $subordinateIdsByUser[$user->id] ?? [];
            $missing = $this->resolveMissingAssignments($user, $superiorIds, $profile);

            $node = [
                'id' => $user->id,
                'name' => trim("{$user->name} {$user->last_name}"),
                'email' => $user->email,
                'created_at' => $user->created_at?->toIso8601String(),
                'role' => $user->role?->role,
                'job_position' => $profile?->jobPosition?->name,
                'physical_area' => $profile?->physicalArea?->name,
                'physical_area_id' => $profile?->physical_area_id,
                'superior_count' => count($superiorIds),
                'subordinate_count' => count($subordinateIds),
                'children' => [],
            ];

            $userMap[$user->id] = $node;

            // Si el usuario NO tiene relaciones y tiene campos faltantes, va a "usuarios sin asignar"
            if (!in_array($user->id, $usersWithRelations) && !empty($missing)) {
                $allUnassigned[] = array_merge($node, ['missing' => $missing]);
            }
        }

        // Construir el árbol completo (sin filtro de área)
        $fullTree = $this->buildTreeFromUserMap($userMap, $superiorIdsByUser, $subordinateIdsByUser, $usersWithRelations);

        // Si no hay filtro de área, devolver el árbol completo y los usuarios sin asignar
        if (empty($physicalAreaId)) {
            return [
                'tree' => $fullTree,
                'unassigned' => $allUnassigned,
                'stats' => [
                    'total_users' => $users->count(),
                    'in_tree' => $this->countNodesInTree($fullTree),
                    'unassigned' => count($allUnassigned),
                    'relations' => $relations->count(),
                    'cycles_detected' => $this->countExistingCycles($superiorIdsByUser),
                ],
            ];
        }

        // Aplicar filtro por área: conservar nodos que coinciden + sus ancestros
        $filteredTree = $this->filterTreeByArea($fullTree, $physicalAreaId);

        return [
            'tree' => $filteredTree,
            'unassigned' => $allUnassigned, // Los usuarios sin asignar son los mismos (sin relaciones)
            'stats' => [
                'total_users' => $users->count(),
                'in_tree' => $this->countNodesInTree($filteredTree),
                'unassigned' => count($allUnassigned),
                'relations' => $relations->count(),
                'cycles_detected' => $this->countExistingCycles($superiorIdsByUser),
            ],
        ];
    }

    /**
     * Construye el árbol a partir del mapa de usuarios, solo incluyendo nodos que tienen relaciones.
     *
     * @param array<int, array> $userMap
     * @param array<int, array<int>> $superiorIdsByUser
     * @param array<int, array<int>> $subordinateIdsByUser
     * @param array<int> $usersWithRelations
     * @return array<int, array>
     */
    private function buildTreeFromUserMap(array $userMap, array $superiorIdsByUser, array $subordinateIdsByUser, array $usersWithRelations): array
    {
        $roots = [];
        $placed = [];

        // Identificar nodos raíz: aquellos que están en el conjunto de usuarios con relaciones y no tienen superior
        foreach ($usersWithRelations as $userId) {
            if (!isset($userMap[$userId])) {
                continue;
            }

            $superiorIds = $superiorIdsByUser[$userId] ?? [];
            if (!empty($superiorIds)) {
                continue; // Este usuario tiene superior, no es raíz
            }

            $roots[] = $this->buildTreeNode($userId, $userMap, $superiorIdsByUser, $subordinateIdsByUser, $placed);
        }

        // Si algún nodo no fue colocado (por ejemplo, si hay un ciclo o un error), lo agregamos como raíz huérfana
        foreach ($usersWithRelations as $userId) {
            if (!isset($placed[$userId]) && isset($userMap[$userId])) {
                $roots[] = $this->buildTreeNode($userId, $userMap, $superiorIdsByUser, $subordinateIdsByUser, $placed);
            }
        }

        return $roots;
    }

    /**
     * Construye un nodo y sus descendientes recursivamente.
     *
     * @param int $userId
     * @param array<int, array> $userMap
     * @param array<int, array<int>> $superiorIdsByUser
     * @param array<int, array<int>> $subordinateIdsByUser
     * @param array<int, bool> $placed
     * @param array<int, bool> $ancestors
     * @return array
     */
    private function buildTreeNode(
        int $userId,
        array $userMap,
        array $superiorIdsByUser,
        array $subordinateIdsByUser,
        array &$placed,
        array $ancestors = []
    ): array {
        if (isset($placed[$userId]) || isset($ancestors[$userId])) {
            return $userMap[$userId];
        }

        $placed[$userId] = true;
        $ancestors[$userId] = true;
        $node = $userMap[$userId];
        $node['children'] = [];

        // Obtener los subordinados directos (hijos) de este usuario
        $childIds = $subordinateIdsByUser[$userId] ?? [];

        foreach ($childIds as $childId) {
            if (!isset($userMap[$childId])) {
                continue;
            }

            // Verificar que el padre de este hijo sea este usuario (relación directa)
            $parents = $superiorIdsByUser[$childId] ?? [];
            if (!in_array($userId, $parents)) {
                continue;
            }

            // Construir el nodo hijo recursivamente
            $node['children'][] = $this->buildTreeNode(
                $childId,
                $userMap,
                $superiorIdsByUser,
                $subordinateIdsByUser,
                $placed,
                $ancestors
            );
        }

        unset($ancestors[$userId]);

        return $node;
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
        $this->flattenTree($fullTree, $allNodes);

        // 2. IDs de nodos que tienen el área seleccionada o descendientes con esa área
        $matchingIds = [];
        foreach ($allNodes as $node) {
            if ($this->nodeOrDescendantHasArea($node, $areaId)) {
                $matchingIds[] = $node['id'];
            }
        }

        if (empty($matchingIds)) {
            return [];
        }

        // 3. Obtener todos los ancestros de esos nodos
        $ancestorIds = $this->getAncestors($allNodes, $matchingIds);

        // 4. Combinar ambos conjuntos
        $visibleIds = array_unique(array_merge($matchingIds, $ancestorIds));

        // 5. Filtrar el árbol completo manteniendo solo los nodos visibles
        return $this->filterTreeByIds($fullTree, $visibleIds);
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
     * Cuenta el número total de nodos en el árbol.
     *
     * @param array<int, array> $tree
     * @return int
     */
    private function countNodesInTree(array $tree): int
    {
        $flat = [];
        $this->flattenTree($tree, $flat);
        return count($flat);
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
     * 
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





/**/
