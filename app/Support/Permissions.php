<?php

namespace App\Support;

use App\Models\User;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class Permissions
{
    public const CRUD = ['view', 'create', 'edit', 'delete'];

    /**
     * @return array<string, array{label: string, actions: list<string>}>
     */
    public static function modules(): array
    {
        return [
            'ingresos' => [
                'label' => 'Ingresos',
                'actions' => self::CRUD,
            ],
            'egresos' => [
                'label' => 'Egresos',
                'actions' => self::CRUD,
            ],
        ];
    }

    public static function key(string $module, string $action): string
    {
        return $module.'.'.$action;
    }

    /**
     * @return list<string>
     */
    public static function all(): array
    {
        $names = [];

        foreach (self::modules() as $module => $meta) {
            foreach ($meta['actions'] as $action) {
                $names[] = self::key($module, $action);
            }
        }

        return $names;
    }

    public static function moduleHas(string $module, string $action): bool
    {
        $actions = self::modules()[$module]['actions'] ?? [];

        return in_array($action, $actions, true);
    }

    public static function check(?User $user, string $module, string $action): bool
    {
        if (! $user || ! self::moduleHas($module, $action)) {
            return false;
        }

        return $user->can(self::key($module, $action));
    }

    public static function authorize(string $module, string $action): void
    {
        abort_unless(self::check(auth()->user(), $module, $action), 403);
    }

    public static function syncCatalog(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $desired = self::all();

        foreach ($desired as $name) {
            Permission::findOrCreate($name, 'web');
        }

        $usuario = Role::findOrCreate('usuario', 'web');
        $usuario->syncPermissions($desired);

        $admin = Role::findOrCreate('admin', 'web');
        $admin->syncPermissions($desired);

        Permission::query()
            ->where('guard_name', 'web')
            ->whereNotIn('name', $desired)
            ->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public static function assignUsuario(User $user): void
    {
        self::syncCatalog();
        $user->assignRole('usuario');
    }
}
