<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\LotNumber;
use Illuminate\Support\Facades\Validator;

class LotNumberController extends Controller
{
    public function index(Request $request)
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

    public function store(Request $request)
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

    public function update(Request $request, LotNumber $lotNumber)
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

    public function destroy(Request $request, $id)
    {
        // $ids = $request->input('ids', []);
        // LotNumber::whereIn('id', $ids)->delete();
        LotNumber::find($id)->delete();
        return response()->json(['message' => 'Lots deleted successfully']);
    }

    public function export(Request $request)
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
