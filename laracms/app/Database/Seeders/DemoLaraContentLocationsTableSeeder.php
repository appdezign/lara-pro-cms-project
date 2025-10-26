<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentLocationsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_content_locations')->delete();
        
        \DB::table('lara_content_locations')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Marathon Music Works',
                'slug' => 'marathon-music-works',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'geo_location' => 'auto',
                'geo_country' => 'United States',
                'geo_city' => 'Nashville, TN',
                'geo_pcode' => '37203',
                'geo_address' => '1402 Clinton St',
                'geo_longitude' => '-86.79707840',
                'geo_latitude' => '36.16409770',
                'created_at' => '2025-07-04 17:37:33',
                'updated_at' => '2025-10-06 13:49:57',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-07-04 17:37:24',
                'publish_expire' => 0,
                'publish_to' => NULL,
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
                'title' => 'NDSM Loods',
                'slug' => 'ndsm-loods',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'geo_location' => 'auto',
                'geo_country' => 'Nederland',
                'geo_city' => 'Amsterdam',
                'geo_pcode' => '1033 WC',
                'geo_address' => 'NDSM-Plein 85',
                'geo_longitude' => '4.89572210',
                'geo_latitude' => '52.40120080',
                'created_at' => '2025-07-09 13:15:22',
                'updated_at' => '2025-09-29 18:33:38',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-07-09 13:15:01',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Firmaq West HQ',
                'slug' => 'firmaq-west-hq',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'geo_location' => 'auto',
                'geo_country' => 'Netherlands',
                'geo_city' => 'Haarlem',
                'geo_pcode' => '2012BX',
                'geo_address' => 'Bellevuelaan 241',
                'geo_longitude' => '4.63278940',
                'geo_latitude' => '52.37070970',
                'created_at' => '2025-07-09 13:16:34',
                'updated_at' => '2025-10-06 13:48:21',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-07-09 13:16:11',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Firmaq Noord HQ',
                'slug' => 'firmaq-noord-hq',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'geo_location' => 'auto',
                'geo_country' => 'Nederland',
                'geo_city' => 'Heerenveen',
                'geo_pcode' => '8448 RT',
                'geo_address' => 'Yme Kuiperweg 24',
                'geo_longitude' => '5.94465530',
                'geo_latitude' => '52.96272830',
                'created_at' => '2025-07-09 13:17:33',
                'updated_at' => '2025-10-06 13:49:13',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-07-09 13:17:20',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
            ),
        ));
        
        
    }
}