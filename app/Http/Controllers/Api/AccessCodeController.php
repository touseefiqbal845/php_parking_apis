<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AccessCode;
use App\Models\LotNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class AccessCodeController extends Controller
{
    public function index(Request $request)
    {
        $query = AccessCode::with('lotNumber');

        // Filter by lot number
        if ($request->has('lot_number_id')) {
            $query->where('lot_number_id', $request->lot_number_id);
        }

        // Search by access code
        if ($request->has('search')) {
            $query->where('access_code', 'LIKE', "%{$request->search}%");
        }

        // Filter by active status
        if ($request->has('is_active')) {
            $query->where('is_active', $request->boolean('is_active'));
        }

        return response()->json($query->paginate($request->get('per_page', 10)));
    }

    public function bulkStore(Request $request)
    {
        // Validate the incoming request data
        $validator = Validator::make($request->all(), [
            'lot_number_id' => 'required|exists:lot_numbers,id',
            'permits_per_month' => 'required|integer|min:1',
            'duration' => 'required|string',
            'accessCodes' => 'required|array', // Ensure accessCodes is an array
            'accessCodes.*' => 'string|distinct' // Each access code must be a unique string
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Extract and trim access codes
        $accessCodes = array_map('trim', $request->accessCodes);

        // Validate uniqueness of access codes in the database
        $existingCodes = AccessCode::whereIn('access_code', $accessCodes)->count();
        if ($existingCodes > 0) {
            return response()->json([
                'errors' => ['accessCodes' => ['Some access codes already exist in the system.']]
            ], 422);
        }

        try {
            DB::beginTransaction();

            $createdCodes = [];
            foreach ($accessCodes as $code) {
                $createdCodes[] = AccessCode::create([
                    'lot_number_id' => $request->lot_number_id,
                    'access_code' => $code,
                    'permits_per_month' => $request->permits_per_month,
                    'duration' => $request->duration,
                    'is_active' => true
                ]);
            }

            DB::commit();
            return response()->json($createdCodes, 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create access codes'], 500);
        }
    }

    public function show(AccessCode $accessCode)
    {
        return response()->json($accessCode->load('lotNumber'));
    }

    public function update(Request $request, AccessCode $accessCode)
    {
        $validator = Validator::make($request->all(), [
            'permits_per_month' => 'sometimes|required|integer|min:1',
            'duration' => 'sometimes|required|string',
            'is_active' => 'sometimes|required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $accessCode->update($request->all());
        return response()->json($accessCode->load('lotNumber'));
    }

    public function destroy(Request $request, $id)
    {
        // $validator = Validator::make($request->all(), [
        //     'ids' => 'required|array',
        //     'ids.*' => 'exists:access_codes,id'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        // AccessCode::whereIn('id', $request->ids)->delete();
        AccessCode::find($id)->delete();
        return response()->json(['message' => 'Access codes deleted successfully']);
    }

    public function toggleActive(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array',
            'ids.*' => 'exists:access_codes,id',
            'is_active' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        AccessCode::whereIn('id', $request->ids)
            ->update(['is_active' => $request->is_active]);

        return response()->json(['message' => 'Access codes updated successfully']);
    }
}
