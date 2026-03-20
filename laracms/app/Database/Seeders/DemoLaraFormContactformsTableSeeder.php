<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraFormContactformsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_form_contactforms')->delete();
        
        \DB::table('lara_form_contactforms')->insert(array (
            0 => 
            array (
                'id' => 25,
                'comment' => NULL,
                'telephone' => '0651392621',
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2025-09-05 09:30:16',
                'updated_at' => '2025-09-05 09:30:16',
                'deleted_at' => NULL,
                'ipaddress' => '127.0.0.1',
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
        ));
        
        
    }
}