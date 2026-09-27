<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectTaggablesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_object_taggables')->delete();

        \DB::table('lara_object_taggables')->insert([
            0 => [
                'id' => 247,
                'tag_id' => 2060,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 3,
            ],
            1 => [
                'id' => 248,
                'tag_id' => 2059,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 3,
            ],
            2 => [
                'id' => 249,
                'tag_id' => 2060,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 22,
            ],
            3 => [
                'id' => 250,
                'tag_id' => 2059,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 22,
            ],
            4 => [
                'id' => 251,
                'tag_id' => 2060,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 23,
            ],
            5 => [
                'id' => 252,
                'tag_id' => 2063,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 23,
            ],
            6 => [
                'id' => 253,
                'tag_id' => 2060,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 24,
            ],
            7 => [
                'id' => 254,
                'tag_id' => 2061,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 24,
            ],
            8 => [
                'id' => 255,
                'tag_id' => 2060,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 25,
            ],
            9 => [
                'id' => 256,
                'tag_id' => 2059,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 25,
            ],
            10 => [
                'id' => 257,
                'tag_id' => 2062,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 26,
            ],
            11 => [
                'id' => 258,
                'tag_id' => 2066,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 26,
            ],
            12 => [
                'id' => 259,
                'tag_id' => 2062,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 27,
            ],
            13 => [
                'id' => 260,
                'tag_id' => 2062,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 29,
            ],
            14 => [
                'id' => 261,
                'tag_id' => 2066,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 29,
            ],
            15 => [
                'id' => 262,
                'tag_id' => 2062,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 30,
            ],
            16 => [
                'id' => 263,
                'tag_id' => 2065,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 30,
            ],
            17 => [
                'id' => 265,
                'tag_id' => 2064,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 31,
            ],
            18 => [
                'id' => 266,
                'tag_id' => 2073,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 3,
            ],
            19 => [
                'id' => 267,
                'tag_id' => 2073,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 2,
            ],
            20 => [
                'id' => 268,
                'tag_id' => 2073,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 4,
            ],
            21 => [
                'id' => 269,
                'tag_id' => 2077,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 1,
            ],
            22 => [
                'id' => 270,
                'tag_id' => 2077,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 2,
            ],
            23 => [
                'id' => 271,
                'tag_id' => 2078,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 3,
            ],
            24 => [
                'id' => 272,
                'tag_id' => 2080,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 4,
            ],
            25 => [
                'id' => 273,
                'tag_id' => 2078,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 5,
            ],
            26 => [
                'id' => 274,
                'tag_id' => 2079,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 7,
            ],
            27 => [
                'id' => 275,
                'tag_id' => 2081,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 8,
            ],
            28 => [
                'id' => 276,
                'tag_id' => 2078,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 6,
            ],
            29 => [
                'id' => 277,
                'tag_id' => 2082,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 6,
            ],
            30 => [
                'id' => 278,
                'tag_id' => 2067,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 27,
            ],
            31 => [
                'id' => 279,
                'tag_id' => 2083,
                'entity_type' => 'Lara\\App\\Models\\Event',
                'entity_id' => 1,
            ],
            32 => [
                'id' => 280,
                'tag_id' => 2083,
                'entity_type' => 'Lara\\App\\Models\\Event',
                'entity_id' => 2,
            ],
            33 => [
                'id' => 281,
                'tag_id' => 2088,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 1,
            ],
            34 => [
                'id' => 282,
                'tag_id' => 2091,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 1,
            ],
            35 => [
                'id' => 283,
                'tag_id' => 2089,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 2,
            ],
            36 => [
                'id' => 284,
                'tag_id' => 2092,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 2,
            ],
            37 => [
                'id' => 285,
                'tag_id' => 2090,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 3,
            ],
            38 => [
                'id' => 286,
                'tag_id' => 2093,
                'entity_type' => 'Lara\\App\\Models\\Service',
                'entity_id' => 3,
            ],
            39 => [
                'id' => 287,
                'tag_id' => 2095,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 2,
            ],
            40 => [
                'id' => 288,
                'tag_id' => 2095,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 1,
            ],
            41 => [
                'id' => 289,
                'tag_id' => 2095,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 3,
            ],
            42 => [
                'id' => 290,
                'tag_id' => 2096,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 4,
            ],
            43 => [
                'id' => 291,
                'tag_id' => 2096,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 5,
            ],
            44 => [
                'id' => 292,
                'tag_id' => 2096,
                'entity_type' => 'Lara\\App\\Models\\Testimonial',
                'entity_id' => 6,
            ],
            45 => [
                'id' => 293,
                'tag_id' => 2097,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 1,
            ],
            46 => [
                'id' => 294,
                'tag_id' => 2097,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 2,
            ],
            47 => [
                'id' => 295,
                'tag_id' => 2097,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 3,
            ],
            48 => [
                'id' => 296,
                'tag_id' => 2101,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 4,
            ],
            49 => [
                'id' => 297,
                'tag_id' => 2101,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 5,
            ],
            50 => [
                'id' => 298,
                'tag_id' => 2098,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 6,
            ],
            51 => [
                'id' => 299,
                'tag_id' => 2098,
                'entity_type' => 'Lara\\App\\Models\\Portfolio',
                'entity_id' => 7,
            ],
            52 => [
                'id' => 300,
                'tag_id' => 2076,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 5,
            ],
            53 => [
                'id' => 301,
                'tag_id' => 2076,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 6,
            ],
            54 => [
                'id' => 302,
                'tag_id' => 2102,
                'entity_type' => 'Lara\\App\\Models\\Gallery',
                'entity_id' => 1,
            ],
            55 => [
                'id' => 303,
                'tag_id' => 2103,
                'entity_type' => 'Lara\\App\\Models\\Gallery',
                'entity_id' => 2,
            ],
            56 => [
                'id' => 304,
                'tag_id' => 2106,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 1,
            ],
            57 => [
                'id' => 305,
                'tag_id' => 2106,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 2,
            ],
            58 => [
                'id' => 306,
                'tag_id' => 2108,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 2,
            ],
            59 => [
                'id' => 307,
                'tag_id' => 2107,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 3,
            ],
            60 => [
                'id' => 308,
                'tag_id' => 2106,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 4,
            ],
            61 => [
                'id' => 309,
                'tag_id' => 2106,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 5,
            ],
            62 => [
                'id' => 310,
                'tag_id' => 2108,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 5,
            ],
            63 => [
                'id' => 311,
                'tag_id' => 2106,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 6,
            ],
            64 => [
                'id' => 312,
                'tag_id' => 2109,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 6,
            ],
            65 => [
                'id' => 313,
                'tag_id' => 2117,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 1,
            ],
            66 => [
                'id' => 314,
                'tag_id' => 2120,
                'entity_type' => 'Lara\\App\\Models\\City',
                'entity_id' => 1,
            ],
            67 => [
                'id' => 315,
                'tag_id' => 2074,
                'entity_type' => 'Lara\\Common\\Models\\Slider',
                'entity_id' => 7,
            ],
            68 => [
                'id' => 316,
                'tag_id' => 2062,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 38,
            ],
            69 => [
                'id' => 317,
                'tag_id' => 2064,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 38,
            ],
        ]);

    }
}
