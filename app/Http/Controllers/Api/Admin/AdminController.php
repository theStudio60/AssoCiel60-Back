<?php
namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AdminController extends Controller
{
    /**
     * Liste des admins
     */
    public function index(Request $request)
    {
        try {
            $query = User::where('role', 'admin')
                ->orderBy('created_at', 'desc');
            
            // Recherche
            if ($request->search) {
                $query->where(function($q) use ($request) {
                    $q->where('first_name', 'like', "%{$request->search}%")
                      ->orWhere('last_name', 'like', "%{$request->search}%")
                      ->orWhere('email', 'like', "%{$request->search}%");
                });
            }
            
            $admins = $query->paginate(10);
            
            return response()->json([
                'success' => true,
                'admins' => $admins
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Créer un admin
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'phone' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            // Nettoyer le téléphone (convertir string vide en null)
            $phoneValue = !empty($request->phone) ? $request->phone : null;
            
            $admin = User::create([
                'organization_id' => 1,
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'phone' => $phoneValue,
                'role' => 'admin',
                'email_verified_at' => now(),
            ]);
            
            ActivityLog::log(
                'admin_created',
                "Nouvel admin créé: {$admin->first_name} {$admin->last_name}",
                User::class,
                $admin->id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Administrateur créé avec succès',
                'admin' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Mettre à jour un admin
     */
    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'nullable|string',
        ]);
        
        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }
        
        try {
            $admin = User::findOrFail($id);
            
            // Ne pas permettre de modifier son propre compte
            if ($admin->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas modifier votre propre compte ici'
                ], 403);
            }
            
            // Nettoyer le téléphone (convertir string vide en null)
            $phoneValue = !empty($request->phone) ? $request->phone : null;
            
            $admin->update([
                'first_name' => $request->first_name,
                'last_name' => $request->last_name,
                'email' => $request->email,
                'phone' => $phoneValue, // ← Corrigé : utilise la valeur nettoyée
            ]);
            
            ActivityLog::log(
                'admin_updated',
                "Admin modifié: {$admin->first_name} {$admin->last_name}",
                User::class,
                $admin->id
            );
            
            return response()->json([
                'success' => true,
                'message' => 'Administrateur modifié avec succès',
                'admin' => $admin
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Supprimer un admin
     */
    public function destroy($id)
    {
        try {
            $admin = User::findOrFail($id);
            
            // Ne pas permettre de supprimer son propre compte
            if ($admin->id === auth()->id()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Vous ne pouvez pas supprimer votre propre compte'
                ], 403);
            }
            
            // Ne pas permettre de supprimer s'il reste qu'un seul admin
            $adminCount = User::where('role', 'admin')->count();
            if ($adminCount <= 1) {
                return response()->json([
                    'success' => false,
                    'message' => 'Impossible de supprimer le dernier administrateur'
                ], 403);
            }
            
            ActivityLog::log(
                'admin_deleted',
                "Admin supprimé: {$admin->first_name} {$admin->last_name}",
                User::class,
                $admin->id
            );
            
            $admin->delete();
            
            return response()->json([
                'success' => true,
                'message' => 'Administrateur supprimé avec succès'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Statistiques
     */
    public function stats()
    {
        try {
            $total = User::where('role', 'admin')->count();
            $active_today = User::where('role', 'admin')
                ->whereDate('last_login', today())
                ->count();
            $created_this_month = User::where('role', 'admin')
                ->whereMonth('created_at', now()->month)
                ->whereYear('created_at', now()->year)
                ->count();
            
            return response()->json([
                'success' => true,
                'stats' => [
                    'total' => $total,
                    'active_today' => $active_today,
                    'created_this_month' => $created_this_month,
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 500);
        }
    }
}