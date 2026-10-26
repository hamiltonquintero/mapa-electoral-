<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Auditoria;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UsuarioController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum');
        $this->middleware('permission:usuario.ver')->only(['index', 'show']);
        $this->middleware('permission:usuario.crear')->only(['store']);
        $this->middleware('permission:usuario.editar')->only(['update', 'asignarRol', 'asignarTerritorio']);
        $this->middleware('permission:usuario.eliminar')->only(['destroy']);
    }

    public function index(Request $request)
    {
        $query = User::with(['roles', 'territorios:id,nombre,tipo'])
            ->withCount('territorios');

        if ($request->filled('buscar')) {
            $q = $request->buscar;
            $query->where(function ($q2) use ($q) {
                $q2->where('nombres', 'LIKE', "%{$q}%")
                   ->orWhere('apellidos', 'LIKE', "%{$q}%")
                   ->orWhere('email', 'LIKE', "%{$q}%");
            });
        }

        if ($request->filled('rol')) {
            $query->whereHas('roles', fn($r) => $r->where('name', $request->rol));
        }

        if ($request->filled('activo')) {
            $query->where('activo', (bool) $request->activo);
        }

        $usuarios = $query->orderBy('nombres')->orderBy('apellidos')->paginate($request->get('per_page', 25));

        return response()->json([
            'message' => 'Usuarios obtenidos',
            'data'       => $usuarios->items(),
            'pagination' => [
                'total'        => $usuarios->total(),
                'per_page'     => $usuarios->perPage(),
                'current_page' => $usuarios->currentPage(),
                'last_page'    => $usuarios->lastPage(),
                'from'         => $usuarios->firstItem(),
                'to'           => $usuarios->lastItem(),
            ],
        ]);
    }

    public function show(User $usuario)
    {
        $usuario->load(['roles', 'permissions', 'territorios', 'campanas']);

        return response()->json(['message' => 'Usuario', 'data' => $usuario]);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'        => 'required|string|max:255',
            'email'       => 'required|email|unique:users,email',
            'password'    => 'required|string|min:8|confirmed',
            'rol'         => 'required|string|exists:roles,name',
            'territorios' => 'array',
            'territorios.*' => 'exists:territorios,id',
        ]);

        $usuario = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'activo'   => true,
        ]);

        $usuario->assignRole($request->rol);

        if ($request->filled('territorios')) {
            $usuario->territorios()->sync($request->territorios);
        }

        Auditoria::registrar('usuario_creado', 'users', $usuario->id, [
            'nombre' => $usuario->name,
            'rol'    => $request->rol,
        ]);

        return response()->json(['message' => 'Usuario creado', 'data' => $usuario->load('roles')], 201);
    }

    public function update(Request $request, User $usuario)
    {
        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'email'       => "sometimes|email|unique:users,email,{$usuario->id}",
            'password'    => 'sometimes|string|min:8|confirmed',
            'activo'      => 'sometimes|boolean',
        ]);

        $data = $request->only(['name', 'email', 'activo']);
        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $usuario->update($data);

        Auditoria::registrar('usuario_actualizado', 'users', $usuario->id);

        return response()->json(['message' => 'Usuario actualizado', 'data' => $usuario->load('roles')]);
    }

    public function destroy(User $usuario)
    {
        // No permitir eliminar al propio usuario
        if ($usuario->id === auth()->id()) {
            return response()->json(['message' => 'No puedes eliminar tu propia cuenta'], 422);
        }

        Auditoria::registrar('usuario_eliminado', 'users', $usuario->id);
        $usuario->delete();

        return response()->json(['message' => 'Usuario eliminado']);
    }

    public function asignarRol(Request $request, User $usuario)
    {
        $request->validate(['rol' => 'required|string|exists:roles,name']);

        $rolAnterior = $usuario->roles->first()?->name;
        $usuario->syncRoles([$request->rol]);

        Auditoria::registrar('rol_asignado', 'users', $usuario->id, [
            'rol_anterior' => $rolAnterior,
            'rol_nuevo'    => $request->rol,
        ]);

        return response()->json(['message' => "Rol '{$request->rol}' asignado correctamente"]);
    }

    public function asignarTerritorios(Request $request, User $usuario)
    {
        $request->validate([
            'territorios'   => 'required|array',
            'territorios.*' => 'exists:territorios,id',
        ]);

        $usuario->territorios()->sync($request->territorios);

        Auditoria::registrar('territorio_asignado', 'users', $usuario->id, [
            'territorios' => $request->territorios,
        ]);

        return response()->json(['message' => 'Territorios asignados correctamente']);
    }

    public function roles()
    {
        $roles = Role::withCount('users')->orderBy('name')->get();

        return response()->json(['message' => 'Roles disponibles', 'data' => $roles]);
    }
}
