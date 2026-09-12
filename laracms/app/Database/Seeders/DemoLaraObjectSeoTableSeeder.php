<?php

namespace Lara\App\Database\Seeders;

use Illuminate\Database\Seeder;

class DemoLaraObjectSeoTableSeeder extends Seeder
{

    /**
     * Auto generated seed file
     *
     * @return void
     */
    public function run()
    {
        

        \DB::table('lara_object_seo')->delete();
        
        \DB::table('lara_object_seo')->insert(array (
            0 => 
            array (
                'id' => 1,
                'model_type' => 'Lara\\App\\Models\\Blog',
                'model_id' => 31,
                'title' => 'Five Effective Landing Page Examples',
                'description' => 'Phasellus dapibus enim leo, effective landing page. Donec aliquet arcu mi, eget consectetur ex egestas vel. In vitae efficitur felis. ',
                'focus_keyword' => 'Landing Page',
                'additional_keywords' => '"effective"',
                'is_cornerstone' => 0,
                'locale' => 'nl',
                'canonical_url' => NULL,
                'og_image' => 'https://laracms10.test/glide/blogs/b2bf1552-515d-40f4-99e1-1e10f7a495e2.jpg?w=1200&h=630&fit=crop&fm=webp&s=e0f4d246cc2704f27e7259b2ecfaf99e',
                'noindex' => 0,
                'nofollow' => 0,
                'score' => 75,
                'created_at' => '2026-08-09 10:36:17',
                'updated_at' => '2026-09-08 14:03:07',
            ),
            1 => 
            array (
                'id' => 2,
                'model_type' => 'Lara\\App\\Models\\Blog',
                'model_id' => 38,
                'title' => 'Internal Linking Strategies That Actually Move Rankings',
                'description' => 'A repeatable internal linking strategy: prioritise cornerstone content, audit old posts, and use descriptive anchor text.',
                'focus_keyword' => 'internal linking',
                'additional_keywords' => '"anchor text,link equity"',
                'is_cornerstone' => 0,
                'locale' => 'nl',
                'canonical_url' => NULL,
                'og_image' => 'https://laracms10.test/glide/blogs/51f6fac2-47a4-4d0c-8e69-3aaaba8dd892.jpg?w=1200&h=630&fit=crop&fm=webp&s=067223cce7a59eaaa646f959af30c274',
                'noindex' => 0,
                'nofollow' => 0,
                'score' => 71,
                'created_at' => '2026-08-09 10:39:24',
                'updated_at' => '2026-09-10 12:21:00',
            ),
            2 => 
            array (
                'id' => 3,
                'model_type' => 'Lara\\Common\\Models\\Page',
                'model_id' => 5,
                'title' => 'Lara is the best innovative content management system',
                'description' => 'Lara erat pharetra sed at fringilla etiam nullam platea fringilla. Gravida sodales sit mauris amet massa justo. Egestas ipsum amet tortor hendrerit amet.',
                'focus_keyword' => 'Lara',
                'additional_keywords' => '"Elementum,nullam"',
                'is_cornerstone' => 0,
                'locale' => 'nl',
                'canonical_url' => NULL,
                'og_image' => 'https://laracms10pro.test/glide/pages/2d790e38-205c-43f9-9706-2786d384019f.jpg?w=1200&h=630&fit=crop&fm=webp&s=f928dfaaf966d30cf79d34d6ed7efba9',
                'noindex' => 0,
                'nofollow' => 0,
                'score' => 73,
                'created_at' => '2026-08-10 16:04:03',
                'updated_at' => '2026-09-12 13:20:13',
            ),
            3 => 
            array (
                'id' => 4,
                'model_type' => 'Lara\\App\\Models\\Blog',
                'model_id' => 39,
                'title' => NULL,
                'description' => NULL,
                'focus_keyword' => NULL,
                'additional_keywords' => '""',
                'is_cornerstone' => 0,
                'locale' => 'nl',
                'canonical_url' => NULL,
                'og_image' => 'https://laracms10pro.test/glide/blogs/31243248-37b6-46e5-8291-1121e2ac6872.jpg?w=1200&h=630&fit=crop&fm=webp&s=31e7e664f7ae3d3c74a38c1ac09cfde8',
                'noindex' => 0,
                'nofollow' => 0,
                'score' => 17,
                'created_at' => '2026-08-30 18:23:05',
                'updated_at' => '2026-08-31 07:32:06',
            ),
        ));
        
        
    }
}