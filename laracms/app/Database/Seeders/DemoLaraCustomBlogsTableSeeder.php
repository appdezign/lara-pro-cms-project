<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraCustomBlogsTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {


        \DB::table('lara_custom_blogs')->delete();

        \DB::table('lara_custom_blogs')->insert(array (
            0 =>
            array (
                'id' => 1,
                'user_id' => 3,
                'language' => 'nl',
                'title' => 'My Custom Blog nr 2',
                'slug' => 'my-custom-blog-nr-1',
                'body' => '<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit. Pellentesque porttitor nunc elit, eu suscipit ligula facilisis at. Nulla facilisi. Mauris maximus, sapien ut gravida efficitur, leo nibh molestie urna, a pulvinar neque turpis et dolor. Morbi vestibulum nulla id erat molestie maximus. Vivamus suscipit tincidunt varius. Fusce tincidunt et urna at lobortis. Proin aliquam, eros eu maximus placerat, nisi magna imperdiet lorem, ut pulvinar urna est finibus nulla. Donec maximus orci eget orci sodales, sed sodales sem fringilla. Praesent volutpat maximus mauris luctus sodales. Ut ex velit, faucibus quis mauris a, congue convallis massa. Proin suscipit tellus ut sem laoreet dapibus vitae sit amet velit. Sed pellentesque laoreet orci quis viverra.</p>',
                'publish' => 1,
                'created_at' => '2026-10-10 14:04:00',
                'updated_at' => '2026-10-10 14:40:51',
            ),
            1 =>
            array (
                'id' => 2,
                'user_id' => 3,
                'language' => 'nl',
                'title' => 'Test with Tiptap editor',
                'slug' => 'test-with-tiptap-editor',
                'body' => '<h2>Lorem ipsum dolor sit amet</h2><p>Lorem ipsum dolor sit amet, <strong>consectetur</strong> adipiscing elit. <em>Pellentesque</em> porttitor <u>nunc</u> elit, eu suscipit ligula facilisis at. Nulla facilisi. Mauris maximus, sapien ut gravida efficitur, leo nibh molestie urna, a pulvinar neque turpis et dolor. Morbi vestibulum nulla id erat molestie maximus. Vivamus suscipit tincidunt varius. Fusce tincidunt et urna at lobortis. Proin aliquam, eros eu maximus placerat, nisi magna imperdiet lorem, ut pulvinar urna est finibus nulla. Donec maximus orci eget orci sodales, sed sodales sem fringilla. Praesent volutpat maximus mauris luctus sodales. Ut ex velit, faucibus quis mauris a, congue convallis massa.</p><ul><li><p>Proin suscipit tellus ut sem laoreet dapibus vitae sit amet velit.</p></li><li><p>Sed pellentesque laoreet orci quis viverra.</p></li><li><p>Aliquam semper eu tortor nec vulputate.<br /></p></li></ul><p></p>',
                'publish' => 0,
                'created_at' => '2026-10-10 14:19:01',
                'updated_at' => '2026-10-10 14:20:18',
            ),
        ));


    }
}
