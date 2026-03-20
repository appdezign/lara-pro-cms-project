<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraContentServicesTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_content_services')->delete();
        
        \DB::table('lara_content_services')->insert(array (
            0 => 
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Product Design',
                'slug' => 'product-design',
                'slug_lock' => 0,
                'lead' => '<p>Nullam semper enim quis vulputate mollis. Donec ultrices elementum mauris, ac porttitor mi cursus eget. Vestibulum gravida risus et lacus finibus tincidunt. Vivamus dui ante, pharetra eu blandit ac, vulputate et diam eu faucibus.</p>',
                'body' => '<ul><li><p>UX/UI</p></li><li><p>Interactive design</p></li><li><p>Motion design</p></li></ul>',
                'created_at' => '2025-08-22 17:47:57',
                'updated_at' => '2026-03-12 14:29:53',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-22 17:46:00',
                'publish_expire' => 0,
                'publish_to' => NULL,
                'publish_hide' => 0,
                'position' => 0,
                'cgroup' => NULL,
                'locked_at' => '2026-03-12 14:29:53',
                'locked_by' => 3,
            ),
            1 => 
            array (
                'id' => 2,
                'user_id' => 3,
                'language' => 'nl',
                'language_parent' => NULL,
                'title' => 'Web Development',
                'slug' => 'web-development',
                'slug_lock' => 0,
                'lead' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Aenean in arcu ligula. Vestibulum euismod interdum risus eu interdum. Vivamus suscipit mauris at erat ultricies, vitae placerat nulla feugiat. Integer venenatis blandit efficitur.</p>',
                'body' => '<ul><li><p>Bootstrap 5</p></li><li><p>Responsive</p></li><li><p>ES6 Modules</p></li></ul>',
                'created_at' => '2025-08-22 18:44:20',
                'updated_at' => '2025-12-24 12:23:46',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-22 18:44:00',
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
                'title' => 'Software Testing',
                'slug' => 'software-testing',
                'slug_lock' => 0,
                'lead' => '<p>Phasellus posuere leo vitae quam faucibus cursus. Phasellus eu ex ultrices, facilisis ex at, aliquet felis. Cras id rutrum ante. Curabitur suscipit diam a facilisis laoreet. Duis id elit imperdiet eros vestibulum molestie. Nulla pellentesque justo enim,</p>',
            'body' => '<div class="grid-layout" data-cols="2" style="--cols: repeat(2, minmax(0, 1fr));" data-from-breakpoint="lg"><div class="grid-layout-col" data-col-span="1" style="--col-span: span 1 / span 1;"><h3>85+</h3><p>Tested projects</p></div><div class="grid-layout-col" data-col-span="1" style="--col-span: span 1 / span 1;"><h3>200+</h3><p>Happy clients</p></div></div><h5></h5><h5></h5>',
                'created_at' => '2025-08-22 18:49:09',
                'updated_at' => '2026-03-12 14:28:35',
                'deleted_at' => NULL,
                'publish' => 1,
                'publish_from' => '2025-08-22 18:49:00',
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