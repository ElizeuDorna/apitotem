<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
use App\Support\EmpresaContext;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Display the registration view.
     */
    public function create(): View
    {
        $requiresEmpresaVinculo = Schema::hasColumn('users', 'empresa_id');
        $authUser = Auth::user();

        $empresas = $requiresEmpresaVinculo
            ? $this->visibleEmpresasQuery($authUser)->orderBy('fantasia')->get()
            : collect();

        $usuariosQuery = User::query()->orderByDesc('id');

        if (! $authUser->isDefaultAdmin()) {
            if ($authUser->empresa_id) {
                $empresaIds = $this->visibleEmpresasQuery($authUser)->pluck('id');
                $usuariosQuery->whereIn('empresa_id', $empresaIds);
            } else {
                $usuariosQuery->where('cpf', $authUser->documento());
            }
        }

        $usuarios = $usuariosQuery->paginate(10);

        return view('auth.register', compact('empresas', 'requiresEmpresaVinculo', 'usuarios'));
    }

    /**
     * Handle an incoming registration request.
     *
     * @throws \Illuminate\Validation\ValidationException
     */
    public function store(Request $request): RedirectResponse
    {
        $documento = preg_replace('/\D/', '', (string) $request->input('cpf'));
        $requiresEmpresaVinculo = Schema::hasColumn('users', 'empresa_id');

        $request->merge([
            'cpf' => $documento,
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', 'unique:'.User::class],
            'cpf' => ['required', 'string', 'regex:/^(\d{11}|\d{14})$/', 'unique:users,cpf'],
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
        ];

        if ($requiresEmpresaVinculo) {
            $rules['empresa_id'] = ['required', 'integer', 'exists:empresa,id'];
        }

        $request->validate($rules);

        if ($requiresEmpresaVinculo && ! Auth::user()->isDefaultAdmin()) {
            $empresaPermitida = $this->visibleEmpresasQuery(Auth::user())
                ->whereKey((int) $request->empresa_id)
                ->exists();

            if (! $empresaPermitida) {
                return redirect()
                    ->back()
                    ->withErrors(['empresa_id' => 'Empresa nao permitida para o seu perfil.'])
                    ->withInput();
            }
        }

        $attributes = [
            'name' => $request->name,
            'email' => $request->email,
            'cpf' => $request->cpf,
            'password' => Hash::make($request->password),
        ];

        if ($requiresEmpresaVinculo) {
            $attributes['empresa_id'] = $request->empresa_id;
        }

        $user = User::create($attributes);

        event(new Registered($user));

        if (! Auth::check()) {
            Auth::login($user);
            return redirect(route('dashboard', absolute: false));
        }

        return redirect()
            ->route('register')
            ->with('status', 'Usuário cadastrado com sucesso.');
    }

    public function edit(User $user): View
    {
        $this->authorizeVisibleUser($user);

        $requiresEmpresaVinculo = Schema::hasColumn('users', 'empresa_id');

        $empresas = $requiresEmpresaVinculo
            ? $this->visibleEmpresasQuery(Auth::user())->orderBy('fantasia')->get()
            : collect();

        return view('auth.register-edit', compact('user', 'empresas', 'requiresEmpresaVinculo'));
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $this->authorizeVisibleUser($user);

        $documento = preg_replace('/\D/', '', (string) $request->input('cpf'));
        $requiresEmpresaVinculo = Schema::hasColumn('users', 'empresa_id');

        $request->merge([
            'cpf' => $documento,
        ]);

        $rules = [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'cpf' => ['required', 'string', 'regex:/^(\d{11}|\d{14})$/', Rule::unique('users', 'cpf')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Rules\Password::defaults()],
        ];

        if ($requiresEmpresaVinculo) {
            $rules['empresa_id'] = ['required', 'integer', 'exists:empresa,id'];
        }

        $validated = $request->validate($rules);

        if ($requiresEmpresaVinculo && ! Auth::user()->isDefaultAdmin()) {
            $empresaPermitida = $this->visibleEmpresasQuery(Auth::user())
                ->whereKey((int) $validated['empresa_id'])
                ->exists();

            if (! $empresaPermitida) {
                return redirect()
                    ->back()
                    ->withErrors(['empresa_id' => 'Empresa nao permitida para o seu perfil.'])
                    ->withInput();
            }
        }

        $attributes = [
            'name' => $validated['name'],
            'email' => $validated['email'],
            'cpf' => $validated['cpf'],
        ];

        if ($requiresEmpresaVinculo) {
            $attributes['empresa_id'] = $validated['empresa_id'];
        }

        if (! empty($validated['password'])) {
            $attributes['password'] = Hash::make($validated['password']);
        }

        $user->update($attributes);

        return redirect()
            ->route('register')
            ->with('status', 'Usuário atualizado com sucesso.');
    }

    private function authorizeVisibleUser(User $user): void
    {
        $authUser = Auth::user();

        if ($authUser->isDefaultAdmin()) {
            return;
        }

        if ($authUser->empresa_id) {
            if ($authUser->empresa && $authUser->empresa->isRevenda()) {
                $empresaIds = $this->visibleEmpresasQuery($authUser)->pluck('id');
                abort_unless($empresaIds->contains((int) $user->empresa_id), 403);
                return;
            }

            abort_unless((int) $user->empresa_id === (int) $authUser->empresa_id, 403);
            return;
        }

        abort_unless($user->cpf === $authUser->documento(), 403);
    }

    private function visibleEmpresasQuery(User $authUser)
    {
        $query = Empresa::query();

        if ($authUser->isDefaultAdmin()) {
            return $query;
        }

        if (! $authUser->empresa_id) {
            return $query->whereRaw('1 = 0');
        }

        $empresaVinculada = $authUser->empresa;

        if ($empresaVinculada && $empresaVinculada->isRevenda()) {
            $empresaAtivaId = EmpresaContext::resolveEmpresaIdForUser($authUser);

            if (! $empresaAtivaId) {
                return $query->whereRaw('1 = 0');
            }

            return $query->whereKey($empresaAtivaId);
        }

        return $query->where('id', $authUser->empresa_id);
    }
}
