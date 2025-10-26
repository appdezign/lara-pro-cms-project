<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentCitiesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_content_cities')->delete();
        
        \DB::table('lara_content_cities')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'My first city',
                'slug' => 'my-first-city',
                'slug_lock' => 0,
                'lead' => '<p></p>',
                'body' => '<p></p>',
                'geo_longitude' => '4.63278940',
                'geo_latitude' => '52.37070970',
                'geo_location' => 'auto',
                'geo_country' => 'Netherlands',
                'geo_city' => 'Haarlem',
                'geo_pcode' => '2012BX',
                'geo_address' => 'Bellevuelaan 241',
                'created_at' => '2025-10-21 13:07:08',
                'updated_at' => '2025-10-21 13:15:35',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-10-20 15:11:00',
                'publish_expire' => 1,
                'publish_to' => '2026-12-31 15:12:00',
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
        ));
        
        
    }
}