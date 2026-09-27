<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraFormContactformsTableSeeder extends Seeder
{
    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {

        \DB::table('lara_form_contactforms')->delete();

        \DB::table('lara_form_contactforms')->insert([
            0 => [
                'id' => 25,
                'comment' => null,
                'telephone' => '0651392621',
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2025-09-05 09:30:16',
                'updated_at' => '2025-09-05 09:30:16',
                'deleted_at' => null,
                'ipaddress' => '127.0.0.1',
                'locked_at' => null,
                'locked_by' => null,
            ],
            1 => [
                'id' => 26,
                'comment' => null,
                'telephone' => null,
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2026-04-24 17:08:23',
                'updated_at' => '2026-04-24 17:08:23',
                'deleted_at' => null,
                'ipaddress' => '127.0.0.1',
                'locked_at' => null,
                'locked_by' => null,
            ],
            2 => [
                'id' => 27,
                'comment' => null,
                'telephone' => null,
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2026-04-24 17:12:13',
                'updated_at' => '2026-04-24 17:12:13',
                'deleted_at' => null,
                'ipaddress' => '127.0.0.1',
                'locked_at' => null,
                'locked_by' => null,
            ],
            3 => [
                'id' => 28,
                'comment' => null,
                'telephone' => null,
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2026-04-24 17:13:07',
                'updated_at' => '2026-04-24 17:13:07',
                'deleted_at' => null,
                'ipaddress' => '127.0.0.1',
                'locked_at' => null,
                'locked_by' => null,
            ],
            4 => [
                'id' => 29,
                'comment' => null,
                'telephone' => null,
                'email' => 's.hoeksma@firmaq.nl',
                'name' => 'Sybrand Hoeksma',
                'created_at' => '2026-04-24 17:16:41',
                'updated_at' => '2026-04-24 17:16:41',
                'deleted_at' => null,
                'ipaddress' => '127.0.0.1',
                'locked_at' => null,
                'locked_by' => null,
            ],
        ]);

    }
}
