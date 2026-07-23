<?php

namespace Weap\Junction\Tests\TestSupport\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;
use Weap\Junction\Junction;
use Weap\Junction\Tests\TestSupport\Database\Factories\PostFactory;

class Post extends Model
{
    /** @use HasFactory<PostFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = ['user_id', 'title', 'body', 'published_at'];

    /**
     * @var array<string, string>
     */
    protected $casts = ['published_at' => 'datetime'];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<Comment, $this>
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * @return BelongsToMany<Tag, $this>
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withPivot('label')->withTimestamps();
    }

    /**
     * Using old scope notation to test compatability.
     *
     * @param Builder<Post> $query
     */
    public function scopePublished(Builder $query): void
    {
        $query->whereNotNull('published_at');
    }

    /**
     * @param Builder<Post> $query
     * @param string $needle
     */
    #[Scope]
    protected function titleContains(Builder $query, string $needle): void
    {
        $query->where('title', 'like', "%{$needle}%");
    }

    /**
     * Using old attribute notation to test compatability.
     *
     * @return string
     */
    public function getExcerptAttribute(): string
    {
        return Str::limit((string) $this->body, 20);
    }

    /**
     * @return Attribute<string|null, never>
     */
    protected function authorName(): Attribute
    {
        return Junction::makeAttribute(fn (): ?string => $this->user?->name, with: ['user']);
    }

    protected static function newFactory(): PostFactory
    {
        return PostFactory::new();
    }
}
