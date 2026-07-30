<?php

namespace App\Http\Middleware;

use App\Services\Authorization\PermissionAccessService;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureAccessPermission
{
    public function __construct(private readonly PermissionAccessService $permissions) {}

    public function handle(Request $request, Closure $next, string $permissionKey): Response
    {
        abort_unless(
            $this->permissions->allows($request->user(), $permissionKey),
            403,
            'Sin permisos para acceder a esta sección.'
        );

        return $next($request);
    }
}
