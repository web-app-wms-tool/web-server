<?php

namespace Database\Seeders;

use App\Models\Srs;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SrsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Srs::truncate();
        DB::statement("INSERT INTO srs(name,code,description) select concat(spatial_ref_sys.auth_name,':',spatial_ref_sys.auth_srid) as name, spatial_ref_sys.auth_srid as code,concat(spatial_ref_sys.auth_name,':',spatial_ref_sys.auth_srid) as description from spatial_ref_sys");
    }
}
