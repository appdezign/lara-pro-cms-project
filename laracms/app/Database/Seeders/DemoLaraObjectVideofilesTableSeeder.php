<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectVideofilesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_object_videofiles')->delete();

        \DB::table('lara_object_videofiles')->insert([
            0 => [
                'id' => 18,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 22,
                'entity_videofiles' => '[]',
            ],
            1 => [
                'id' => 19,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 23,
                'entity_videofiles' => '[]',
            ],
            2 => [
                'id' => 20,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 27,
                'entity_videofiles' => '[]',
            ],
            3 => [
                'id' => 21,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 31,
                'entity_videofiles' => '[]',
            ],
            4 => [
                'id' => 22,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 30,
                'entity_videofiles' => '[]',
            ],
            5 => [
                'id' => 23,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 29,
                'entity_videofiles' => '[]',
            ],
            6 => [
                'id' => 24,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 26,
                'entity_videofiles' => '[]',
            ],
            7 => [
                'id' => 25,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 25,
                'entity_videofiles' => '[]',
            ],
            8 => [
                'id' => 26,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 24,
                'entity_videofiles' => '[]',
            ],
            9 => [
                'id' => 27,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 35,
                'entity_videofiles' => '[]',
            ],
            10 => [
                'id' => 28,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 36,
                'entity_videofiles' => '[]',
            ],
            11 => [
                'id' => 29,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 37,
                'entity_videofiles' => '[]',
            ],
            12 => [
                'id' => 30,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 38,
                'entity_videofiles' => '[]',
            ],
            13 => [
                'id' => 31,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 39,
                'entity_videofiles' => '[]',
            ],
            14 => [
                'id' => 32,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 40,
                'entity_videofiles' => '[]',
            ],
        ]);

    }
}
