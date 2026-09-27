<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentVideosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_content_videos')->delete();

        \DB::table('lara_content_videos')->insert([
            0 => [
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => null,
                'title' => 'New York City #1',
                'slug' => 'new-york-city-1',
                'slug_lock' => 0,
                'lead' => null,
                'body' => null,
                'created_at' => '2025-08-29 14:03:19',
                'updated_at' => '2025-08-29 14:04:39',
                'deleted_at' => null,
                'publish' => 1,
                'publish_from' => '2025-08-29 14:03:00',
                'publish_expire' => 0,
                'publish_to' => null,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => null,
                'locked_at' => null,
                'locked_by' => null,
            ],
            1 => [
                'id' => 2,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => null,
                'title' => 'New York City #2',
                'slug' => 'new-york-city-2',
                'slug_lock' => 0,
                'lead' => null,
                'body' => null,
                'created_at' => '2025-08-29 14:03:19',
                'updated_at' => '2025-09-29 14:21:58',
                'deleted_at' => null,
                'publish' => 1,
                'publish_from' => '2025-08-29 14:03:00',
                'publish_expire' => 0,
                'publish_to' => null,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => null,
                'locked_at' => null,
                'locked_by' => null,
            ],
            2 => [
                'id' => 3,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => null,
                'title' => 'New York City #3',
                'slug' => 'new-york-city-3',
                'slug_lock' => 0,
                'lead' => null,
                'body' => null,
                'created_at' => '2025-08-29 14:03:19',
                'updated_at' => '2025-08-29 14:06:37',
                'deleted_at' => null,
                'publish' => 1,
                'publish_from' => '2025-08-29 14:03:00',
                'publish_expire' => 0,
                'publish_to' => null,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => null,
                'locked_at' => null,
                'locked_by' => null,
            ],
        ]);

    }
}
