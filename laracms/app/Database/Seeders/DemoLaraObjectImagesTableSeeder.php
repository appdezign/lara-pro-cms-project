<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectImagesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_object_images')->delete();
        
        \DB::table('lara_object_images')->insert(array (
            0 => 
            array (
                'id' => 11,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 31,
                'media_id' => 65,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-22 20:10:13',
                'updated_at' => '2025-12-22 20:10:13',
            ),
            1 => 
            array (
                'id' => 15,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 2,
                'media_id' => 82,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 08:49:31',
                'updated_at' => '2025-12-24 08:49:31',
            ),
            2 => 
            array (
                'id' => 18,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 30,
                'media_id' => 57,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:47:17',
                'updated_at' => '2025-12-24 10:47:17',
            ),
            3 => 
            array (
                'id' => 19,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 29,
                'media_id' => 56,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:47:29',
                'updated_at' => '2025-12-24 10:47:29',
            ),
            4 => 
            array (
                'id' => 20,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 22,
                'media_id' => 60,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:47:53',
                'updated_at' => '2025-12-24 10:47:53',
            ),
            5 => 
            array (
                'id' => 21,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 27,
                'media_id' => 59,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:53:53',
                'updated_at' => '2025-12-24 10:53:53',
            ),
            6 => 
            array (
                'id' => 22,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 26,
                'media_id' => 58,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:54:03',
                'updated_at' => '2025-12-24 10:54:03',
            ),
            7 => 
            array (
                'id' => 23,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 25,
                'media_id' => 66,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:54:23',
                'updated_at' => '2025-12-24 10:54:23',
            ),
            8 => 
            array (
                'id' => 24,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 24,
                'media_id' => 67,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:54:34',
                'updated_at' => '2025-12-24 10:54:34',
            ),
            9 => 
            array (
                'id' => 25,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 23,
                'media_id' => 68,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:54:47',
                'updated_at' => '2025-12-24 10:54:47',
            ),
            10 => 
            array (
                'id' => 26,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 2,
                'media_id' => 84,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:57:26',
                'updated_at' => '2025-12-24 10:57:26',
            ),
            11 => 
            array (
                'id' => 27,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 3,
                'media_id' => 86,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:57:54',
                'updated_at' => '2025-12-24 10:57:54',
            ),
            12 => 
            array (
                'id' => 28,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 4,
                'media_id' => 87,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:58:13',
                'updated_at' => '2025-12-24 10:58:13',
            ),
            13 => 
            array (
                'id' => 29,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 5,
                'media_id' => 88,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:58:40',
                'updated_at' => '2025-12-24 10:58:40',
            ),
            14 => 
            array (
                'id' => 30,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 6,
                'media_id' => 89,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:58:48',
                'updated_at' => '2025-12-24 10:58:48',
            ),
            15 => 
            array (
                'id' => 31,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 5,
                'media_id' => 90,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 10:59:39',
                'updated_at' => '2025-12-24 10:59:39',
            ),
            16 => 
            array (
                'id' => 32,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 1,
                'media_id' => 69,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:13:10',
                'updated_at' => '2025-12-24 12:13:10',
            ),
            17 => 
            array (
                'id' => 33,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 2,
                'media_id' => 71,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:13:20',
                'updated_at' => '2025-12-24 12:13:20',
            ),
            18 => 
            array (
                'id' => 34,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 3,
                'media_id' => 70,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:13:30',
                'updated_at' => '2025-12-24 12:13:30',
            ),
            19 => 
            array (
                'id' => 35,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 4,
                'media_id' => 72,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:13:42',
                'updated_at' => '2025-12-24 12:13:42',
            ),
            20 => 
            array (
                'id' => 36,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 5,
                'media_id' => 73,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:13:53',
                'updated_at' => '2025-12-24 12:13:53',
            ),
            21 => 
            array (
                'id' => 37,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 6,
                'media_id' => 74,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:14:14',
                'updated_at' => '2025-12-24 12:14:14',
            ),
            22 => 
            array (
                'id' => 38,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 7,
                'media_id' => 75,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:14:27',
                'updated_at' => '2025-12-24 12:14:27',
            ),
            23 => 
            array (
                'id' => 39,
                'mediable_type' => 'Lara\\App\\Models\\Team',
                'mediable_id' => 8,
                'media_id' => 76,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:14:35',
                'updated_at' => '2025-12-24 12:14:35',
            ),
            24 => 
            array (
                'id' => 40,
                'mediable_type' => 'Lara\\App\\Models\\Event',
                'mediable_id' => 1,
                'media_id' => 80,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:14:59',
                'updated_at' => '2025-12-24 12:14:59',
            ),
            25 => 
            array (
                'id' => 41,
                'mediable_type' => 'Lara\\App\\Models\\Event',
                'mediable_id' => 2,
                'media_id' => 81,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:15:07',
                'updated_at' => '2025-12-24 12:15:07',
            ),
            26 => 
            array (
                'id' => 42,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 1,
                'media_id' => 95,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:16:45',
                'updated_at' => '2025-12-24 12:16:45',
            ),
            27 => 
            array (
                'id' => 43,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 2,
                'media_id' => 97,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:16:57',
                'updated_at' => '2025-12-24 12:16:57',
            ),
            28 => 
            array (
                'id' => 44,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 3,
                'media_id' => 96,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:17:11',
                'updated_at' => '2025-12-24 12:17:11',
            ),
            29 => 
            array (
                'id' => 45,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 4,
                'media_id' => 98,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:17:25',
                'updated_at' => '2025-12-24 12:17:25',
            ),
            30 => 
            array (
                'id' => 46,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 5,
                'media_id' => 100,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:18:13',
                'updated_at' => '2025-12-24 12:18:13',
            ),
            31 => 
            array (
                'id' => 47,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 6,
                'media_id' => 99,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:18:24',
                'updated_at' => '2025-12-24 12:18:24',
            ),
            32 => 
            array (
                'id' => 48,
                'mediable_type' => 'Lara\\App\\Models\\Portfolio',
                'mediable_id' => 7,
                'media_id' => 101,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:18:33',
                'updated_at' => '2025-12-24 12:18:33',
            ),
            33 => 
            array (
                'id' => 49,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 1,
                'media_id' => 83,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:19:03',
                'updated_at' => '2025-12-24 12:19:03',
            ),
            34 => 
            array (
                'id' => 50,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 2,
                'media_id' => 102,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:19:31',
                'updated_at' => '2025-12-24 12:19:31',
            ),
            35 => 
            array (
                'id' => 51,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 3,
                'media_id' => 103,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:21:41',
                'updated_at' => '2025-12-24 12:21:41',
            ),
            36 => 
            array (
                'id' => 52,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 4,
                'media_id' => 102,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:22:15',
                'updated_at' => '2025-12-24 12:22:15',
            ),
            37 => 
            array (
                'id' => 53,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 5,
                'media_id' => 104,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:22:42',
                'updated_at' => '2025-12-24 12:22:42',
            ),
            38 => 
            array (
                'id' => 54,
                'mediable_type' => 'Lara\\App\\Models\\Testimonial',
                'mediable_id' => 6,
                'media_id' => 83,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:22:58',
                'updated_at' => '2025-12-24 12:22:58',
            ),
            39 => 
            array (
                'id' => 55,
                'mediable_type' => 'Lara\\App\\Models\\Service',
                'mediable_id' => 1,
                'media_id' => 77,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:23:30',
                'updated_at' => '2025-12-24 12:23:30',
            ),
            40 => 
            array (
                'id' => 56,
                'mediable_type' => 'Lara\\App\\Models\\Service',
                'mediable_id' => 2,
                'media_id' => 78,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:23:46',
                'updated_at' => '2025-12-24 12:23:46',
            ),
            41 => 
            array (
                'id' => 57,
                'mediable_type' => 'Lara\\App\\Models\\Service',
                'mediable_id' => 3,
                'media_id' => 79,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:24:04',
                'updated_at' => '2025-12-24 12:24:04',
            ),
            42 => 
            array (
                'id' => 59,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 105,
                'order' => 11,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            43 => 
            array (
                'id' => 60,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 106,
                'order' => 12,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            44 => 
            array (
                'id' => 61,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 107,
                'order' => 13,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            45 => 
            array (
                'id' => 62,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 109,
                'order' => 14,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            46 => 
            array (
                'id' => 63,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 110,
                'order' => 15,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            47 => 
            array (
                'id' => 64,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 111,
                'order' => 16,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            48 => 
            array (
                'id' => 65,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 113,
                'order' => 17,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            49 => 
            array (
                'id' => 66,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 112,
                'order' => 18,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:25:55',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            50 => 
            array (
                'id' => 67,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 115,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:26:47',
                'updated_at' => '2025-12-24 12:26:47',
            ),
            51 => 
            array (
                'id' => 68,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 114,
                'order' => 1,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            52 => 
            array (
                'id' => 69,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 117,
                'order' => 2,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            53 => 
            array (
                'id' => 70,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 116,
                'order' => 3,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            54 => 
            array (
                'id' => 71,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 118,
                'order' => 4,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            55 => 
            array (
                'id' => 72,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 119,
                'order' => 5,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            56 => 
            array (
                'id' => 73,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 120,
                'order' => 6,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            57 => 
            array (
                'id' => 74,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 121,
                'order' => 7,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:05',
                'updated_at' => '2025-12-24 12:27:05',
            ),
            58 => 
            array (
                'id' => 75,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 115,
                'order' => 15,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:27:33',
                'updated_at' => '2025-12-24 12:27:33',
            ),
            59 => 
            array (
                'id' => 76,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 2,
                'media_id' => 122,
                'order' => 17,
                'type' => 'gallery',
                'created_at' => '2025-12-24 12:28:05',
                'updated_at' => '2025-12-24 12:28:05',
            ),
            60 => 
            array (
                'id' => 77,
                'mediable_type' => 'Lara\\Common\\Models\\LaraWidget',
                'mediable_id' => 3,
                'media_id' => 92,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:31:59',
                'updated_at' => '2025-12-24 12:31:59',
            ),
            61 => 
            array (
                'id' => 78,
                'mediable_type' => 'Lara\\Common\\Models\\LaraWidget',
                'mediable_id' => 4,
                'media_id' => 94,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:32:19',
                'updated_at' => '2025-12-24 12:32:19',
            ),
            62 => 
            array (
                'id' => 79,
                'mediable_type' => 'Lara\\Common\\Models\\LaraWidget',
                'mediable_id' => 5,
                'media_id' => 93,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:32:36',
                'updated_at' => '2025-12-24 12:32:36',
            ),
            63 => 
            array (
                'id' => 80,
                'mediable_type' => 'Lara\\Common\\Models\\LaraWidget',
                'mediable_id' => 6,
                'media_id' => 91,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2025-12-24 12:32:55',
                'updated_at' => '2025-12-24 12:32:55',
            ),
            64 => 
            array (
                'id' => 81,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 2,
                'media_id' => 123,
                'order' => 1,
                'type' => 'gallery',
                'created_at' => '2025-12-25 16:50:47',
                'updated_at' => '2025-12-25 16:50:47',
            ),
            65 => 
            array (
                'id' => 82,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 2,
                'media_id' => 124,
                'order' => 2,
                'type' => 'gallery',
                'created_at' => '2025-12-25 16:50:47',
                'updated_at' => '2025-12-25 16:50:47',
            ),
            66 => 
            array (
                'id' => 83,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 2,
                'media_id' => 125,
                'order' => 3,
                'type' => 'gallery',
                'created_at' => '2025-12-25 16:50:47',
                'updated_at' => '2025-12-25 16:50:47',
            ),
            67 => 
            array (
                'id' => 85,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 34,
                'media_id' => 126,
                'order' => 1,
                'type' => 'hero',
                'created_at' => '2025-12-25 16:57:28',
                'updated_at' => '2025-12-25 16:57:28',
            ),
            68 => 
            array (
                'id' => 88,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 25,
                'media_id' => 82,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2026-02-21 20:15:41',
                'updated_at' => '2026-02-21 20:15:41',
            ),
            69 => 
            array (
                'id' => 89,
                'mediable_type' => 'Lara\\App\\Models\\Blog',
                'mediable_id' => 37,
                'media_id' => 66,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2026-02-22 14:41:58',
                'updated_at' => '2026-02-22 14:41:58',
            ),
            70 => 
            array (
                'id' => 90,
                'mediable_type' => 'Lara\\Common\\Models\\LaraWidget',
                'mediable_id' => 7,
                'media_id' => 128,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2026-03-10 08:14:20',
                'updated_at' => '2026-03-10 08:14:20',
            ),
            71 => 
            array (
                'id' => 91,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 2,
                'media_id' => 85,
                'order' => 1,
                'type' => 'hero',
                'created_at' => '2026-03-10 18:50:38',
                'updated_at' => '2026-03-10 18:50:38',
            ),
            72 => 
            array (
                'id' => 93,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 108,
                'order' => 2,
                'type' => 'featured',
                'created_at' => '2026-03-12 18:56:26',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            73 => 
            array (
                'id' => 95,
                'mediable_type' => 'Lara\\App\\Models\\Gallery',
                'mediable_id' => 1,
                'media_id' => 114,
                'order' => 10,
                'type' => 'gallery',
                'created_at' => '2026-03-12 20:03:54',
                'updated_at' => '2026-03-13 09:46:00',
            ),
            74 => 
            array (
                'id' => 96,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 14,
                'media_id' => 129,
                'order' => 2,
                'type' => 'hero',
                'created_at' => '2026-03-16 13:51:38',
                'updated_at' => '2026-03-16 13:51:50',
            ),
            75 => 
            array (
                'id' => 97,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 35,
                'media_id' => 130,
                'order' => 1,
                'type' => 'hero',
                'created_at' => '2026-03-16 13:53:40',
                'updated_at' => '2026-03-16 13:53:40',
            ),
            76 => 
            array (
                'id' => 98,
                'mediable_type' => 'Lara\\Common\\Models\\Page',
                'mediable_id' => 36,
                'media_id' => 131,
                'order' => 2,
                'type' => 'hero',
                'created_at' => '2026-03-16 13:54:59',
                'updated_at' => '2026-03-16 14:01:38',
            ),
            77 => 
            array (
                'id' => 101,
                'mediable_type' => 'Lara\\Common\\Models\\Tag',
                'mediable_id' => 2083,
                'media_id' => 133,
                'order' => 2,
                'type' => 'hero',
                'created_at' => '2026-03-16 14:03:40',
                'updated_at' => '2026-03-16 14:03:40',
            ),
            78 => 
            array (
                'id' => 103,
                'mediable_type' => 'Lara\\Common\\Models\\Slider',
                'mediable_id' => 7,
                'media_id' => 134,
                'order' => 1,
                'type' => 'featured',
                'created_at' => '2026-03-19 10:46:47',
                'updated_at' => '2026-03-19 10:46:47',
            ),
        ));
        
        
    }
}