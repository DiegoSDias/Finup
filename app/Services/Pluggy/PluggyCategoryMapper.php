<?php

namespace App\Services\Pluggy;

use App\Models\Category;
use Illuminate\Support\Str;

class PluggyCategoryMapper
{
    public function resolve(int $userId, string $type, ?string $pluggyCategory): ?int
    {
        if (! $pluggyCategory) {
            return null;
        }

        $normalizedPluggyCategory = $this->normalize($pluggyCategory);
        $categories = Category::query()->where('user_id', $userId)->where('type', $type)->get();
        $exact = $categories->first(fn (Category $category) => $this->normalize($category->name) === $normalizedPluggyCategory);

        if ($exact) {
            return $exact->id;
        }

        $similar = $categories->first(function (Category $category) use ($normalizedPluggyCategory): bool {
            $name = $this->normalize($category->name);

            return strlen($name) >= 4 && (str_contains($normalizedPluggyCategory, $name) || str_contains($name, $normalizedPluggyCategory));
        });

        return $similar?->id
            ?? $categories->first(fn (Category $category) => in_array($this->normalize($category->name), ['outros', 'sem categoria'], true))?->id;
    }

    private function normalize(string $value): string
    {
        return trim(Str::lower(Str::ascii($value)));
    }
}
