<?php

namespace App\Http\Controllers;

use App\Models\InventoryMovement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InventoryMovementController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAdmin() && ! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $from = $request->get('from');
        $to = $request->get('to');
        $query = InventoryMovement::with('recordedByUser')->orderByDesc('movement_date')->orderByDesc('id');

        if ($from) {
            $query->whereDate('movement_date', '>=', $from);
        }
        if ($to) {
            $query->whereDate('movement_date', '<=', $to);
        }
        if ($request->filled('direction')) {
            $query->where('direction', $request->direction);
        }

        return response()->json(['success' => true, 'data' => $query->get()]);
    }

    public function store(Request $request)
    {
        $user = Auth::user();
        if (! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        return $this->saveMovement($request, null);
    }

    public function update(Request $request, string $id)
    {
        $user = Auth::user();
        if (! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $movement = InventoryMovement::find($id);
        if (! $movement) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }

        return $this->saveMovement($request, $movement);
    }

    public function destroy(string $id)
    {
        $user = Auth::user();
        if (! $user->isAccountant()) {
            return response()->json(['success' => false, 'message' => 'Unauthorized'], 403);
        }

        $movement = InventoryMovement::find($id);
        if (! $movement) {
            return response()->json(['success' => false, 'message' => 'Not found'], 404);
        }
        $movement->delete();

        return response()->json(['success' => true, 'message' => 'Supprimé']);
    }

    private function saveMovement(Request $request, ?InventoryMovement $existing)
    {
        $validator = Validator::make($request->all(), [
            'movement_date' => ($existing ? 'sometimes' : 'required').'|date',
            'direction' => ($existing ? 'sometimes' : 'required').'|string|in:in,out',
            'category' => 'nullable|string|max:64',
            'label' => ($existing ? 'sometimes' : 'required').'|string|max:255',
            'quantity' => 'nullable|numeric|min:0',
            'unit' => 'nullable|string|max:32',
            'total_value' => 'nullable|numeric|min:0',
            'currency' => 'nullable|string|max:8',
            'notes' => 'nullable|string|max:2000',
        ]);
        if ($validator->fails()) {
            return response()->json(['success' => false, 'message' => 'Validation error', 'errors' => $validator->errors()], 422);
        }

        $user = Auth::user();
        $payload = [
            'recorded_by' => $user->id,
            'movement_date' => $request->input('movement_date', $existing?->movement_date?->format('Y-m-d')),
            'direction' => $request->input('direction', $existing?->direction),
            'category' => $request->input('category', $existing?->category ?? 'other'),
            'label' => $request->input('label', $existing?->label),
            'quantity' => $request->input('quantity', $existing?->quantity),
            'unit' => $request->input('unit', $existing?->unit),
            'total_value' => $request->input('total_value', $existing?->total_value ?? 0),
            'currency' => $request->input('currency', $existing?->currency ?? 'TND'),
            'notes' => $request->input('notes', $existing?->notes),
        ];

        if ($existing) {
            $existing->update($payload);
            $movement = $existing->fresh(['recordedByUser']);
        } else {
            $movement = InventoryMovement::create($payload)->load('recordedByUser');
        }

        return response()->json([
            'success' => true,
            'message' => $existing ? 'Mis à jour' : 'Enregistré',
            'data' => $movement,
        ], $existing ? 200 : 201);
    }
}
