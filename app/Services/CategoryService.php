<?php

namespace App\Services;

use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use Illuminate\Support\Facades\Auth;

class CategoryService
{
    public function saveCategory(array $categoryData, array $categoryMatchData, bool $edition, $category, bool $applyMatch, bool $applyMatchToAlreadyCategorized)
    {
        $user = Auth::user();

        if ($edition) {
            $category->update($categoryData);

            $existingKeywords = $category->categoryMatches->pluck('keyword')->toArray();
            $newKeywords = array_filter(array_column($categoryMatchData ?? [], 'keyword'));

            $deletedKeywords = array_diff($existingKeywords, $newKeywords ?? []);
            foreach ($deletedKeywords as $keyword) {
                $match = $category->categoryMatches()->where('keyword', $keyword)->first();
                if ($match) {
                    $match->delete();
                }
            }

            foreach ($categoryMatchData as $index => $match) {
                $keyword = trim($match['keyword'] ?? '');
                $matchId = $match['id'] ?? null;

                if ($keyword !== '') {
                    if (! empty($matchId)) {
                        $category->categoryMatches()->updateOrCreate(
                            ['id' => $matchId],
                            [
                                'user_id' => $user->id,
                                'keyword' => $keyword,
                            ],
                        );
                    } else {
                        $created = $category->categoryMatches()->create([
                            'user_id' => $user->id,
                            'keyword' => $keyword,
                        ]);
                        $categoryMatchData[$index]['id'] = $created->id;
                    }
                } else {
                    if (! empty($matchId)) {
                        $category->categoryMatches()->where('id', $matchId)->delete();
                    }
                    unset($categoryMatchData[$index]);
                }
            }

            $categoryMatchData = array_values($categoryMatchData);
        } else {
            $user->moneyCategories()->create($categoryData);
        }

        if ($applyMatch) {
            $transactionEdited = 0;
            foreach ($categoryMatchData as $match) {
                $transactionEdited += MoneyCategoryMatch::searchAndApplyMatchCategory($match['keyword'], $applyMatchToAlreadyCategorized);
            }

            return $transactionEdited;
        }

        return 0;
    }

    public function updateCategory(MoneyCategory $category, array $data): bool
    {
        return $category->update($data);
    }

    public function deleteCategory(MoneyCategory $category): bool
    {
        return $category->delete();
    }

    public function addCategory(array $data): MoneyCategory
    {
        return Auth::user()->moneyCategories()->create($data);
    }

    public function findOrCreateCategory(string $name, ?string $description = null): MoneyCategory
    {
        $user = Auth::user();

        return $user->moneyCategories()->firstOrCreate(
            ['name' => $name],
            ['description' => $description]
        );
    }

    public function associateCategoryWithTransaction($transaction, MoneyCategory $category): void
    {
        $transaction->category()->associate($category)->save();
    }

    public function updateOrCreateCategoryMatch(string $keyword, MoneyCategory $category): void
    {
        Auth::user()->moneyCategoryMatches()->updateOrCreate(
            ['keyword' => $keyword],
            ['money_category_id' => $category->id]
        );
    }
}
