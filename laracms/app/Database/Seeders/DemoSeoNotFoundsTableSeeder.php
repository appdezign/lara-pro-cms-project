<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoSeoNotFoundsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('seo_not_founds')->delete();
        
        
        
    }
}