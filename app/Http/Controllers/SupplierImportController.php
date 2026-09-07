<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class SupplierImportController extends Controller
{
    /**
     * Show the import page.
     */
    public function index()
    {
        return view('suppliers.import');
    }

    /**
     * Process a CSV upload and return a preview of rows to be imported.
     * POST /suppliers/import/preview
     */
    public function preview(Request $request): JsonResponse
    {
        $request->validate([
            'csv_file' => ['required', 'file', 'mimes:csv,txt', 'max:5120'],
        ]);

        $file = $request->file('csv_file');
        $rows = [];
        $handle = fopen($file->getPathname(), 'r');

        if ($handle === false) {
            return response()->json(['success' => false, 'message' => 'Could not read CSV file.'], 422);
        }

        // Read header row
        $headers = fgetcsv($handle);
        if ($headers === false) {
            fclose($handle);
            return response()->json(['success' => false, 'message' => 'CSV file is empty.'], 422);
        }

        $headers = array_map('trim', array_map('strtolower', $headers));

        // Map common header variations to our fields
        $fieldMap = [
            'name' => 'name',
            'supplier' => 'name',
            'supplier name' => 'name',
            'contact_person' => 'contact_person',
            'contact person' => 'contact_person',
            'contact_number' => 'contact_number',
            'contact number' => 'contact_number',
            'phone' => 'contact_number',
            'address' => 'address',
            'landmark' => 'landmark',
            'notes' => 'notes',
            'latitude' => 'latitude',
            'lat' => 'latitude',
            'longitude' => 'longitude',
            'lng' => 'longitude',
            'lon' => 'longitude',
        ];

        $mappedHeaders = [];
        foreach ($headers as $i => $h) {
            $mappedHeaders[$i] = $fieldMap[$h] ?? $h;
        }

        $rowNum = 0;
        while (($data = fgetcsv($handle)) !== false) {
            $rowNum++;
            if (count($data) < count($headers)) {
                $data = array_pad($data, count($headers), '');
            }

            $row = ['row' => $rowNum, 'errors' => []];
            foreach ($mappedHeaders as $i => $field) {
                $row[$field] = trim($data[$i] ?? '');
            }

            // Validate required fields
            if (empty($row['name'])) {
                $row['errors'][] = 'Name is required';
            }

            // Mark if geocoding is needed
            $row['needs_geocode'] = empty($row['latitude']) || empty($row['longitude']);

            $rows[] = $row;
        }

        fclose($handle);

        return response()->json([
            'success' => true,
            'headers' => $headers,
            'rows' => $rows,
            'total' => count($rows),
        ]);
    }

    /**
     * Import suppliers from a confirmed preview.
     * POST /suppliers/import/execute
     */
    public function execute(Request $request): JsonResponse
    {
        $request->validate([
            'rows' => ['required', 'array', 'min:1'],
            'geocode_missing' => ['nullable', 'boolean'],
        ]);

        $rows = $request->input('rows');
        $geocodeMissing = $request->boolean('geocode_missing', true);
        $results = [];
        $created = 0;
        $errors = 0;

        foreach ($rows as $row) {
            $name = $row['name'] ?? '';
            if (empty($name)) {
                $results[] = ['name' => '(empty)', 'status' => 'skipped', 'reason' => 'No name'];
                $errors++;
                continue;
            }

            // Check for duplicate
            if (Supplier::where('name', $name)->exists()) {
                $results[] = ['name' => $name, 'status' => 'skipped', 'reason' => 'Already exists'];
                $errors++;
                continue;
            }

            $supplierData = [
                'name' => $name,
                'contact_person' => $row['contact_person'] ?? null,
                'contact_number' => $row['contact_number'] ?? null,
                'address' => $row['address'] ?? null,
                'landmark' => $row['landmark'] ?? null,
                'notes' => $row['notes'] ?? null,
                'latitude' => !empty($row['latitude']) ? (float) $row['latitude'] : null,
                'longitude' => !empty($row['longitude']) ? (float) $row['longitude'] : null,
                'is_active' => true,
            ];

            // Auto-geocode if missing coordinates
            if ($geocodeMissing && ($supplierData['latitude'] === null || $supplierData['longitude'] === null)) {
                $address = $supplierData['address'] ?? $supplierData['landmark'] ?? $supplierData['name'];
                $coords = $this->nominatimLookup($address);
                if ($coords) {
                    $supplierData['latitude'] = $coords['lat'];
                    $supplierData['longitude'] = $coords['lng'];
                }
                // Rate limit: 1 req/sec
                usleep(1100000);
            }

            Supplier::create($supplierData);
            $results[] = ['name' => $name, 'status' => 'created'];
            $created++;
        }

        return response()->json([
            'success' => true,
            'created' => $created,
            'errors' => $errors,
            'results' => $results,
        ]);
    }

    /**
     * Nominatim geocoding lookup (same as GeocodingController).
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
            \Log::warning('Geocoding failed for import: ' . $address, ['error' => $e->getMessage()]);
        }

        return null;
    }
}
