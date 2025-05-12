<div>
    <flux:modal.trigger name="category-form-{{ $categoryId }}" id="category-form-{{ $categoryId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer">
        @if ($edition)
            <flux:icon.pencil-square class="cursor-pointer" variant="micro" />
        @else
            <span class="flex items-center justify-center space-x-2 rounded-lg">
                <span>{{ __('Create') }}</span>
                <flux:icon.plus variant="micro" />
            </span>
        @endif
    </flux:modal.trigger>

    <flux:modal name="category-form-{{ $categoryId }}" class="w-5/6" wire:cancel="resetForm">
        <div class="space-y-6">
            <div>
                @if ($edition)
                    <flux:heading size="2xl">{{ __('Edit your category') }} « {{ $category->name }} »</flux:heading>
                @else
                    <flux:heading size="2xl">{{ __('Create your category') }}</flux:heading>
                @endif
            </div>

            <flux:input :label="__('Name of the category')" :placeholder="__('Category')"
                wire:model.lazy="categoryForm.name" />
            <flux:textarea :label="__('Description (optional)')" wire:model.lazy="categoryForm.description" />

            <flux:switch :label="__('Include in statistics')" wire:model.lazy="categoryForm.is_active" />

            <div class="space-y-4 p-4 bg-custom-accent rounded-lg">
                <flux:heading size="lg">{{ __('Transactions match') }}</flux:heading>

                <div class="max-h-64 overflow-y-auto">
                    @if ($categoryMatchForm)
                        @foreach ($categoryMatchForm as $index => $match)
                            <div class="flex items-center mb-6 space-x-2">
                                <flux:input :label="__('Transaction keyword')"
                                    wire:model.lazy="categoryMatchForm.{{ $index }}.keyword"
                                    value="{{ $match['keyword'] }}" class="flex-1" />
                                <flux:icon.trash wire:click="removeCategoryMatch({{ $index }})"
                                    class="cursor-pointer text-red-500 hover:text-red-700"
                                    title="{{ __('Remove this keyword') }}" />
                            </div>
                        @endforeach
                    @endif
                    <flux:icon.plus wire:click="addCategoryMatch"
                        class="cursor-pointer text-custom-accent hover:text-custom" />
                </div>
            </div>

            <div class="flex gap-x-2 mt-6 justify-end">

                <flux:modal.close>
                    <flux:button variant="ghost">{{ __('Cancel') }}</flux:button>
                </flux:modal.close>
                <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                    @if ($edition)
                        {{ __('Update') }}
                    @else
                        {{ __('Create') }}
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
