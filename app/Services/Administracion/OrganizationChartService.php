<?php

namespace App\Services\Administracion;

use App\Models\User;
use App\Models\UserHierarchyRelation;
use App\Models\UserOrganizationalProfile;
use Carbon\Carbon;
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

        // Aprovechar la actividad que Laravel ya registra en las sesiones.
        // No se altera la autenticación ni se agrega un segundo mecanismo de presencia.
        $lastActivityByUser = DB::table('sessions')
            ->whereNotNull('user_id')
            ->select('user_id', DB::raw('MAX(last_activity) as last_activity'))
            ->groupBy('user_id')
            ->pluck('last_activity', 'user_id');
        $onlineThreshold = now()->subMinutes(3)->timestamp;

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
            // Actividad registrada en las sesiones o por el seguimiento de presencia (users.last_seen_at).
            $lastActivityTimestamp = max((int) ($lastActivityByUser[$user->id] ?? 0), (int) ($user->last_seen_at?->timestamp ?? 0));
            $isOnline = $lastActivityTimestamp >= $onlineThreshold;
            $lastActivity = $lastActivityTimestamp > 0
                ? Carbon::createFromTimestamp($lastActivityTimestamp)->locale('es')
                : null;

            $node = [
                'id' => $user->id,
                'name' => trim("{$user->name} {$user->last_name}"),
                'email' => $user->email,
                'photo' => $user->profile_photo_path ? $user->profile_photo_url : null,
                'created_at' => $user->created_at?->toIso8601String(),
                'role' => $user->role?->role,
                'job_position' => $profile?->jobPosition?->name,
                'physical_area' => $profile?->physicalArea?->name,
                'physical_area_id' => $profile?->physical_area_id,
                'superior_count' => count($superiorIds),
                'subordinate_count' => count($subordinateIds),
                'is_online' => $isOnline,
                // Cuánto tiempo lleva sin conectarse: desde su última conexión o, si nunca ha entrado, desde que se creó su usuario.
                'presence_label' => $isOnline
                    ? 'En línea'
                    : ($lastActivity
                        ? 'Última conexión hace '.$lastActivity->diffForHumans(now(), true)
                        : 'Sin conectarse hace '.($user->created_at ? $user->created_at->copy()->locale('es')->diffForHumans(now(), true) : 'un tiempo')),
                'children' => [],
            ];

            $userMap[$user->id] = $node;

            // Si el usuario NO tiene relaciones y tiene campos faltantes, va a "usuarios sin asignar"
            if (!in_array($user->id, $usersWithRelations) && !empty($missing)) {
                $allUnassigned[] = array_merge($node, ['missing' => $missing]);
            }
        }

        // Los usuarios sin asignar se muestran del más nuevo al más antiguo.
        usort($allUnassigned, static fn (array $a, array $b): int => strcmp((string) ($b['created_at'] ?? ''), (string) ($a['created_at'] ?? '')));

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

            $roots[] = $this->buildTreeNode($userId, $userMap, $superiorIdsByUser, $subordinateIdsByUser, $placed, 'raiz');
        }

        // Si algún nodo no fue colocado (por ejemplo, si hay un ciclo o un error), lo agregamos como raíz huérfana
        foreach ($usersWithRelations as $userId) {
            if (!isset($placed[$userId]) && isset($userMap[$userId])) {
                $roots[] = $this->buildTreeNode($userId, $userMap, $superiorIdsByUser, $subordinateIdsByUser, $placed, 'huerfano');
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
        string $parentKey = 'raiz',
        array $ancestors = []
    ): array {
        // «instance» es única por posición en el árbol: una persona con varios jefes aparece bajo cada uno de ellos
        // (con todo su equipo), así que el identificador de la persona por sí solo ya no sirve como llave de dibujo.
        $instance = $parentKey.'>'.$userId;

        // Una referencia circular se corta aquí: la persona se muestra, pero sin volver a bajar por su rama.
        if (isset($ancestors[$userId])) {
            return array_merge($userMap[$userId], ['children' => [], 'instance' => $instance]);
        }

        $placed[$userId] = true;
        $ancestors[$userId] = true;
        $node = $userMap[$userId];
        $node['instance'] = $instance;
        $node['children'] = [];

        // Obtener los subordinados directos (hijos) de este usuario
        $childIds = array_values(array_unique($subordinateIdsByUser[$userId] ?? []));

        foreach ($childIds as $childId) {
            if (!isset($userMap[$childId])) {
                continue;
            }

            // Verificar que el padre de este hijo sea este usuario (relación directa)
            $parents = $superiorIdsByUser[$childId] ?? [];
            if (!in_array($userId, $parents)) {
                continue;
            }

            // Construir el nodo hijo recursivamente (se repite bajo cada jefe que lo tenga)
            $node['children'][] = $this->buildTreeNode(
                $childId,
                $userMap,
                $superiorIdsByUser,
                $subordinateIdsByUser,
                $placed,
                $instance,
                $ancestors
            );
        }

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
        // Quien está en el árbol únicamente por ser jefe de alguien del área elegida se marca como «de otra área»
        // para dibujarlo con otro color y que se vea de qué rama sale cada persona.
        return $this->markOutOfArea($this->filterTreeByIds($fullTree, $visibleIds), $areaId);
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
    /**
     * @param  array<int, array>  $nodes
     * @return array<int, array>
     */
    private function markOutOfArea(array $nodes, int $areaId): array
    {
        foreach ($nodes as $index => $node) {
            $nodes[$index]['out_of_area'] = (int) ($node['physical_area_id'] ?? 0) !== $areaId;
            $nodes[$index]['children'] = $this->markOutOfArea($node['children'] ?? [], $areaId);
        }

        return $nodes;
    }

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

        // Una persona con varios jefes aparece más de una vez en el árbol, pero cuenta una sola vez.
        return count(array_unique(array_column($flat, 'id')));
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

        // La tabla sigue siendo relacional para facilitar una futura vuelta a
        // muchos-a-muchos. Por ahora cada subordinado solo reporta a un jefe.
        if (UserHierarchyRelation::query()
            ->where('subordinate_id', $subordinateId)
            ->where('superior_id', '<>', $superiorId)
            ->exists()) {
            throw ValidationException::withMessages([
                'superior_id' => 'El subordinado ya tiene un jefe directo asignado.',
            ]);
        }

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
