<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ProductService;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;

class ProductImportController extends Controller
{
    public function import(Request $request, ProductService $productService)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,csv',
        ]);

        $spreadsheet = IOFactory::load($request->file('file')->getPathname());
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray();

        // Ambil header
        $header = array_map(fn ($h) => strtolower(trim($h)), array_shift($rows));

        $success = 0;
        $errors = [];

        foreach ($rows as $index => $row) {
            try {
                if (count($row) !== count($header)) {
                    throw new \Exception('Jumlah kolom tidak sesuai header');
                }

                $data = array_combine($header, $row);

                // ===== PARSE SIZES =====
                $sizes = [];
                if ($data['stock_type'] === 'ready' && ! empty($data['sizes'])) {
                    foreach (explode('|', $data['sizes']) as $item) {
                        [$size, $stock] = explode(':', $item);
                        $sizes[] = [
                            'size' => trim($size),
                            'stock' => (int) $stock,
                        ];
                    }
                }

                // ===== PARSE COLORS =====
                $colors = ! empty($data['colors'])
                    ? array_map('trim', explode('|', $data['colors']))
                    : [];

                // ===== PAYLOAD KE PRODUCT SERVICE =====
                $payload = [
                    'name' => $data['name'],
                    'description' => $data['description'],
                    'price' => $data['price'],
                    'category_id' => $data['category_id'],
                    'stock_type' => $data['stock_type'],
                    'po_estimate_days' => $data['po_estimate_days'] ?: null,
                    'po_notes' => $data['po_notes'] ?: null,
                    'sizes' => $sizes,
                    'colors' => $colors,
                ];

                // ===== IMAGE VIA URL (OPTIONAL) =====
                $image = null;
                if (! empty($data['image'])) {
                    $image = trim($data['image']); // SIMPAN URL SAJA
                }

                // CREATE PRODUCT (PAKAI LOGIC STORE YANG SAMA)
                $productService->create($payload, $image);

                $success++;

            } catch (\Throwable $e) {
                $errors[] = [
                    'row' => $index + 2, // + header
                    'message' => $e->getMessage(),
                ];
            }
        }

        return response()->json([
            'success' => true,
            'inserted' => $success,
            'errors' => $errors,
        ]);
    }
}
