<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserPermissionController extends Controller
{
    public function index(): View
    {
        $this->authorizeDefaultAdmin();

        $menuPermissionsReady = Schema::hasColumn('users', 'menu_permissions');

        $users = User::query()
            ->with('empresa:id,nome,nivel_acesso')
            ->orderByDesc('id')
            ->paginate(15);

        return view('admin.user-permissions.index', compact('users', 'menuPermissionsReady'));
    }

    public function edit(User $user): View|RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        if (! Schema::hasColumn('users', 'menu_permissions')) {
            return redirect()
                ->route('admin.user-permissions.index')
                ->with('status', 'Execute as migrations para habilitar permissões de acesso.');
        }

        $menuOptions = User::availableMenuPermissions();

        return view('admin.user-permissions.edit', compact('user', 'menuOptions'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeDefaultAdmin();

        if (! Schema::hasColumn('users', 'menu_permissions')) {
            return redirect()
                ->route('admin.user-permissions.index')
                ->with('status', 'Execute as migrations para habilitar permissões de acesso.');
        }

        $validated = $request->validate([
            'menu_permissions' => ['nullable', 'array'],
            'menu_permissions.*' => ['string', Rule::in(array_keys(User::availableMenuPermissions()))],
        ]);

        $user->update([
            'menu_permissions' => $validated['menu_permissions'] ?? [],
        ]);

        return redirect()
            ->route('admin.user-permissions.index')
            ->with('status', 'Permissões atualizadas com sucesso.');
    }

    private function authorizeDefaultAdmin(): void
    {
        abort_unless(Auth::user()?->isDefaultAdmin(), 403);
    }
}
