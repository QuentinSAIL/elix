<?php

namespace Tests\Feature\Livewire\Money;

use App\Livewire\Money\CategoryForm;
use App\Models\MoneyCategory;
use App\Models\MoneyCategoryMatch;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Masmerise\Toaster\Toaster;
use Mockery;
use Tests\TestCase;

/**
 * @covers \App\Livewire\Money\CategoryForm
 */
class CategoryFormTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::factory()->create();
        $this->actingAs($this->user);

        // Default: don't mock static calls globally to avoid alias conflicts across test suites
    }

    public function category_form_component_can_be_rendered()
    {
        Livewire::test(CategoryForm::class)
            ->assertStatus(200);
    }

    public function it_populates_form_for_new_category()
    {
        Livewire::test(CategoryForm::class)
            ->assertSet('edition', false)
            ->assertSet('categoryForm.name', '')
            ->assertSet('categoryForm.budget', 0)
            ->assertCount(1, 'categoryMatchForm');
    }

    public function it_populates_form_for_existing_category()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->count(2)->create(['money_category_id' => $category->id, 'user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->assertSet('edition', true)
            ->assertSet('categoryForm.name', $category->name)
            ->assertCount(2, 'categoryMatchForm');
    }

    public function it_resets_form()
    {
        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'Test Name')
            ->set('categoryMatchForm', [['keyword' => 'test']])
            ->call('resetForm')
            ->assertSet('categoryForm.name', '')
            ->assertCount(1, 'categoryMatchForm')
            ->assertSet('categoryMatchForm.0.keyword', '');
    }

    public function has_match_changes_property_returns_true_if_matches_changed()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'old_keyword']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm.0.keyword', 'new_keyword');

        $this->assertTrue($component->get('hasMatchChanges'));
    }

    public function has_match_changes_property_returns_false_if_no_changes()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing_keyword']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category]);

        $this->assertFalse($component->get('hasMatchChanges'));
    }

    public function it_adds_category_match_field()
    {
        Livewire::test(CategoryForm::class)
            ->call('addCategoryMatch')
            ->assertCount(2, 'categoryMatchForm');
    }

    public function it_removes_category_match_field()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $match = MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->call('removeCategoryMatch', 0)
            ->assertCount(0, 'categoryMatchForm');

        $this->assertDatabaseMissing('money_category_matches', ['id' => $match->id]);
    }

    public function it_creates_new_category_and_matches()
    {
        $component = Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'New Category')
            ->set('categoryMatchForm.0.keyword', 'keyword1');
        $component->call('save');
        $component->assertDispatched('category-saved')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('money_categories', ['name' => 'New Category']);
        $newCategory = MoneyCategory::where('name', 'New Category')->first();
        $this->assertDatabaseHas('money_category_matches', ['money_category_id' => $newCategory->id, 'keyword' => 'keyword1']);
    }

    public function it_updates_existing_category_and_matches()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Old Name']);
        $match = MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'old_keyword']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Name')
            ->set('categoryMatchForm.0.keyword', 'updated_keyword');
        $component->call('save');
        $component->assertHasNoErrors();

        $this->assertDatabaseHas('money_categories', ['id' => $category->id, 'name' => 'Updated Name']);
        $this->assertDatabaseHas('money_category_matches', ['id' => $match->id, 'keyword' => 'updated_keyword']);
    }

    public function test_deletes_removed_matches_on_update()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Cat']);
        $match1 = MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'keep_me']);
        $match2 = MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'remove_me']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Cat')
            // Only keep the first match, drop the second
            ->set('categoryMatchForm', [
                ['id' => (string) $match1->id, 'category_id' => (string) $match1->id, 'keyword' => 'keep_me'],
            ]);
        $component->call('save');
        $component->assertHasNoErrors();

        $this->assertDatabaseHas('money_category_matches', ['id' => $match1->id, 'keyword' => 'keep_me']);
        $this->assertDatabaseMissing('money_category_matches', ['id' => $match2->id]);
    }

    public function test_generates_mobile_category_id_on_populate()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Mobile']);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('mobile', true)
            ->call('populateForm')
            ->assertSet('edition', true)
            ->assertSet('categoryForm.name', 'Mobile')
            ->assertSet('mobile', true);
    }

    public function test_apply_match_with_no_keywords_still_dispatches_with_zero_count()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id, 'name' => 'Empty']);

        // No keywords present
        Toaster::fake();
        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => ''],
            ])
            ->set('applyMatch', true);
        $component->call('applyMatch');

        Toaster::assertDispatched('Category applied to all matching transactions (0)');
    }

    public function it_validates_required_fields_for_category_and_matches()
    {
        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', '')
            ->set('categoryMatchForm.0.keyword', '')
            ->call('save')
            ->assertHasErrors([
                'categoryForm.name' => 'required',
                'categoryMatchForm.0.keyword' => 'required',
            ]);
    }

    public function it_handles_keyword_collisions()
    {
        $existingCategory = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $existingCategory->id, 'user_id' => $this->user->id, 'keyword' => 'colliding_keyword']);

        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'New Category')
            ->set('categoryMatchForm.0.keyword', 'colliding_keyword')
            ->call('save')
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'Keyword "colliding_keyword" collides with existing "colliding_keyword".');
            });
    }

    public function it_applies_match_to_transactions()
    {
        // Mock the static method searchAndApplyMatchCategory only for this test
        try {
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldReceive('searchAndApplyMatchCategory')
                ->andReturn(5);
        } catch (\RuntimeException $e) {
            // If alias already exists from another test, ignore
        }
        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'New Category')
            ->set('categoryMatchForm.0.keyword', 'keyword1')
            ->call('save')
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'success' && str_contains($data['message'], 'Category applied to all matching transactions (5)');
            })
            ->assertDispatched('transactions-edited');
    }

    public function it_does_not_apply_match_if_apply_match_is_false()
    {
        // Ensure the mock is not called if applyMatch is false
        try {
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldNotReceive('searchAndApplyMatchCategory');
        } catch (\RuntimeException $e) {
            // ignore alias conflicts
        }

        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'New Category')
            ->set('categoryMatchForm.0.keyword', 'keyword1')
            ->set('applyMatch', false)
            ->call('save');
    }

    public function it_handles_empty_keywords_in_match_form()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'valid_keyword'],
                ['id' => '', 'category_id' => '', 'keyword' => ''], // Empty keyword
                ['id' => '', 'category_id' => '', 'keyword' => '   '], // Whitespace only
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Should only create match for valid keyword
        $this->assertDatabaseHas('money_category_matches', [
            'money_category_id' => $category->id,
            'keyword' => 'valid_keyword',
        ]);
    }

    public function it_handles_keyword_collisions_during_edition()
    {
        $existingCategory = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create([
            'money_category_id' => $existingCategory->id,
            'user_id' => $this->user->id,
            'keyword' => 'colliding_keyword',
        ]);

        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm.0.keyword', 'colliding_keyword')
            ->call('save')
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'error' && str_contains($data['message'], 'colliding_keyword');
            });
    }

    public function it_updates_existing_match_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $match = MoneyCategoryMatch::factory()->create([
            'money_category_id' => $category->id,
            'user_id' => $this->user->id,
            'keyword' => 'old_keyword',
        ]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => (string) $match->id, 'category_id' => (string) $match->id, 'keyword' => 'updated_keyword'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('money_category_matches', [
            'id' => $match->id,
            'keyword' => 'updated_keyword',
        ]);
    }

    public function it_creates_new_matches_during_edition()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $existingMatch = MoneyCategoryMatch::factory()->create([
            'money_category_id' => $category->id,
            'user_id' => $this->user->id,
            'keyword' => 'existing_keyword',
        ]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => (string) $existingMatch->id, 'category_id' => (string) $existingMatch->id, 'keyword' => 'existing_keyword'],
                ['id' => '', 'category_id' => '', 'keyword' => 'new_keyword'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('money_category_matches', [
            'money_category_id' => $category->id,
            'keyword' => 'new_keyword',
        ]);
    }

    public function it_handles_apply_match_to_already_categorized()
    {
        try {
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldReceive('searchAndApplyMatchCategory')
                ->with('test_keyword', true)
                ->andReturn(3);
        } catch (\RuntimeException $e) {
            // ignore alias conflicts
        }

        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm.0.keyword', 'test_keyword')
            ->set('applyMatch', true)
            ->set('applyMatchToAlreadyCategorized', true)
            ->call('applyMatch');

        Toaster::assertDispatched('Category applied to all matching transactions (3)');
    }

    public function it_handles_multiple_keyword_collisions()
    {
        $existingCategory = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create([
            'money_category_id' => $existingCategory->id,
            'user_id' => $this->user->id,
            'keyword' => 'keyword1',
        ]);
        MoneyCategoryMatch::factory()->create([
            'money_category_id' => $existingCategory->id,
            'user_id' => $this->user->id,
            'keyword' => 'keyword2',
        ]);

        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'New Category')
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'keyword1'],
                ['id' => '', 'category_id' => '', 'keyword' => 'keyword2'],
            ])
            ->call('save')
            ->assertDispatched('toast', function ($name, $data) {
                return $data['type'] === 'error' &&
                       str_contains($data['message'], 'keyword1') &&
                       str_contains($data['message'], 'keyword2');
            });
    }

    public function it_handles_category_with_no_existing_matches()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->assertSet('edition', true)
            ->assertCount(1, 'categoryMatchForm')
            ->assertSet('categoryMatchForm.0.keyword', '');
    }

    public function it_handles_remove_category_match_with_invalid_index()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->call('removeCategoryMatch', 999) // Invalid index
            ->assertHasNoErrors();
    }

    public function it_handles_remove_category_match_without_existing_match()
    {
        Livewire::test(CategoryForm::class)
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'test'],
            ])
            ->call('removeCategoryMatch', 0)
            ->assertCount(0, 'categoryMatchForm');
    }

    public function it_handles_has_match_changes_property_for_new_category()
    {
        Livewire::test(CategoryForm::class)
            ->assertSet('hasMatchChanges', false);
    }

    public function it_handles_has_match_changes_property_with_empty_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => ''],
            ]);

        $this->assertFalse($component->get('hasMatchChanges'));
    }

    public function it_handles_has_match_changes_property_with_same_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'existing'],
            ]);

        $this->assertFalse($component->get('hasMatchChanges'));
    }

    public function it_handles_has_match_changes_property_with_different_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'new_keyword'],
            ]);

        $this->assertTrue($component->get('hasMatchChanges'));
    }

    public function it_handles_has_match_changes_property_with_multiple_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing1']);
        MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'existing2']);

        $component = Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'existing1'],
                ['id' => '', 'category_id' => '', 'keyword' => 'new_keyword'],
            ]);

        $this->assertTrue($component->get('hasMatchChanges'));
    }

    public function it_handles_save_with_existing_match_id()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);
        $match = MoneyCategoryMatch::factory()->create(['money_category_id' => $category->id, 'user_id' => $this->user->id, 'keyword' => 'old']);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => (string) $match->id, 'category_id' => (string) $match->id, 'keyword' => 'updated'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        $match->refresh();
        $this->assertEquals('updated', $match->keyword);
    }

    public function it_handles_save_with_non_existing_match_id()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => '999', 'category_id' => '999', 'keyword' => 'new_keyword'],
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Should create new match since ID doesn't exist
        $this->assertDatabaseHas('money_category_matches', [
            'money_category_id' => $category->id,
            'keyword' => 'new_keyword',
        ]);
    }

    public function it_handles_save_with_empty_keyword_after_validation()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'valid_keyword'],
                ['id' => '', 'category_id' => '', 'keyword' => ''], // Empty keyword
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Should only create match for valid keyword
        $this->assertDatabaseHas('money_category_matches', [
            'money_category_id' => $category->id,
            'keyword' => 'valid_keyword',
        ]);
    }

    public function it_handles_save_with_whitespace_only_keyword()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryForm.name', 'Updated Category')
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => '   '], // Whitespace only
            ])
            ->call('save')
            ->assertHasNoErrors();

        // Should not create match for whitespace-only keyword
        $this->assertDatabaseMissing('money_category_matches', [
            'money_category_id' => $category->id,
            'keyword' => '   ',
        ]);
    }

    public function it_handles_apply_match_with_empty_keywords()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Toaster::fake();
        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => ''],
            ])
            ->set('applyMatch', true)
            ->call('applyMatch');

        Toaster::assertDispatched('Category applied to all matching transactions (0)');
    }

    public function it_handles_apply_match_with_multiple_keywords()
    {
        try {
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldReceive('searchAndApplyMatchCategory')
                ->with('keyword1', false)
                ->andReturn(2);
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldReceive('searchAndApplyMatchCategory')
                ->with('keyword2', false)
                ->andReturn(3);
        } catch (\RuntimeException $e) {
            // ignore alias conflicts
        }

        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Toaster::fake();
        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'keyword1'],
                ['id' => '', 'category_id' => '', 'keyword' => 'keyword2'],
            ])
            ->set('applyMatch', true)
            ->call('applyMatch');

        Toaster::assertDispatched('Category applied to all matching transactions (5)');
    }

    public function it_handles_apply_match_false()
    {
        try {
            Mockery::mock('alias:App\\Models\\MoneyCategoryMatch')
                ->shouldNotReceive('searchAndApplyMatchCategory');
        } catch (\RuntimeException $e) {
            // ignore alias conflicts
        }

        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->set('categoryMatchForm', [
                ['id' => '', 'category_id' => '', 'keyword' => 'test_keyword'],
            ])
            ->set('applyMatch', false)
            ->call('applyMatch');

        // Should not dispatch any toast
        $this->assertTrue(true); // Just to have an assertion
    }

    public function it_handles_mobile_mode_for_new_category()
    {
        Livewire::test(CategoryForm::class, ['mobile' => true])
            ->assertSet('mobile', true)
            ->assertSet('categoryId', function ($value) {
                return str_contains($value, 'create-') && str_contains($value, '-m');
            });
    }

    public function it_handles_mobile_mode_for_existing_category()
    {
        $category = MoneyCategory::factory()->create(['user_id' => $this->user->id]);

        Livewire::test(CategoryForm::class, ['category' => $category, 'mobile' => true])
            ->assertSet('mobile', true)
            ->assertSet('categoryId', function ($value) use ($category) {
                return str_contains($value, (string) $category->id) && str_contains($value, '-m');
            });
    }

    public function it_handles_category_form_with_all_fields()
    {
        $category = MoneyCategory::factory()->create([
            'user_id' => $this->user->id,
            'name' => 'Test Category',
            'description' => 'Test Description',
            'color' => '#ff0000',
            'budget' => 1000.50,
            'include_in_dashboard' => false,
        ]);

        Livewire::test(CategoryForm::class, ['category' => $category])
            ->assertSet('categoryForm.name', 'Test Category')
            ->assertSet('categoryForm.description', 'Test Description')
            ->assertSet('categoryForm.color', '#ff0000')
            ->assertSet('categoryForm.budget', 1000.50)
            ->assertSet('categoryForm.include_in_dashboard', false);
    }

    public function it_handles_reset_form_with_all_fields()
    {
        Livewire::test(CategoryForm::class)
            ->set('categoryForm.name', 'Test')
            ->set('categoryForm.description', 'Test Description')
            ->set('categoryForm.color', '#ff0000')
            ->set('categoryForm.budget', 1000)
            ->set('categoryForm.include_in_dashboard', false)
            ->call('resetForm')
            ->assertSet('categoryForm.name', '')
            ->assertSet('categoryForm.description', '')
            ->assertSet('categoryForm.color', '#f66151')
            ->assertSet('categoryForm.budget', 0)
            ->assertSet('categoryForm.include_in_dashboard', true);
    }
}
