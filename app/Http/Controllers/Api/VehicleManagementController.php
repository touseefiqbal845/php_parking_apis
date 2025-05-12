<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\VehicleManagement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class VehicleManagementController extends Controller
{
    public function index(Request $request)
    {
        $query = VehicleManagement::with('lotNumber')->latest();

        // Filter by lot number
        if ($request->has('lot_number_id')) {
            $query->where('lot_number_id', $request->lot_number_id);
        }

        // Filter by status
        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        // Search by license plate or permit ID
        if ($request->has('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('license_plate', 'LIKE', "%{$search}%")
                  ->orWhere('permit_id', 'LIKE', "%{$search}%");
            });
        }

        // Filter by date range
        if ($request->has('date_from')) {
            $query->where('start_date', '>=', $request->date_from);
        }
        if ($request->has('date_to')) {
            $query->where('end_date', '<=', $request->date_to);
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
            'status' => 'required|in:Tenant,Employee,Visitor,Do Not Tag,Other',
            'start_date' => 'required|date',
            'duration_type' => 'required|in:1 Day,7 Days,1 Month,1 Year,5 Years',
            'vehicles' => 'required|array', // Ensure vehicles is an array
            'vehicles.*.license_plate' => 'required|string', // Validate each license plate
            'vehicles.*.permit_id' => 'required|string', // Validate each permit ID
        ]);
        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Calculate end date based on duration type
        $startDate = Carbon::parse($request->start_date);
        $endDate = match ($request->duration_type) {
            '1 Day' => $startDate->copy()->addDay(),
            '7 Days' => $startDate->copy()->addDays(7),
            '1 Month' => $startDate->copy()->addMonth(),
            '1 Year' => $startDate->copy()->addYear(),
            '5 Years' => $startDate->copy()->addYears(5),
        };

        try {
            DB::beginTransaction();

            $createdVehicles = [];
            foreach ($request->vehicles as $vehicle) {
                // Extract license plate and permit ID from the vehicle object
                $licensePlate = $vehicle['license_plate'];
                $permitId = $vehicle['permit_id'];

                // Create a new vehicle management record
                $createdVehicles[] = VehicleManagement::create([
                    'lot_number_id' => $request->lot_number_id,
                    'license_plate' => $licensePlate,
                    'permit_id' => $permitId,
                    'status' => $request->status,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'duration_type' => $request->duration_type,
                    'is_active' => false,
                ]);
            }

            DB::commit();
            return response()->json($createdVehicles, 201);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['error' => 'Failed to create vehicle permits', "erro2" => $e], 500);
        }
    }

    public function show(VehicleManagement $vehicle)
    {
        return response()->json($vehicle->load('lotNumber'));
    }

    public function update(Request $request, VehicleManagement $vehicle)
    {
        $validator = Validator::make($request->all(), [
            'status' => 'sometimes|required|in:Tenant,Employee,Visitor,Do Not Tag,Other',
            'start_date' => 'sometimes|required|date',
            'duration_type' => 'sometimes|required|in:1 Day,7 Days,1 Month,1 Year,5 Years',
            'is_active' => 'sometimes|required|boolean',
            'notes' => 'nullable|string'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // If updating duration, recalculate end_date
        if ($request->has('start_date') || $request->has('duration_type')) {
            $startDate = Carbon::parse($request->start_date ?? $vehicle->start_date);
            $durationType = $request->duration_type ?? $vehicle->duration_type;

            $endDate = match($durationType) {
                '1 Day' => $startDate->copy()->addDay(),
                '7 Days' => $startDate->copy()->addDays(7),
                '1 Month' => $startDate->copy()->addMonth(),
                '1 Year' => $startDate->copy()->addYear(),
                '5 Years' => $startDate->copy()->addYears(5),
            };

            $request->merge(['end_date' => $endDate]);
        }

        $vehicle->update($request->all());
        return response()->json($vehicle->load('lotNumber'));
    }

    public function destroy(Request $request, $id)
    {
        // $validator = Validator::make($request->all(), [
        //     'ids' => 'required|array',
        //     'ids.*' => 'exists:vehicle_managements,id'
        // ]);

        // if ($validator->fails()) {
        //     return response()->json(['errors' => $validator->errors()], 422);
        // }

        // VehicleManagement::whereIn('id', $request->ids)->delete();
        VehicleManagement::find($id)->delete();
        return response()->json(['message' => 'Vehicle permits deleted successfully']);
    }
}
