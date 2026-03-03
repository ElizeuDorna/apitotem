<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Empresa;
use App\Models\User;
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
            ? Empresa::query()->orderBy('fantasia')->get()
            : collect();

        $usuariosQuery = User::query()->orderByDesc('id');

        if (! $authUser->isDefaultAdmin()) {
            $usuariosQuery->where('cpf', $authUser->documento());
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
            ? Empresa::query()->orderBy('fantasia')->get()
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

        abort_unless($user->cpf === $authUser->documento(), 403);
    }
}
