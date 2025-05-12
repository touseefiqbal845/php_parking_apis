<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use App\Models\LotNumber;
use App\Models\AccessCode;
use App\Models\VehicleManagement;
use Carbon\Carbon;

class RegistrationController extends Controller
{
    /**
     * User Login (Passport Authentication)
     */
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string|min:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation error',
                'errors' => $validator->errors(),
            ], 422);
        }

        // Find user by email or username
        $user = User::where('email', $request->username)
                    ->orWhere('username', $request->username)
                    ->first();

        if (!$user || !Auth::attempt(['email' => $user->email, 'password' => $request->password])) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials',
            ], 401);
        }

        // Generate Passport access token
        $tokenResult = $user->createToken('Personal Access Token');
        $token = $tokenResult->accessToken;

        return response()->json([
            'success' => true,
            'message' => 'Login successful',
            'user' => $user,
            'access_token' => $token,
            'token_type' => 'Bearer',
        ]);
    }

    /**
     * Register a Vehicle Permit
     */
    public function registerPermit(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lot_code' => 'required|string',
            'license_plate' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        // Find Lot Number ID using Lot Code or Access Code
        $lot = LotNumber::where('lot_code', $request->lot_code)->first();

        if (!$lot) {
            // If lot code not found in lot_numbers, check access_codes
            $accessCode = AccessCode::where('access_code', $request->lot_code)->first();

            if (!$accessCode) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid lot code or access code'
                ], 404);
            }

            // Assign lot based on access code
            $lot = LotNumber::find($accessCode->lot_number_id);
        }

        // Validate lot status (if needed)
        if ($lot->status === 'FreePaid') {
            return response()->json([
                'success' => false,
                'message' => 'This lot requires payment. Please contact management.'
            ], 403);
        }

        // Get all non-active records with matching license plate and lot
        $vehicles = VehicleManagement::where('license_plate', $request->license_plate)
            ->where('lot_number_id', $lot->id)
            ->where('is_active', false)
            ->get();

        // Calculate duration for each record
        $vehicles = $vehicles->map(function ($vehicle) {
            $startDate = $vehicle->start_date;
            $endDate = $vehicle->end_date;

            $durationInHours = 0;
            if ($startDate && $endDate) {
                $durationInHours = $startDate->diffInHours($endDate);
            }

            return [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'lot_number_id' => $vehicle->lot_number_id,
                'lot_name' => $vehicle->lotNumber->name,
                'start_date' => $startDate?->format('Y-m-d H:i:s'),
                'end_date' => $endDate?->format('Y-m-d H:i:s'),
                'duration_hours' => $durationInHours,
                'permit_id' => $vehicle->permit_id,
                'status' => $vehicle->status,
                'notes' => $vehicle->notes
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Vehicle permit registered successfully!',
            'permit' => $vehicles
        ]);
        // Prevent permit overlap for the same plate
        // $existingPermit = VehicleManagement::where('license_plate', $request->license_plate)
        //     ->where('lot_number_id', $lot->id)
        //     ->where('end_date', '>=', now())
        //     ->first();

        // if ($existingPermit) {
        //     return response()->json([
        //         'success' => false,
        //         'message' => 'This vehicle already has an active permit.'
        //     ], 409);
        // }

        // // Get default duration from lot or assign 1-day permit
        // $defaultDuration = $lot->duration ?? '1 Day';

        // // Calculate end date
        // $startDate = now();
        // switch ($defaultDuration) {
        //     case '1 Day':  $endDate = $startDate->addDay(); break;
        //     case '7 Days': $endDate = $startDate->addDays(7); break;
        //     case '1 Month': $endDate = $startDate->addMonth(); break;
        //     case '1 Year': $endDate = $startDate->addYear(); break;
        //     case '5 Years': $endDate = $startDate->addYears(5); break;
        //     default: $endDate = $startDate->addDay(); break;
        // }

        // // Create vehicle permit
        // $permit = new VehicleManagement();
        // $permit->lot_number_id = $lot->id;
        // $permit->license_plate = strtoupper($request->license_plate);
        // $permit->start_date = $startDate;
        // $permit->end_date = $endDate;
        // $permit->duration_type = $defaultDuration;
        // $permit->is_active = 1;
        // $permit->status = 'Visitor';
        // $permit->save();

        // return response()->json([
        //     'success' => true,
        //     'message' => 'Vehicle permit registered successfully!',
        //     'permit' => $permit
        // ]);
    }

    /**
     * Register a Vehicle Permit
     */
    public function registerPermitProcess(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'vehicle_management_id' => 'required|exists:vehicle_management,id',
            'start_date' => 'required|date_format:Y-m-d',
            'start_time' => 'required|date_format:H:i:s',
            'duration_hours' => 'required|numeric|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the vehicle record
        $vehicle = VehicleManagement::findOrFail($request->vehicle_management_id);

        // Set start datetime
        $startDatetime = \Carbon\Carbon::createFromFormat(
            'Y-m-d H:i:s',
            $request->start_date . ' ' . $request->start_time
        );

        // Calculate end datetime
        $endDatetime = $startDatetime->copy()->addHours($request->duration_hours);

        // Update vehicle record
        $vehicle->start_date = $startDatetime;
        $vehicle->end_date = $endDatetime;
        $vehicle->is_active = true;
        $vehicle->save();

        return response()->json([
            'message' => 'Vehicle record activated successfully',
            'data' => [
                'id' => $vehicle->id,
                'license_plate' => $vehicle->license_plate,
                'lot_name' => $vehicle->lotNumber->lot_code,
                'start_date' => $vehicle->start_date->format('Y-m-d H:i:s'),
                'end_date' => $vehicle->end_date->format('Y-m-d H:i:s'),
                'duration_hours' => $vehicle->start_date->diffInHours($vehicle->end_date),
                'is_active' => $vehicle->is_active
            ]
        ]);
    }


    public function shareVehicleInfo(Request $request)
    {
        // Validate the request
        $validator = Validator::make($request->all(), [
            'vehicle_id' => 'required|exists:vehicle_management,id',
            'email' => 'required|email',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Find the vehicle record
        $vehicle = VehicleManagement::with('lotNumber')->findOrFail($request->vehicle_id);

        // Get formatted dates for display
        $startDate = $vehicle->start_date ? $vehicle->start_date->format('Y-m-d H:i:s') : 'Not set';
        $endDate = $vehicle->end_date ? $vehicle->end_date->format('Y-m-d H:i:s') : 'Not set';

        // Calculate duration if both dates exist
        $durationHours = null;
        if ($vehicle->start_date && $vehicle->end_date) {
            $durationHours = $vehicle->start_date->diffInHours($vehicle->end_date);
        }

        // Send email with vehicle information
        try {
            Mail::send('emails.vehicle-info', [
                'license_plate' => $vehicle->license_plate,
                'lot_name' => $vehicle->lotNumber->lot_code,
                'start_date' => $startDate,
                'end_date' => $endDate,
                'duration_hours' => $durationHours,
                'permit_id' => $vehicle->permit_id,
                'status' => $vehicle->status,
                'is_active' => $vehicle->is_active ? 'Yes' : 'No',
                'notes' => $vehicle->notes
            ], function ($message) use ($request, $vehicle) {
                $message->to($request->email)
                        ->subject('Vehicle Information - ' . $vehicle->license_plate);
            });

            return response()->json([
                'message' => 'Vehicle information has been shared successfully via email',
                'email' => $request->email
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to send email',
                'details' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get Vehicle Permit History
     */
    public function vehicleHistory(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lot_code' => 'required|string|exists:lot_numbers,lot_code',
            'license_plate' => 'required|string|max:10',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        $history = VehicleManagement::where('license_plate', $request->license_plate)
            ->whereHas('lotNumber', function ($query) use ($request) {
                $query->where('lot_code', $request->lot_code);
            })
            ->orderBy('start_date', 'desc')
            ->limit(30)
            ->get();

        return response()->json([
            'success' => true,
            'history' => $history
        ]);
    }
}
