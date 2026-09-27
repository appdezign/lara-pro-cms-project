<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentGalleriesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_content_galleries')->delete();

        \DB::table('lara_content_galleries')->insert([
            0 => [
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => null,
                'title' => 'Team at work',
                'slug' => 'team-at-work',
                'slug_lock' => 0,
                'lead' => null,
                'body' => null,
                'created_at' => '2025-08-28 14:43:33',
                'updated_at' => '2026-09-12 13:29:09',
                'deleted_at' => null,
                'publish' => 1,
                'publish_from' => '2025-08-28 14:43:00',
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
                'title' => 'New tech',
                'slug' => 'new-tech',
                'slug_lock' => 0,
                'lead' => null,
                'body' => null,
                'created_at' => '2025-08-29 12:32:13',
                'updated_at' => '2026-03-27 09:50:49',
                'deleted_at' => null,
                'publish' => 1,
                'publish_from' => '2025-08-29 12:32:00',
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
