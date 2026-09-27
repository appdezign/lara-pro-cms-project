<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectVideosTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_object_videos')->delete();

        \DB::table('lara_object_videos')->insert([
            0 => [
                'id' => 38,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 22,
                'entity_videos' => '[]',
            ],
            1 => [
                'id' => 39,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 1,
                'entity_videos' => '[]',
            ],
            2 => [
                'id' => 40,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 2,
                'entity_videos' => '[]',
            ],
            3 => [
                'id' => 41,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 2,
                'entity_videos' => '[{"video_title": "FLYING OVER NEW YORK 4K UHD", "youtubecode": "9R0DvILdHV8"}]',
            ],
            4 => [
                'id' => 42,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 23,
                'entity_videos' => '[]',
            ],
            5 => [
                'id' => 43,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 3,
                'entity_videos' => '[]',
            ],
            6 => [
                'id' => 44,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 4,
                'entity_videos' => '[]',
            ],
            7 => [
                'id' => 45,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 5,
                'entity_videos' => '[]',
            ],
            8 => [
                'id' => 46,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 6,
                'entity_videos' => '[]',
            ],
            9 => [
                'id' => 47,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 7,
                'entity_videos' => '[]',
            ],
            10 => [
                'id' => 48,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 8,
                'entity_videos' => '[]',
            ],
            11 => [
                'id' => 49,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 27,
                'entity_videos' => '[]',
            ],
            12 => [
                'id' => 50,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 31,
                'entity_videos' => '[]',
            ],
            13 => [
                'id' => 51,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 17,
                'entity_videos' => '[]',
            ],
            14 => [
                'id' => 52,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 19,
                'entity_videos' => '[]',
            ],
            15 => [
                'id' => 53,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 30,
                'entity_videos' => '[]',
            ],
            16 => [
                'id' => 54,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 5,
                'entity_videos' => '[]',
            ],
            17 => [
                'id' => 55,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 29,
                'entity_videos' => '[]',
            ],
            18 => [
                'id' => 56,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 26,
                'entity_videos' => '[]',
            ],
            19 => [
                'id' => 57,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 25,
                'entity_videos' => '[]',
            ],
            20 => [
                'id' => 58,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 24,
                'entity_videos' => '[]',
            ],
            21 => [
                'id' => 59,
                'entity_type' => 'Lara\\App\\Models\\Video',
                'entity_id' => 1,
                'entity_videos' => '[{"video_title": "FLYING OVER NEW YORK 4K UHD", "youtubecode": "9R0DvILdHV8"}]',
            ],
            22 => [
                'id' => 60,
                'entity_type' => 'Lara\\App\\Models\\Video',
                'entity_id' => 2,
                'entity_videos' => '[{"video_title": "Above NYC - Filmed in 12K", "youtubecode": "UN3uF3990Q0"}]',
            ],
            23 => [
                'id' => 61,
                'entity_type' => 'Lara\\App\\Models\\Video',
                'entity_id' => 3,
                'entity_videos' => '[{"video_title": "Stunning New York Manhattan Flyover", "youtubecode": "W7HKVdzP_XY"}]',
            ],
            24 => [
                'id' => 62,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 25,
                'entity_videos' => '[]',
            ],
            25 => [
                'id' => 63,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 14,
                'entity_videos' => '[]',
            ],
            26 => [
                'id' => 64,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 34,
                'entity_videos' => '[]',
            ],
            27 => [
                'id' => 65,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 35,
                'entity_videos' => '[]',
            ],
            28 => [
                'id' => 66,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 36,
                'entity_videos' => '[]',
            ],
            29 => [
                'id' => 67,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 37,
                'entity_videos' => '[]',
            ],
            30 => [
                'id' => 68,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 38,
                'entity_videos' => '[]',
            ],
            31 => [
                'id' => 69,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 40,
                'entity_videos' => '[]',
            ],
            32 => [
                'id' => 70,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 41,
                'entity_videos' => '[]',
            ],
            33 => [
                'id' => 71,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 42,
                'entity_videos' => '[]',
            ],
            34 => [
                'id' => 72,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 1,
                'entity_videos' => '[]',
            ],
            35 => [
                'id' => 73,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 2,
                'entity_videos' => '[]',
            ],
            36 => [
                'id' => 74,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 3,
                'entity_videos' => '[]',
            ],
            37 => [
                'id' => 75,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 5,
                'entity_videos' => '[]',
            ],
            38 => [
                'id' => 76,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 9,
                'entity_videos' => '[]',
            ],
            39 => [
                'id' => 77,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 10,
                'entity_videos' => '[]',
            ],
            40 => [
                'id' => 78,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 11,
                'entity_videos' => '[]',
            ],
            41 => [
                'id' => 79,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 12,
                'entity_videos' => '[]',
            ],
            42 => [
                'id' => 80,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 14,
                'entity_videos' => '[]',
            ],
            43 => [
                'id' => 81,
                'entity_type' => 'Lara\\App\\Models\\City',
                'entity_id' => 1,
                'entity_videos' => '[]',
            ],
            44 => [
                'id' => 82,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 26,
                'entity_videos' => '[]',
            ],
            45 => [
                'id' => 83,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 35,
                'entity_videos' => '[]',
            ],
            46 => [
                'id' => 84,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 36,
                'entity_videos' => '[]',
            ],
            47 => [
                'id' => 85,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 37,
                'entity_videos' => '[]',
            ],
            48 => [
                'id' => 86,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 50,
                'entity_videos' => '[]',
            ],
            49 => [
                'id' => 87,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 38,
                'entity_videos' => '[]',
            ],
            50 => [
                'id' => 88,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 39,
                'entity_videos' => '[]',
            ],
            51 => [
                'id' => 89,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 40,
                'entity_videos' => '[]',
            ],
        ]);

    }
}
