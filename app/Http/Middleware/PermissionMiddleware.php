<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class PermissionMiddleware
{
    public function handle(Request $request, Closure $next, string ...$permissions): Response
    {
        /** @var User|null $user */
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        $grantedPermissions = $user->role?->permissions()->pluck('name');

        $hasAllPermissions = $grantedPermissions !== null
            && collect($permissions)->every(
                fn (string $permission): bool => $grantedPermissions->contains($permission)
            );

        if (! $hasAllPermissions) {
            abort(403, 'Anda tidak memiliki permission untuk melakukan aksi ini.');
        }

        return $next($request);
    }
}
