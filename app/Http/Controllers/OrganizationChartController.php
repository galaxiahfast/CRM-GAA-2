<?php

namespace App\Http\Controllers;

use App\Models\UserOrganizationalProfile;
use App\Services\Administracion\OrganizationChartService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class OrganizationChartController extends Controller
{
    public function __construct(
        private readonly OrganizationChartService $chartService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $physicalAreaId = $request->integer('physical_area_id') ?: null;

        return response()->json(
            $this->chartService->buildChartData($physicalAreaId)
        );
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'subordinate_id' => ['required', 'integer', 'exists:users,id'],
            'superior_id' => ['required', 'integer', 'exists:users,id', 'different:subordinate_id'],
            'job_position_id' => ['nullable', 'integer', 'exists:job_positions,id'],
            'physical_area_id' => ['nullable', 'integer', 'exists:physical_areas,id'],
        ]);

        $relation = $this->chartService->createRelation($validated);

        return response()->json($relation->load(['subordinate', 'superior', 'jobPosition', 'physicalArea']), 201);
    }

    public function destroy(int $relationId): JsonResponse
    {
        $relation = \App\Models\UserHierarchyRelation::findOrFail($relationId);
        $relation->delete();

        return response()->json(['message' => 'Relación jerárquica eliminada.']);
    }
}
