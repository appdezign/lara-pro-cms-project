<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentPortfoliosTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_content_portfolios')->delete();
        
        \DB::table('lara_content_portfolios')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 1',
                'slug' => 'brand-1',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:16:47',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 2',
                'slug' => 'brand-2',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:16:57',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            2 => 
            array (
                'id' => 3,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 3',
                'slug' => 'brand-3',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:17:13',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            3 => 
            array (
                'id' => 4,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 4',
                'slug' => 'brand-4',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:17:26',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            4 => 
            array (
                'id' => 5,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 5',
                'slug' => 'brand-5',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:18:14',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            5 => 
            array (
                'id' => 6,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 6',
                'slug' => 'brand-6',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:18:25',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
            6 => 
            array (
                'id' => 7,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Brand 7',
                'slug' => 'brand-7',
                'slug_lock' => 0,
                'lead' => NULL,
                'body' => NULL,
                'url' => NULL,
                'created_at' => '2025-08-24 13:10:12',
                'updated_at' => '2025-12-24 12:18:34',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-24 13:09:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => NULL,
                'locked_by' => NULL,
            ),
        ));
        
        
    }
}