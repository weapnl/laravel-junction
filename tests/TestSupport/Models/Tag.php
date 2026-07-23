<?php

namespace Weap\Junction\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;
use Weap\Junction\Models\Concerns\HasDefaultAppends;
use Weap\Junction\Tests\TestSupport\Database\Factories\TagFactory;

class Tag extends Model
{
    /** @use HasFactory<TagFactory> */
    use HasFactory;
    use HasDefaultAppends;

    /**
     * @var list<string>
     */
    protected $fillable = ['name'];

    /**
     * @var list<string>
     */
    protected $appends = ['slug'];

    /**
     * @return BelongsToMany<Post, $this>
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class)->withPivot('label')->withTimestamps();
    }

    /**
     * @return Attribute<string, never>
     */
    protected function slug(): Attribute
    {
        return Attribute::get(fn (): string => Str::slug((string) $this->name));
    }

    protected static function newFactory(): TagFactory
    {
        return TagFactory::new();
    }
}
