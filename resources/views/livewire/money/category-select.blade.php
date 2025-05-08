<div id="div-category-form-{{ $modalId }}">
    <flux:modal.trigger name="category-form-{{ $modalId }}" id="category-form-{{ $modalId }}">
        <div class="text-center">
            @if ($category)
                <span class="cursor-pointer">@limit($category->name, 24)</span>
            @else
                {{-- <span class="cursor-pointer">{{ __('Add') }}</span> --}}
                <span class="cursor-pointer">-</span>
            @endif
        </div>
    </flux:modal.trigger>

    <flux:modal name="category-form-{{ $modalId }}" class="w-5/6">
        <div class="space-y-6">
            <div>
                <flux:heading size="2xl">{{ __('Create a new category') }}</flux:heading>
                @if ($transaction)
                    <flux:text class="mt-2">
                        @if ($category)
                            {{ __('Edit the category of your transaction') }}
                        @else
                            {{ __('Add a category to your transaction') }}
                        @endif
                        <span class="font-extrabold"> « {{ $transaction->description }} »</span>
                    </flux:text>
                @endif
            </div>
            <form class="w-full text-left">
                <label for="category" class="font-bold">Catégorie</label>

                <div class="flex flex-col space-y-4">
                    <input type="text" name="category_name" id="category" placeholder="— Sélectionnez ou tapez —"
                        wire:model.live.debounce.500ms="selectedCategory" list="categories-list"
                        class="w-full px-4 py-2 border rounded-lg bg-custom focus:outline-none" />

                    <datalist id="categories-list">
                        @foreach ($categories as $category)
                            <option value="{{ $category->name }}"></option>
                        @endforeach
                    </datalist>

                    @if (!$alreadyExists)
                        <flux:text class="mt-4 mb-3 text-gray-400">
                            La catégorie {{ $selectedCategory }} n'existe pas encore.
                        </flux:text>

                        <div class="space-y-6">
                            <flux:textarea :label="__('Description (optional)')" wire:model.lazy="description" />
                            <flux:switch :label="__('Expense')" wire:model.lazy="isExpense" />
                        </div>
                    @endif

                    <flux:switch :label="__('Add other transactions to this category')"
                        wire:model.lazy="addOtherTransactions" />

                    @if ($addOtherTransactions)
                        <flux:input :label="__('Keyword to match')" placeholder="Payment Amazon"
                            wire:model.lazy="keyword" />
                        <flux:switch :label="__('Add only future transactions')"
                            wire:model.lazy="addOnlyFutureTransactions" />
                    @endif

                    <div class="flex mt-6 justify-end">
                        <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                            {{ __('Save') }}
                        </flux:button>
                    </div>

                </div>
            </form>
        </div>
    </flux:modal>
</div>
