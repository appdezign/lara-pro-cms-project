<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectFilesTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_object_files')->delete();

        \DB::table('lara_object_files')->insert([
            0 => [
                'id' => 128,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 31,
                'entity_files' => '[{"doc_date": "2025-12-19 11:19", "doc_filename": "blogs/file/20251219111937-test.pdf", "doc_original": "test.pdf"}]',
            ],
            1 => [
                'id' => 129,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 30,
                'entity_files' => '[]',
            ],
            2 => [
                'id' => 130,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 29,
                'entity_files' => '[]',
            ],
            3 => [
                'id' => 131,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 22,
                'entity_files' => '[]',
            ],
            4 => [
                'id' => 132,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 27,
                'entity_files' => '[]',
            ],
            5 => [
                'id' => 133,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 26,
                'entity_files' => '[]',
            ],
            6 => [
                'id' => 134,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 25,
                'entity_files' => '[]',
            ],
            7 => [
                'id' => 135,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 24,
                'entity_files' => '[]',
            ],
            8 => [
                'id' => 136,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 23,
                'entity_files' => '[]',
            ],
            9 => [
                'id' => 137,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 1,
                'entity_files' => '[{"doc_date": "2025-12-19 12:51", "doc_filename": "teams/file/20251219125141-test.pdf", "doc_original": "test.pdf"}]',
            ],
            10 => [
                'id' => 138,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 2,
                'entity_files' => '[]',
            ],
            11 => [
                'id' => 139,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 3,
                'entity_files' => '[]',
            ],
            12 => [
                'id' => 140,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 4,
                'entity_files' => '[]',
            ],
            13 => [
                'id' => 141,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 5,
                'entity_files' => '[]',
            ],
            14 => [
                'id' => 142,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 6,
                'entity_files' => '[]',
            ],
            15 => [
                'id' => 143,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 7,
                'entity_files' => '[]',
            ],
            16 => [
                'id' => 144,
                'entity_type' => 'Lara\\App\\Models\\Team',
                'entity_id' => 8,
                'entity_files' => '[]',
            ],
            17 => [
                'id' => 145,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 2,
                'entity_files' => '[{"doc_date": "2025-12-25 16:54", "doc_filename": "pages/file/20251225165413-test.pdf", "doc_original": "test.pdf"}]',
            ],
            18 => [
                'id' => 146,
                'entity_type' => 'Lara\\App\\Models\\Doc',
                'entity_id' => 1,
                'entity_files' => '[{"doc_date": "2025-12-19 14:26", "doc_filename": "docs/file/20251219142706-test.pdf", "doc_original": "test.pdf"}]',
            ],
            19 => [
                'id' => 147,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 5,
                'entity_files' => '[]',
            ],
            20 => [
                'id' => 148,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 14,
                'entity_files' => '[]',
            ],
            21 => [
                'id' => 149,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 34,
                'entity_files' => '[]',
            ],
            22 => [
                'id' => 150,
                'entity_type' => 'Lara\\App\\Models\\City',
                'entity_id' => 1,
                'entity_files' => '[]',
            ],
            23 => [
                'id' => 151,
                'entity_type' => 'Lara\\App\\Models\\Product',
                'entity_id' => 1,
                'entity_files' => '[]',
            ],
            24 => [
                'id' => 152,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 26,
                'entity_files' => '[]',
            ],
            25 => [
                'id' => 153,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 35,
                'entity_files' => '[]',
            ],
            26 => [
                'id' => 154,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 36,
                'entity_files' => '[]',
            ],
            27 => [
                'id' => 155,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 25,
                'entity_files' => '[]',
            ],
            28 => [
                'id' => 156,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 37,
                'entity_files' => '[]',
            ],
            29 => [
                'id' => 157,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 41,
                'entity_files' => '[]',
            ],
            30 => [
                'id' => 158,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 35,
                'entity_files' => '[]',
            ],
            31 => [
                'id' => 159,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 36,
                'entity_files' => '[]',
            ],
            32 => [
                'id' => 160,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 38,
                'entity_files' => '[]',
            ],
            33 => [
                'id' => 161,
                'entity_type' => 'Lara\\Common\\Models\\Page',
                'entity_id' => 50,
                'entity_files' => '[]',
            ],
            34 => [
                'id' => 162,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 38,
                'entity_files' => '[]',
            ],
            35 => [
                'id' => 163,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 39,
                'entity_files' => '[]',
            ],
            36 => [
                'id' => 164,
                'entity_type' => 'Lara\\App\\Models\\Blog',
                'entity_id' => 40,
                'entity_files' => '[]',
            ],
        ]);

    }
}
