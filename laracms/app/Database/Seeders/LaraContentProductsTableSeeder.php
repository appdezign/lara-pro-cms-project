<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class LaraContentProductsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_content_products')->delete();
        
        \DB::table('lara_content_products')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'my first product',
                'slug' => 'my-first-product',
                'slug_lock' => 0,
                'lead' => '<p>my lead</p>',
                'body' => '<p>my body text</p>',
                'mycolor' => '#ff0000',
                'myradio' => '"eight"',
                'mycheckbox' => 0,
                'mycheckboxlist' => '["five"]',
                'mymultitogglebuttons' => '["five"]',
                'mytogglebuttons' => 'two',
                'mytoggle' => 1,
                'mytagsinput' => '["one", "two", "three"]',
                'mymultiselect' => '["five", "six"]',
                'myselect_2' => 'three',
                'mydatetime' => '2025-10-30 12:34:00',
                'mytime' => '12:34:00',
                'mydate' => '2025-10-30',
                'mydecimal' => '12.3400',
                'mytextarea' => 'my text area',
                'mynumber' => 456,
                'mytext' => 'my text',
                'myemail' => 'myemail@domain.com',
                'mystring' => 'my string',
                'created_at' => '2025-10-06 18:51:02',
                'updated_at' => '2025-10-21 08:00:14',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-10-06 18:50:00',
                'publish_expire' => 1,
                'publish_to' => '2025-12-31 20:52:00',
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'my second product',
                'slug' => 'my-second-product',
                'slug_lock' => 0,
                'lead' => '<p></p>',
                'body' => '<p></p>',
                'mycolor' => NULL,
                'myradio' => NULL,
                'mycheckbox' => 0,
                'mycheckboxlist' => '[]',
                'mymultitogglebuttons' => '[]',
                'mytogglebuttons' => NULL,
                'mytoggle' => 0,
                'mytagsinput' => '[]',
                'mymultiselect' => '[]',
                'myselect_2' => NULL,
                'mydatetime' => '2025-10-21 08:09:00',
                'mytime' => NULL,
                'mydate' => '2025-10-21',
                'mydecimal' => '0.0000',
                'mytextarea' => NULL,
                'mynumber' => 0,
                'mytext' => NULL,
                'myemail' => NULL,
                'mystring' => NULL,
                'created_at' => '2025-10-21 08:09:22',
                'updated_at' => '2025-10-21 08:09:22',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-10-21 08:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
        ));
        
        
    }
}