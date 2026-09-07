<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class GeocodingController extends Controller
{
    /**
     * Geocode a single address string into lat/lng via Nominatim.
     */
    public function geocodeAddress(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'address' => ['required', 'string', 'max:500'],
        ]);

        $address = $validated['address'];
        $coords = $this->nominatimLookup($address);

        if ($coords === null) {
            return response()->json([
                'success' => false,
                'message' => 'Could not geocode address: ' . $address,
            ], 422);
        }

        return response()->json([
            'success' => true,
            'latitude' => $coords['lat'],
            'longitude' => $coords['lng'],
        ]);
    }

    /**
     * Batch-geocode all branches that are missing coordinates.
     */
    public function geocodeBranches(): JsonResponse
    {
        $branches = Branch::whereNull('latitude')
            ->orWhereNull('longitude')
            ->get();

        $results = [];
        $updated = 0;

        foreach ($branches as $index => $branch) {
            // Rate limit: Nominatim requires max 1 request/sec
            if ($index > 0) { usleep(1100000); }

            $address = $branch->location ?? $branch->name;
            $coords = $this->nominatimLookup($address);

            if ($coords) {
                $branch->update([
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ]);
                $results[] = [
                    'name' => $branch->name,
                    'status' => 'success',
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ];
                $updated++;
            } else {
                $results[] = [
                    'name' => $branch->name,
                    'status' => 'failed',
                    'address' => $address,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'total' => $branches->count(),
            'results' => $results,
        ]);
    }

    /**
     * Batch-geocode all suppliers that are missing coordinates.
     */
    public function geocodeSuppliers(): JsonResponse
    {
        $suppliers = Supplier::whereNull('latitude')
            ->orWhereNull('longitude')
            ->get();

        $results = [];
        $updated = 0;

        foreach ($suppliers as $index => $supplier) {
            // Rate limit: Nominatim requires max 1 request/sec
            if ($index > 0) { usleep(1100000); }

            $address = $supplier->address ?? $supplier->landmark ?? $supplier->name;
            $coords = $this->nominatimLookup($address);

            if ($coords) {
                $supplier->update([
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ]);
                $results[] = [
                    'name' => $supplier->name,
                    'status' => 'success',
                    'latitude' => $coords['lat'],
                    'longitude' => $coords['lng'],
                ];
                $updated++;
            } else {
                $results[] = [
                    'name' => $supplier->name,
                    'status' => 'failed',
                    'address' => $address,
                ];
            }
        }

        return response()->json([
            'success' => true,
            'updated' => $updated,
            'total' => $suppliers->count(),
            'results' => $results,
        ]);
    }

    /**
     * Call Nominatim API to geocode an address.
     * Returns ['lat' => float, 'lng' => float] or null on failure.
     * Respects Nominatum usage policy: max 1 request/sec, custom User-Agent.
     */
    private function nominatimLookup(string $address): ?array
    {
        try {
            $response = Http::withHeaders([
                'User-Agent' => 'InvenTrack/1.0 (inventory-tracker)',
            ])->timeout(10)->get('https://nominatim.openstreetmap.org/search', [
                'q' => $address . ', Philippines',
                'format' => 'json',
                'limit' => 1,
                'addressdetails' => 0,
            ]);

            if ($response->successful() && count($response->json()) > 0) {
                $result = $response->json()[0];
                return [
                    'lat' => (float) $result['lat'],
                    'lng' => (float) $result['lon'],
                ];
            }
        } catch (\Exception $e) {
            // Log and return null — don't crash the batch
            \Log::warning('Geocoding failed for: ' . $address, ['error' => $e->getMessage()]);
        }

        return null;
    }
}
