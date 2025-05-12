<?php

namespace Database\Seeders;

use App\Models\Property;
use Illuminate\Database\Seeder;

class PropertiesTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $properties = [
            '1CA', '1704V', '2243E', '219R', '25D', '2600J', '3251B', '6161B'
        ];

        foreach ($properties as $code) {
            Property::create(['code' => $code]);
        }
    }
}
