<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\DatabaseTransactions;
use Illuminate\Support\Facades\DB;
use Lara\Admin\Resources\Base\Concerns\HasContentSection;
use Lara\App\Models\Blog;
use Lara\Common\Models\ObjectImage;
use ReflectionMethod;
use Tests\TestCase;

/**
 * A select custom field with the dynamic option get_record_images lists the record's own
 * images (media id => name), so a field can point to one of them (found on a migrated v8
 * site: interview paragraphs that each show one of the interview's photos).
 */
class RecordImageOptionsTest extends TestCase
{
    use DatabaseTransactions;

    private function imageOptions(?object $record): array
    {
        $form = new class
        {
            use HasContentSection;
        };

        $method = new ReflectionMethod($form::class, 'getSelectFieldOptions');
        $method->setAccessible(true);

        return $method->invoke(null, $record, (object) ['field_options' => ['get_record_images']]);
    }

    private function media(string $name): int
    {
        return DB::table('curator')->insertGetId([
            'disk' => 'public',
            'directory' => 'blogs',
            'visibility' => 'public',
            'name' => $name,
            'path' => "blogs/{$name}.jpg",
            'type' => 'image/jpeg',
            'ext' => 'jpg',
            'in_use' => 1,
        ]);
    }

    private function link(Blog $blog, int $mediaId, string $type, int $order): void
    {
        ObjectImage::create([
            'mediable_type' => Blog::class,
            'mediable_id' => $blog->id,
            'media_id' => $mediaId,
            'type' => $type,
            'order' => $order,
        ]);
    }

    public function test_the_options_are_the_records_own_images_each_listed_once(): void
    {
        $blog = Blog::factory()->create();
        $other = Blog::factory()->create();
        $cover = $this->media('cover');
        $portrait = $this->media('portrait');
        $elsewhere = $this->media('elsewhere');

        $this->link($blog, $cover, 'featured', 1);
        $this->link($blog, $cover, 'gallery', 1);
        $this->link($blog, $portrait, 'gallery', 2);
        $this->link($other, $elsewhere, 'gallery', 1);

        $this->assertSame([$cover => 'cover', $portrait => 'portrait'], $this->imageOptions($blog));
    }

    public function test_a_new_record_has_no_options(): void
    {
        $this->assertSame([], $this->imageOptions(null));
    }

    public function test_static_options_are_unchanged(): void
    {
        $form = new class
        {
            use HasContentSection;
        };

        $method = new ReflectionMethod($form::class, 'getSelectFieldOptions');
        $method->setAccessible(true);

        $this->assertSame(['left' => 'left', 'right' => 'right'], $method->invoke(null, null, (object) ['field_options' => ['left', 'right']]));
    }
}
