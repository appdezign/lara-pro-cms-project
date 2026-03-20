<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraFormClassicformsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_form_classicforms')->delete();
        
        \DB::table('lara_form_classicforms')->insert(array (
            0 => 
            array (
                'id' => 6,
                'mydate' => '2025-12-31',
                'myradio' => '1',
                'myselect' => '1',
                'mycomment' => 'test',
                'myboolean' => 1,
                'myemail' => 's.hoeksma@firmaq.nl',
                'myname' => 'Sybrand Hoeksma',
                'created_at' => '2025-12-31 16:12:00',
                'updated_at' => '2025-12-31 16:12:00',
                'deleted_at' => NULL,
                'ipaddress' => '127.0.0.1',
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            1 => 
            array (
                'id' => 7,
                'mydate' => '2025-12-23',
                'myradio' => 'five',
                'myselect' => 'two',
                'mycomment' => 'test 3',
                'myboolean' => 1,
                'myemail' => 's.hoeksma@firmaq.nl',
                'myname' => 'Sybrand Hoeksma',
                'created_at' => '2025-12-31 16:48:05',
                'updated_at' => '2025-12-31 16:48:05',
                'deleted_at' => NULL,
                'ipaddress' => '127.0.0.1',
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            2 => 
            array (
                'id' => 8,
                'mydate' => '2026-01-07',
                'myradio' => 'five',
                'myselect' => 'two',
                'mycomment' => 'sdfg',
                'myboolean' => 1,
                'myemail' => 's.hoeksma@firmaq.nl',
                'myname' => 'Sybrand Hoeksma',
                'created_at' => '2026-01-06 11:47:37',
                'updated_at' => '2026-01-06 11:47:37',
                'deleted_at' => NULL,
                'ipaddress' => '127.0.0.1',
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
        ));
        
        
    }
}