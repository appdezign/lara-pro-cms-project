<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectPageablesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_object_pageables')->delete();

        \DB::table('lara_object_pageables')->insert([
            0 => [
                'id' => 24,
                'page_id' => 2,
                'entity_type' => 'Lara\\Common\\Models\\Widget',
                'entity_id' => 2,
            ],
            1 => [
                'id' => 25,
                'page_id' => 17,
                'entity_type' => 'Lara\\Common\\Models\\Widget',
                'entity_id' => 2,
            ],
            2 => [
                'id' => 28,
                'page_id' => 5,
                'entity_type' => 'Lara\\Common\\Models\\Widget',
                'entity_id' => 1,
            ],
            3 => [
                'id' => 29,
                'page_id' => 5,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 3,
            ],
            4 => [
                'id' => 30,
                'page_id' => 5,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 4,
            ],
            5 => [
                'id' => 31,
                'page_id' => 5,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 5,
            ],
            6 => [
                'id' => 32,
                'page_id' => 5,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 6,
            ],
            7 => [
                'id' => 34,
                'page_id' => 2,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 2,
            ],
            8 => [
                'id' => 35,
                'page_id' => 14,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 1,
            ],
            9 => [
                'id' => 36,
                'page_id' => 2,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 12,
            ],
            10 => [
                'id' => 37,
                'page_id' => 2,
                'entity_type' => 'Lara\\Common\\Models\\LaraWidget',
                'entity_id' => 13,
            ],
        ]);

    }
}
