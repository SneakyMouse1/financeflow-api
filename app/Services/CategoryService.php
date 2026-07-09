<?php

namespace App\Services;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Validation\ValidationException;

class CategoryService
{
    public function getAll(User $user): Collection
    {
        return $user->categories()->get();
    }

    public function create(User $user, array $data): Category
    {
        $category = $user->categories()->create($data);
        if ($category instanceof Category) {
            return $category;
        }
        throw new \UnexpectedValueException('Expected Category model');
    }

    public function update(Category $category, array $data): Category
    {
        $category->update($data);
        return $category;
    }

    public function delete(Category $category): void
    {
        if ($category->budgets()->exists()) {
            throw ValidationException::withMessages([
                'category' => ['Cannot delete category with active budgets. Delete the budgets first.'],
            ]);
        }

        $category->delete();
    }
}
