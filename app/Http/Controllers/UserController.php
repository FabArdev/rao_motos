<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreUsuarioRequest;
use App\Http\Requests\UpdateUsuarioRequest;
use App\Models\Cliente;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $q = $request->string('q')->toString();

        $usuarios = User::with('role')
            ->when($q, function ($query) use ($q) {
                $query->where(function ($sub) use ($q) {
                    $sub->where('nombre', 'ilike', "%{$q}%")
                        ->orWhere('apellidos', 'ilike', "%{$q}%")
                        ->orWhere('email', 'ilike', "%{$q}%")
                        ->orWhere('ci', 'ilike', "%{$q}%");
                });
            })
            ->orderBy('nombre')
            ->paginate(12)
            ->withQueryString();

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'filtros' => ['q' => $q],
        ]);
    }

    public function create()
    {
        return Inertia::render('Usuarios/Create', [
            'roles' => Role::orderBy('id')->get(['id', 'nombre']),
        ]);
    }

    public function store(StoreUsuarioRequest $request)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {
            $user = User::create([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'ci' => $data['ci'],
                'telefono' => $data['telefono'],
                'direccion' => $data['direccion'] ?? null,
                'email' => $data['email'],
                'password' => Hash::make($data['password']),
                'role_id' => $data['role_id'],
                'estado' => $data['estado'] ?? true,
            ]);

            if ($this->esRolCliente($data['role_id'])) {
                Cliente::updateOrCreate(['id' => $user->id], ['nit_ci' => $data['nit_ci'] ?? null]);
            }
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario creado correctamente.');
    }

    public function show(User $usuario)
    {
        $usuario->load('role', 'cliente');

        return Inertia::render('Usuarios/Show', ['usuario' => $usuario]);
    }

    public function edit(User $usuario)
    {
        $usuario->load('cliente');

        return Inertia::render('Usuarios/Edit', [
            'usuario' => $usuario,
            'roles' => Role::orderBy('id')->get(['id', 'nombre']),
        ]);
    }

    public function update(UpdateUsuarioRequest $request, User $usuario)
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $usuario) {
            $usuario->fill([
                'nombre' => $data['nombre'],
                'apellidos' => $data['apellidos'],
                'ci' => $data['ci'],
                'telefono' => $data['telefono'],
                'direccion' => $data['direccion'] ?? null,
                'email' => $data['email'],
                'role_id' => $data['role_id'],
                'estado' => $data['estado'] ?? $usuario->estado,
            ]);

            if (! empty($data['password'])) {
                $usuario->password = Hash::make($data['password']);
            }

            $usuario->save();

            if ($this->esRolCliente($data['role_id'])) {
                Cliente::updateOrCreate(['id' => $usuario->id], ['nit_ci' => $data['nit_ci'] ?? null]);
            }
        });

        return redirect()->route('usuarios.index')->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(Request $request, User $usuario)
    {
        if ($usuario->id === $request->user()->id) {
            return back()->with('error', 'No puedes eliminar tu propio usuario.');
        }

        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuario eliminado.');
    }

    private function esRolCliente($roleId): bool
    {
        return optional(Role::find($roleId))->nombre === 'cliente';
    }
}
