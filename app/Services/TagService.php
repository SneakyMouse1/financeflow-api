<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    public function getAll(User $user): Collection
    {
        return $user->tags()->get();
    }

    public function create(User $user, array $data): Tag
    {
        $tag = $user->tags()->create($data);
        if ($tag instanceof Tag) {
            return $tag;
        }
        throw new \UnexpectedValueException('Expected Tag model');
    }

    public function update(Tag $tag, array $data): Tag
    {
        $tag->update($data);
        return $tag;
    }

    public function delete(Tag $tag): void
    {
        $tag->delete();
    }
}
