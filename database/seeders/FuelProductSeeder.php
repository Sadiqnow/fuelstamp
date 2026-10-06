<?php

namespace Database\Seeders;

use App\Models\FuelProduct;
use Illuminate\Database\Seeder;

class FuelProductSeeder extends Seeder
{
    public function run(): void
    {
        $products = [
            [
                'code' => 'PMS',
                'name' => 'Premium Motor Spirit',
            ],
            [
                'code' => 'AGO',
                'name' => 'Automotive Gas Oil',
            ],
            [
                'code' => 'DPK',
                'name' => 'Dual Purpose Kerosene',
            ],
        ];

        foreach ($products as $product) {
            FuelProduct::updateOrCreate(
                [
                    'code' => $product['code'],
                ],
                [
                    'name' => $product['name'],
                    'active' => true,
                ]
            );
        }
    }
}