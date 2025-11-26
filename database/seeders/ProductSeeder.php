<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Seeder;

class ProductSeeder extends Seeder
{
    public function run(): void
    {
        $categoryIds = Category::pluck('id');

        foreach (range(1, 10) as $i) {
            Product::create([
                'name' => "Produk Contoh $i",
                'description' => "Deskripsi produk ke-$i",
                'price' => rand(50000, 500000),
                'stock' => rand(5, 50),
                'category_id' => $categoryIds->random(),
            ]);
        }
    }
}
