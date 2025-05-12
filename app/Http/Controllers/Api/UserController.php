<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Property;
use App\Models\LotNumber;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('properties') // Eager load the properties relationship
        ->when($request->search, fn($q) => $q->where('username', 'like', "%{$request->search}%"))
        ->orderBy('username');

        $perPage = $request->per_page === 'all' ? $query->count() : ($request->per_page ?? 10);

        // Paginate the results
        $paginatedUsers = $query->paginate($perPage);

        // Transform the users to include properties as an array of codes/names
        $transformedUsers = $paginatedUsers->getCollection()->map(function ($user) {
            return [
                'id' => $user->id,
                'username' => $user->username,
                'role' => $user->role,
                'note' => $user->note,
                'properties' => $user->properties->pluck('code')->toArray(), // Extract property codes
            ];
        });

        // Replace the collection with the transformed data
        $paginatedUsers->setCollection($transformedUsers);

        return response()->json($paginatedUsers);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'username' => 'required|unique:users|max:255',
            'password' => ['required', Password::defaults()],
            'role' => 'required|in:Admin,Property Manager',
            'note' => 'nullable|string',
            'properties' => 'nullable|array'
        ]);

        $data['password'] = Hash::make($data['password']);
        $data['name'] = $data['username'];
        $user = User::create($data);
        // Process the properties array
            if ($request->has('properties')) {
                $propertyIds = [];

                foreach ($request->properties as $propertyCode) {
                    // Use firstOrCreate to find or create the property by its code
                    $property = Property::firstOrCreate(
                        ['code' => $propertyCode], // Find by the unique property code
                        ['name' => $propertyCode] // Default name if creating a new property
                    );

                    // Collect the property IDs
                    $propertyIds[] = $property->id;
                }

                // Sync the properties with the user
                $user->properties()->sync($propertyIds);
            }

        return response()->json($user->load('properties'), 201);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate([
            'username' => 'required|max:255|unique:users,username,'.$user->id,
            'password' => ['nullable', Password::defaults()],
            'role' => 'required|in:Admin,Property Manager',
            'note' => 'nullable|string',
            'properties' => 'nullable|array'
        ]);

        if ($request->filled('password')) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $user->update($data);
        // Process the properties array
            if ($request->has('properties')) {
                $propertyIds = [];

                foreach ($request->properties as $propertyCode) {
                    // Use firstOrCreate to find or create the property by its code
                    $property = Property::firstOrCreate(
                        ['code' => $propertyCode], // Find by the unique property code
                        ['name' => $propertyCode] // Default name if creating a new property
                    );

                    // Collect the property IDs
                    $propertyIds[] = $property->id;
                }

                // Sync the properties with the user
                $user->properties()->sync($propertyIds);
            }

        return response()->json($user->load('properties'));
    }

    public function destroy(User $user)
    {
        $user->delete();
        return response()->noContent();
    }

    public function bulkDelete(Request $request)
    {
        $request->validate(['ids' => 'required|array']);
        User::whereIn('id', $request->ids)->delete();
        return response()->noContent();
    }


    // Lot controller function 
    
    public function lot_index(Request $request)
    {
        $query = LotNumber::query();

        // Handle search
        if ($request->has('search')) {
            $searchTerm = $request->search;
            $query->where(function($q) use ($searchTerm) {
                $q->where('lot_code', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('address', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('city', 'LIKE', "%{$searchTerm}%")
                  ->orWhere('note', 'LIKE', "%{$searchTerm}%");
            });
        }

        // Handle sorting
        $sortField = $request->get('sort_field', 'lot_code');
        $sortDirection = $request->get('sort_direction', 'asc');
        $query->orderBy($sortField, $sortDirection);

        // Handle pagination
        $perPage = $request->get('per_page', 10);

        return response()->json($query->paginate($perPage));
    }

    public function lot_store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lot_code' => 'required|unique:lot_numbers,lot_code',
            'address' => 'required',
            'city' => 'required',
            'permits_per_month' => 'required_if:status,Free,FreePaid|integer|min:0',
            'duration' => 'required_if:status,Free,FreePaid',
            'status' => 'required|in:Free,FreePaid',
            'note' => 'nullable|string',
            'pricing' => 'required_if:status,FreePaid|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lotNumber = LotNumber::create($request->all());
        return response()->json($lotNumber, 201);
    }

    public function show(LotNumber $lotNumber)
    {
        return response()->json($lotNumber);
    }

    public function lot_update(Request $request, LotNumber $lotNumber)
    {
        $validator = Validator::make($request->all(), [
            'lot_code' => 'required|unique:lot_numbers,lot_code,' . $lotNumber->id,
            'address' => 'required',
            'city' => 'required',
            'permits_per_month' => 'required_if:status,Free,FreePaid|integer|min:0',
            'duration' => 'required_if:status,Free,FreePaid',
            'status' => 'required|in:Free,FreePaid',
            'note' => 'nullable|string',
            'pricing' => 'required_if:status,FreePaid|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $lotNumber->update($request->all());
        return response()->json($lotNumber);
    }

    public function lot_destroy(Request $request, $id)
    {
        // $ids = $request->input('ids', []);
        // LotNumber::whereIn('id', $ids)->delete();
        LotNumber::find($id)->delete();
        return response()->json(['message' => 'Lots deleted successfully']);
    }

    public function lot_export(Request $request)
    {
        $ids = $request->input('ids', []);
        $lots = LotNumber::whereIn('id', $ids)->get();

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="lots_export.csv"',
        ];

        $handle = fopen('php://temp', 'r+');

        // Add headers
        fputcsv($handle, ['Lot Code', 'Address', 'City', 'Permits/Mo', 'Duration', 'Status', 'Note']);

        // Add data
        foreach ($lots as $lot) {
            fputcsv($handle, [
                $lot->lot_code,
                $lot->address,
                $lot->city,
                $lot->permits_per_month,
                $lot->duration,
                $lot->status,
                $lot->note
            ]);
        }

        rewind($handle);
        $content = stream_get_contents($handle);
        fclose($handle);

        return response($content, 200, $headers);
    }
}
