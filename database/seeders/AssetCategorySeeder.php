<?php

namespace Database\Seeders;

use App\Models\AssetCategory;
use Illuminate\Database\Seeder;

class AssetCategorySeeder extends Seeder
{
    public function run(): void
    {
        $categories = [
            [
                'name'                   => 'Furniture & Fixtures',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 10,
                'useful_life_years'      => 10,
                'residual_value_percent' => 10,
            ],
            [
                'name'                   => 'Computers & IT Equipment',
                'depreciation_method'    => 'wdv',
                'depreciation_rate'      => 40,
                'useful_life_years'      => 3,
                'residual_value_percent' => 5,
            ],
            [
                'name'                   => 'Lab Equipment',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 15,
                'useful_life_years'      => 7,
                'residual_value_percent' => 10,
            ],
            [
                'name'                   => 'Sports Equipment',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 20,
                'useful_life_years'      => 5,
                'residual_value_percent' => 0,
            ],
            [
                'name'                   => 'Vehicles',
                'depreciation_method'    => 'wdv',
                'depreciation_rate'      => 20,
                'useful_life_years'      => 5,
                'residual_value_percent' => 15,
            ],
            [
                'name'                   => 'Musical Instruments',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 15,
                'useful_life_years'      => 7,
                'residual_value_percent' => 10,
            ],
            [
                'name'                   => 'Audio Visual Equipment',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 25,
                'useful_life_years'      => 4,
                'residual_value_percent' => 5,
            ],
            [
                'name'                   => 'Building & Infrastructure',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 5,
                'useful_life_years'      => 20,
                'residual_value_percent' => 20,
            ],
            [
                'name'                   => 'Library Books & Resources',
                'depreciation_method'    => 'slm',
                'depreciation_rate'      => 20,
                'useful_life_years'      => 5,
                'residual_value_percent' => 0,
            ],
            [
                'name'                   => 'Land',
                'depreciation_method'    => 'none',
                'depreciation_rate'      => 0,
                'useful_life_years'      => 100,
                'residual_value_percent' => 100,
            ],
        ];

        foreach ($categories as $data) {
            AssetCategory::firstOrCreate(
                ['name' => $data['name']],
                array_merge($data, ['status' => true])
            );
        }
    }
}