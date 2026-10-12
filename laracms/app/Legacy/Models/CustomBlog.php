<?php

namespace Lara\App\Legacy\Models;

use Cviebrock\EloquentSluggable\Sluggable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Lara\App\Legacy\Database\Factories\CustomBlogFactory;

/**
 * The model of the legacy (non-Livewire) custom blog example.
 *
 * A plain Eloquent model with its own table: it is not an entity and does not extend BaseModel.
 */
class CustomBlog extends Model
{
    /** @use HasFactory<CustomBlogFactory> */
    use HasFactory, Sluggable;

    protected $table = 'lara_custom_blogs';

    protected $fillable = [
        'user_id',
        'language',
        'title',
        'body',
        'publish',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'publish' => 'boolean',
        ];
    }

    /**
     * The slug is generated from the title and is unique across the whole table.
     *
     * @return array<string, array<string, string>>
     */
    public function sluggable(): array
    {
        return [
            'slug' => [
                'source' => 'title',
            ],
        ];
    }

    protected static function newFactory(): CustomBlogFactory
    {
        return CustomBlogFactory::new();
    }
}
