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
        // Obtener todas las relaciones (sin filtrar por área todavía). Cada equipo se dibuja en el orden guardado (posición) y
        // quien no tiene posición va al final, en el orden en que se asignó.
        $relations = UserHierarchyRelation::query()
            ->orderByRaw('CASE WHEN position IS NULL THEN 1 ELSE 0 END')
            ->orderBy('position')
            ->orderBy('id')
            ->get(['subordinate_id', 'superior_id']);

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
            $isOnline = $lastActivityTimestamp >= $onlineThreshold
                || in_array($user->email, (array) config('support.always_online_emails', []), true);
            $lastActivity = $lastActivityTimestamp > 0
                ? Carbon::createFromTimestamp($lastActivityTimestamp)->locale('es')
                : null;

            $node = [
                'id' => $user->id,
                'name' => trim("{$user->name} {$user->last_name}"),
                'first_name' => trim((string) $user->name),
                'last_name' => trim((string) $user->last_name),
                'email' => $user->email,
                'photo' => $user->avatarUrl(), // su foto o, si no tiene (o falta el archivo), la ilustración según su género
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

    /**
     * Sello del organigrama: cambia en cuanto cambia una relación, una persona (nombre, correo, rol, foto) o su puesto o
     * área. Las pantallas abiertas lo comparan para actualizarse solas cuando alguien más modifica el organigrama.
     * No usa updated_at de los usuarios porque la presencia en línea lo toca en cada petición.
     */
    public function chartSignature(): string
    {
        $relations = UserHierarchyRelation::query()
            ->orderBy('subordinate_id')
            ->orderBy('superior_id')
            ->get(['subordinate_id', 'superior_id', 'position'])
            ->map(fn (UserHierarchyRelation $relation): string => $relation->subordinate_id.'>'.$relation->superior_id.'@'.$relation->position)
            ->implode(',');

        $users = User::query()
            ->orderBy('id')
            ->get(['id', 'name', 'last_name', 'email', 'role_id', 'profile_photo_path', 'gender'])
            ->map(fn (User $user): string => implode('|', [$user->id, $user->name, $user->last_name, $user->email, $user->role_id, $user->profile_photo_path, $user->gender]))
            ->implode(',');

        $profiles = UserOrganizationalProfile::query()
            ->where('is_active', true)
            ->orderBy('user_id')
            ->get(['user_id', 'job_position_id', 'physical_area_id'])
            ->map(fn (UserOrganizationalProfile $profile): string => $profile->user_id.':'.$profile->job_position_id.':'.$profile->physical_area_id)
            ->implode(',');

        return md5($relations.'#'.$users.'#'.$profiles);
    }

    /**
     * Pone a una persona bajo un nuevo jefe directo; su equipo (todo lo que cuelga de ella) se mueve con ella.
     *
     * $expectedSuperiorId es el jefe que tenía la persona en la pantalla de quien la movió. Si ya no coincide con el de la
     * base de datos, otra persona cambió el organigrama mientras tanto y se lanza OrganizationChartConflict sin tocar nada.
     * Todo ocurre en una transacción que bloquea las relaciones, así dos movimientos al mismo tiempo no se pisan.
     *
     * @return bool false si la persona ya estaba bajo ese jefe (no había nada que cambiar)
     */
    public function moveUser(int $userId, int $newSuperiorId, ?int $expectedSuperiorId, bool $checkExpected = true): bool
    {
        if ($userId === $newSuperiorId) {
            throw ValidationException::withMessages(['move' => 'Una persona no puede ser su propio jefe.']);
        }

        return DB::transaction(function () use ($userId, $newSuperiorId, $expectedSuperiorId, $checkExpected): bool {
            $relations = UserHierarchyRelation::query()->lockForUpdate()->get(['id', 'subordinate_id', 'superior_id']);
            $currentSuperiorId = $this->currentSuperiorId($relations, $userId);

            if ($checkExpected && $currentSuperiorId !== $expectedSuperiorId) {
                throw new OrganizationChartConflict('El jefe de la persona cambió desde que se dibujó el organigrama.');
            }

            if (User::query()->whereKey([$userId, $newSuperiorId])->count() !== 2) {
                throw ValidationException::withMessages(['move' => 'Una de las personas ya no existe; el organigrama se actualizó.']);
            }

            if ($currentSuperiorId === $newSuperiorId) {
                return false;
            }

            // El nuevo jefe no puede estar dentro del equipo de la persona (se formaría un círculo)
            $subordinatesMap = [];
            foreach ($relations as $relation) {
                $subordinatesMap[(int) $relation->superior_id][] = (int) $relation->subordinate_id;
            }
            if ($this->canReach($userId, $newSuperiorId, $subordinatesMap)) {
                throw ValidationException::withMessages(['move' => 'No puedes poner a una persona debajo de alguien de su propio equipo.']);
            }

            UserHierarchyRelation::query()->where('subordinate_id', $userId)->delete();
            UserHierarchyRelation::create([
                'subordinate_id' => $userId,
                'superior_id' => $newSuperiorId,
                'job_position_id' => null,
                'physical_area_id' => null,
            ]);

            return true;
        });
    }

    /**
     * Coloca a una persona (con todo su equipo) respecto de otra del organigrama, como al arrastrarla y soltarla:
     *  - 'child': en el equipo de la otra persona, al final.
     *  - 'before' / 'after': junto a la otra persona, con su mismo jefe, a su izquierda o a su derecha.
     *  - 'above': en el lugar de la otra persona, a cargo de ella (la otra pasa a su equipo con toda su rama).
     *
     * Las dos personas deben seguir con el jefe que la pantalla mostraba ($expectedSuperiorId y
     * $expectedTargetSuperiorId); si alguien más cambió el organigrama se lanza OrganizationChartConflict sin tocar nada.
     *
     * @return array{changed: bool, superior_id: ?int, previous_superior_id: ?int}
     */
    public function placeUser(int $userId, int $targetId, string $placement, ?int $expectedSuperiorId, ?int $expectedTargetSuperiorId): array
    {
        if (! in_array($placement, ['child', 'before', 'after', 'above'], true)) {
            throw ValidationException::withMessages(['move' => 'Ese movimiento no es válido.']);
        }

        if ($userId === $targetId) {
            throw ValidationException::withMessages(['move' => 'Suelta a la persona sobre otra del organigrama.']);
        }

        return DB::transaction(function () use ($userId, $targetId, $placement, $expectedSuperiorId, $expectedTargetSuperiorId): array {
            $relations = UserHierarchyRelation::query()->lockForUpdate()->get(['id', 'subordinate_id', 'superior_id', 'position']);
            $userSuperiorId = $this->currentSuperiorId($relations, $userId);
            $targetSuperiorId = $this->currentSuperiorId($relations, $targetId);

            if ($userSuperiorId !== $expectedSuperiorId || $targetSuperiorId !== $expectedTargetSuperiorId) {
                throw new OrganizationChartConflict('El organigrama cambió desde que se dibujó.');
            }

            if (User::query()->whereKey([$userId, $targetId])->count() !== 2) {
                throw ValidationException::withMessages(['move' => 'Una de las personas ya no existe; el organigrama se actualizó.']);
            }

            $subordinatesMap = [];
            foreach ($relations as $relation) {
                $subordinatesMap[(int) $relation->superior_id][] = (int) $relation->subordinate_id;
            }
            // Ni debajo, ni junto, ni a cargo de alguien de su propio equipo (se formaría un círculo)
            if ($this->canReach($userId, $targetId, $subordinatesMap)) {
                throw ValidationException::withMessages(['move' => 'No puedes moverla dentro de su propio equipo.']);
            }

            // Soltarla sobre su propio jefe la pasa al final de su equipo (si ya era la última, no cambia nada)
            $unchanged = ['changed' => false, 'superior_id' => $userSuperiorId, 'previous_superior_id' => $userSuperiorId];

            if (in_array($placement, ['before', 'after'], true) && $targetSuperiorId === null) {
                throw ValidationException::withMessages(['move' => 'Quien encabeza el organigrama no tiene jefe: suéltala arriba de esa persona para ponerla a su cargo.']);
            }

            $newSuperiorId = $placement === 'child' ? $targetId : $targetSuperiorId;

            // Nuevo orden de cada equipo que cambia
            $order = [];
            if ($newSuperiorId !== null) {
                $team = array_values(array_diff($this->orderedChildren($relations, $newSuperiorId), [$userId]));
                $index = array_search($targetId, $team, true);
                if ($placement === 'child' || $index === false) {
                    // (también cuando ya era de su equipo: se va al final)
                    $team[] = $userId;
                } elseif ($placement === 'above') {
                    $team[$index] = $userId;
                } else {
                    array_splice($team, $placement === 'after' ? $index + 1 : $index, 0, [$userId]);
                }
                $order[$newSuperiorId] = $team;

                if ($placement !== 'above' && $userSuperiorId === $newSuperiorId && $team === $this->orderedChildren($relations, $newSuperiorId)) {
                    return $unchanged;
                }
            }
            if ($placement === 'above') {
                $order[$userId] = [...array_values(array_diff($this->orderedChildren($relations, $userId), [$targetId])), $targetId];
            }

            if ($userSuperiorId !== $newSuperiorId) {
                UserHierarchyRelation::query()->where('subordinate_id', $userId)->delete();
                if ($newSuperiorId !== null) {
                    UserHierarchyRelation::create(['subordinate_id' => $userId, 'superior_id' => $newSuperiorId, 'job_position_id' => null, 'physical_area_id' => null]);
                }
            }
            if ($placement === 'above') {
                UserHierarchyRelation::query()->where('subordinate_id', $targetId)->delete();
                UserHierarchyRelation::create(['subordinate_id' => $targetId, 'superior_id' => $userId, 'job_position_id' => null, 'physical_area_id' => null]);
            }
            foreach ($order as $superiorId => $childIds) {
                $this->writeOrder((int) $superiorId, $childIds);
            }

            return ['changed' => true, 'superior_id' => $newSuperiorId, 'previous_superior_id' => $userSuperiorId];
        });
    }

    /**
     * Saca a una persona del organigrama sin borrarla: su equipo directo pasa a reportar a su jefe, en el mismo lugar que
     * ocupaba ella (si no tenía jefe, cada subordinado queda como cabeza de su propia rama) y ella queda en «Sin asignar».
     *
     * @return array{superior_id: ?int, moved: int}
     */
    public function removeFromChart(int $userId, ?int $expectedSuperiorId = null, bool $checkExpected = false): array
    {
        return DB::transaction(function () use ($userId, $expectedSuperiorId, $checkExpected): array {
            $relations = UserHierarchyRelation::query()->lockForUpdate()->get(['id', 'subordinate_id', 'superior_id', 'position']);
            $superiorId = $this->currentSuperiorId($relations, $userId);

            if ($checkExpected && $superiorId !== $expectedSuperiorId) {
                throw new OrganizationChartConflict('El jefe de la persona cambió desde que se dibujó el organigrama.');
            }

            $subordinateIds = $this->orderedChildren($relations, $userId);

            UserHierarchyRelation::query()
                ->where('subordinate_id', $userId)
                ->orWhere('superior_id', $userId)
                ->delete();

            if ($superiorId !== null) {
                foreach ($subordinateIds as $subordinateId) {
                    UserHierarchyRelation::create([
                        'subordinate_id' => $subordinateId,
                        'superior_id' => $superiorId,
                        'job_position_id' => null,
                        'physical_area_id' => null,
                    ]);
                }

                // Su equipo ocupa su lugar entre los compañeros (no se va al final)
                $team = [];
                foreach ($this->orderedChildren($relations, $superiorId) as $childId) {
                    array_push($team, ...($childId === $userId ? $subordinateIds : [$childId]));
                }
                $this->writeOrder($superiorId, array_values(array_unique($team)));
            }

            return ['superior_id' => $superiorId, 'moved' => count($subordinateIds)];
        });
    }

    /**
     * Equipo directo de un jefe en el orden en que se dibuja (posición guardada; sin posición, al final por antigüedad).
     *
     * @param  Collection<int, UserHierarchyRelation>  $relations
     * @return array<int, int>
     */
    private function orderedChildren(Collection $relations, int $superiorId): array
    {
        $children = $relations->filter(fn (UserHierarchyRelation $relation): bool => (int) $relation->superior_id === $superiorId)->all();
        usort($children, static fn (UserHierarchyRelation $a, UserHierarchyRelation $b): int => [$a->position ?? PHP_INT_MAX, (int) $a->id] <=> [$b->position ?? PHP_INT_MAX, (int) $b->id]);

        return array_values(array_unique(array_map(static fn (UserHierarchyRelation $relation): int => (int) $relation->subordinate_id, $children)));
    }

    /** @param  array<int, int>  $childIds */
    private function writeOrder(int $superiorId, array $childIds): void
    {
        foreach (array_values($childIds) as $position => $childId) {
            UserHierarchyRelation::query()
                ->where('superior_id', $superiorId)
                ->where('subordinate_id', $childId)
                ->update(['position' => $position]);
        }
    }

    /** @param  Collection<int, UserHierarchyRelation>  $relations */
    private function currentSuperiorId(Collection $relations, int $userId): ?int
    {
        $superiorId = $relations->firstWhere('subordinate_id', $userId)?->superior_id;

        return $superiorId === null ? null : (int) $superiorId;
    }
}





/**/
