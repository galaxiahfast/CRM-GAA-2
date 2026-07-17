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
        $relations = UserHierarchyRelation::query()
            ->when($physicalAreaId, fn ($q) => $q->where('physical_area_id', $physicalAreaId))
            ->get(['subordinate_id', 'superior_id']);

        $superiorIdsByUser = [];
        $subordinateIdsByUser = [];

        foreach ($relations as $relation) {
            $superiorIdsByUser[$relation->subordinate_id][] = $relation->superior_id;
            $subordinateIdsByUser[$relation->superior_id][] = $relation->subordinate_id;
        }

        $users = User::query()
            ->with([
                'role:id,role',
                'activeOrganizationalProfile.jobPosition:id,name',
                'activeOrganizationalProfile.physicalArea:id,name',
            ])
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $userMap = [];
        $unassigned = [];

        foreach ($users as $user) {
            $profile = $user->activeOrganizationalProfile;
            $superiorIds = $superiorIdsByUser[$user->id] ?? [];
            $missing = $this->resolveMissingAssignments($user, $superiorIds, $profile);

            $node = [
                'id' => $user->id,
                'name' => trim("{$user->name} {$user->last_name}"),
                'email' => $user->email,
                'role' => $user->role?->role,
                'job_position' => $profile?->jobPosition?->name,
                'physical_area' => $profile?->physicalArea?->name,
                'superior_count' => count($superiorIds),
                'subordinate_count' => count($subordinateIdsByUser[$user->id] ?? []),
                'children' => [],
            ];

            $userMap[$user->id] = $node;

            if (! empty($missing)) {
                $unassigned[] = array_merge($node, ['missing' => $missing]);
            }
        }

        $primaryParentByUser = [];
        foreach ($userMap as $userId => $node) {
            $superiorIds = $superiorIdsByUser[$userId] ?? [];
            $primaryParentByUser[$userId] = empty($superiorIds) ? null : min($superiorIds);
        }

        $roots = [];
        $placed = [];

        foreach ($userMap as $userId => $node) {
            if ($primaryParentByUser[$userId] !== null) {
                continue;
            }

            if ($physicalAreaId !== null) {
                $areaId = $users->get($userId)?->activeOrganizationalProfile?->physical_area_id;
                if ((int) $areaId !== (int) $physicalAreaId) {
                    continue;
                }
            }

            $roots[] = $this->buildTreeNode(
                $userId,
                $userMap,
                $primaryParentByUser,
                $placed,
                $physicalAreaId,
                $users,
                []
            );
        }

        foreach ($userMap as $userId => $node) {
            if (isset($placed[$userId])) {
                continue;
            }

            if ($physicalAreaId !== null) {
                $areaId = $users->get($userId)?->activeOrganizationalProfile?->physical_area_id;
                if ((int) $areaId !== (int) $physicalAreaId) {
                    continue;
                }
            }

            $roots[] = $this->buildTreeNode(
                $userId,
                $userMap,
                $primaryParentByUser,
                $placed,
                $physicalAreaId,
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
     * @param  array<int, array>  $userMap
     * @param  array<int, int|null>  $primaryParentByUser
     * @param  array<int, bool>  $placed
     * @param  array<int, bool>  $ancestors
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
