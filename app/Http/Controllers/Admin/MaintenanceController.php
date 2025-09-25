<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\User;

class MaintenanceController extends Controller
{
    /**
     * Mostrar página de gestión de mantenimiento
     */
    public function index()
    {
        $isActive = Cache::get('maintenance_mode', false);
        $retryAfter = Cache::get('maintenance_retry_after', 3600);
        $message = Cache::get('maintenance_message');
        $contactInfo = Cache::get('maintenance_contact_info', []);
        $allowedUsers = Cache::get('maintenance_allowed_users', []);
        $allowedIps = Cache::get('maintenance_allowed_ips', []);

        // Obtener información de usuarios permitidos
        $allowedUsersData = [];
        if (!empty($allowedUsers)) {
            $allowedUsersData = User::whereIn('id', $allowedUsers)->get();
        }

        return view('admin.maintenance.index', compact(
            'isActive',
            'retryAfter',
            'message',
            'contactInfo',
            'allowedUsersData',
            'allowedIps'
        ));
    }

    /**
     * Activar modo mantenimiento
     */
    public function enable(Request $request)
    {
        $request->validate([
            'retry_after' => 'nullable|integer|min:60|max:86400',
            'message' => 'nullable|string|max:500',
            'contact_email' => 'nullable|email',
            'contact_phone' => 'nullable|string|max:20',
            'support_url' => 'nullable|url'
        ]);

        try {
            // Activar modo mantenimiento
            Cache::put('maintenance_mode', true, now()->addHours(24));

            // Configurar tiempo de reintento
            $retryAfter = $request->integer('retry_after', 3600);
            Cache::put('maintenance_retry_after', $retryAfter, now()->addHours(24));

            // Configurar mensaje personalizado
            if ($request->filled('message')) {
                Cache::put('maintenance_message', $request->message, now()->addHours(24));
            }

            // Configurar información de contacto
            $contactInfo = [];
            if ($request->filled('contact_email')) {
                $contactInfo['email'] = $request->contact_email;
            }
            if ($request->filled('contact_phone')) {
                $contactInfo['phone'] = $request->contact_phone;
            }
            if ($request->filled('support_url')) {
                $contactInfo['support_url'] = $request->support_url;
            }

            if (!empty($contactInfo)) {
                Cache::put('maintenance_contact_info', $contactInfo, now()->addHours(24));
            }

            return back()->with('success', 'Modo mantenimiento activado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error activando modo mantenimiento: ' . $e->getMessage());
        }
    }

    /**
     * Desactivar modo mantenimiento
     */
    public function disable()
    {
        try {
            Cache::forget('maintenance_mode');
            Cache::forget('maintenance_retry_after');
            Cache::forget('maintenance_message');
            Cache::forget('maintenance_contact_info');

            return back()->with('success', 'Modo mantenimiento desactivado exitosamente');

        } catch (\Exception $e) {
            return back()->with('error', 'Error desactivando modo mantenimiento: ' . $e->getMessage());
        }
    }

    /**
     * Permitir acceso a un usuario
     */
    public function allowUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $allowedUsers = Cache::get('maintenance_allowed_users', []);

            if (!in_array($request->user_id, $allowedUsers)) {
                $allowedUsers[] = $request->user_id;
                Cache::put('maintenance_allowed_users', $allowedUsers, now()->addDays(30));

                $user = User::find($request->user_id);
                return back()->with('success', "Usuario '{$user->name}' agregado a la lista de permitidos");
            } else {
                return back()->with('info', 'El usuario ya está en la lista de permitidos');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error agregando usuario: ' . $e->getMessage());
        }
    }

    /**
     * Remover usuario de la lista de permitidos
     */
    public function removeUser(Request $request)
    {
        $request->validate([
            'user_id' => 'required|exists:users,id'
        ]);

        try {
            $allowedUsers = Cache::get('maintenance_allowed_users', []);
            $key = array_search($request->user_id, $allowedUsers);

            if ($key !== false) {
                unset($allowedUsers[$key]);
                Cache::put('maintenance_allowed_users', array_values($allowedUsers), now()->addDays(30));

                $user = User::find($request->user_id);
                return back()->with('success', "Usuario '{$user->name}' removido de la lista de permitidos");
            } else {
                return back()->with('info', 'El usuario no estaba en la lista de permitidos');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error removiendo usuario: ' . $e->getMessage());
        }
    }

    /**
     * Permitir acceso desde una IP
     */
    public function allowIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|ip'
        ]);

        try {
            $allowedIps = Cache::get('maintenance_allowed_ips', []);

            if (!in_array($request->ip, $allowedIps)) {
                $allowedIps[] = $request->ip;
                Cache::put('maintenance_allowed_ips', $allowedIps, now()->addDays(30));

                return back()->with('success', "IP '{$request->ip}' agregada a la lista de permitidas");
            } else {
                return back()->with('info', 'La IP ya está en la lista de permitidas');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error agregando IP: ' . $e->getMessage());
        }
    }

    /**
     * Remover IP de la lista de permitidas
     */
    public function removeIp(Request $request)
    {
        $request->validate([
            'ip' => 'required|string'
        ]);

        try {
            $allowedIps = Cache::get('maintenance_allowed_ips', []);
            $key = array_search($request->ip, $allowedIps);

            if ($key !== false) {
                unset($allowedIps[$key]);
                Cache::put('maintenance_allowed_ips', array_values($allowedIps), now()->addDays(30));

                return back()->with('success', "IP '{$request->ip}' removida de la lista de permitidas");
            } else {
                return back()->with('info', 'La IP no estaba en la lista de permitidas');
            }

        } catch (\Exception $e) {
            return back()->with('error', 'Error removiendo IP: ' . $e->getMessage());
        }
    }

    /**
     * Limpiar toda la configuración de mantenimiento
     */
    public function clear()
    {
        try {
            Cache::forget('maintenance_mode');
            Cache::forget('maintenance_retry_after');
            Cache::forget('maintenance_message');
            Cache::forget('maintenance_contact_info');
            Cache::forget('maintenance_allowed_users');
            Cache::forget('maintenance_allowed_ips');

            return back()->with('success', 'Toda la configuración de mantenimiento ha sido eliminada');

        } catch (\Exception $e) {
            return back()->with('error', 'Error limpiando configuración: ' . $e->getMessage());
        }
    }

    /**
     * Obtener estado del modo mantenimiento (API)
     */
    public function status()
    {
        $isActive = Cache::get('maintenance_mode', false);

        $status = [
            'active' => $isActive,
            'retry_after' => Cache::get('maintenance_retry_after', 3600),
            'message' => Cache::get('maintenance_message'),
            'contact_info' => Cache::get('maintenance_contact_info', [])
        ];

        return response()->json($status);
    }

    /**
     * Buscar usuarios para agregar a la lista de permitidos
     */
    public function searchUsers(Request $request)
    {
        $query = $request->get('q');

        if (strlen($query) < 2) {
            return response()->json([]);
        }

        $users = User::where('name', 'like', "%{$query}%")
            ->orWhere('email', 'like', "%{$query}%")
            ->limit(10)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }
}
