@props([
    'name',
    'label' => null,
    'options' => [],
    'selected' => [],
    'placeholder' => 'Rechercher...',
    'showChips' => true,
])

<div
    {{ $attributes->class('relative w-full') }}
    x-modelable="selectedIds"
    x-data="{
    open: false,
    search: '',
    selectedIds: @js($selected),
    items: @js($options),
    filtered() {
        return Object.entries(this.items)
            .filter(([id, name]) =>
                name.toLowerCase().includes(this.search.toLowerCase())
            );
    },
    isSelected(id) {
        return this.selectedIds.includes(id);
    },
    add(i)       { if(!this.isSelected(i)) this.selectedIds.push(i); this.search=''; },
    remove(i)    { this.selectedIds = this.selectedIds.filter(id => id !== i) },
}" class="relative w-full">
    @if ($label)
        <label id="{{ $name }}-label" class="block text-sm font-medium text-grey-inverse mb-2">{{ $label }}</label>
    @endif

    <div @click="open = !open"
        class="flex justify-between bg-custom items-center w-full border border-grey rounded-md px-4 py-2.5 cursor-pointer hover:border-color transition-colors duration-200"
        role="combobox"
        aria-haspopup="listbox"
        aria-expanded="open"
        tabindex="0"
        @if ($label)
            aria-labelledby="{{ $name }}-label"
        @else
            aria-label="{{ $placeholder }}"
        @endif
    >
        <div class="truncate">
            <template x-if="selectedIds.length === 0">
                <span class="text-grey italic">{{ $placeholder }}</span>
            </template>
            <template x-if="selectedIds.length > 0">
                <span class="font-medium" x-text="selectedIds.map(id => items[id]).join(', ')"></span>
            </template>
        </div>
        <x-atoms.sort-direction class="text-grey" />
    </div>

    <div x-show="open" @click.away="open = false"
        class="absolute z-50 w-full mt-1 border border-grey rounded-md shadow-lg bg-custom"
        x-transition:enter="transition ease-out duration-200"
        x-transition:enter-start="opacity-0 transform scale-95"
        x-transition:enter-end="opacity-100 transform scale-100">
        <div class="p-3 border-b border-grey">
            <input x-model="search" type="text" :placeholder="'{{ $placeholder }}'"
                class="w-full border border-grey rounded-md px-3 py-2 placeholder-text-grey focus:ring-2 focus:ring-color focus:border-color focus:outline-none transition-all duration-200"
                @focus="open = true" aria-label="{{ $placeholder }}">
        </div>

        <div class="max-h-60 overflow-y-auto" role="listbox">
            <template x-for="[id, name] in filtered()" :key="id">
                <div @click.stop="isSelected(id) ? remove(id) : add(id)"
                    class="px-4 py-2.5 cursor-pointer hover flex items-center justify-between transition-colors duration-150"
                    role="option"
                    :aria-selected="isSelected(id)"
                >
                    <span x-text="name" :class="{'font-medium': isSelected(id)}"></span>
                    <svg x-show="isSelected(id)" xmlns="http://www.w3.org/2000/svg"
                        class="h-5 w-5 text-color" viewBox="0 0 20 20" fill="currentColor">
                        <path fill-rule="evenodd"
                            d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"
                            clip-rule="evenodd" />
                    </svg>
                </div>
            </template>
            <div x-show="filtered().length === 0" class="px-4 py-3 text-grey text-center italic">
                Aucun résultat
            </div>
        </div>
    </div>

    @if($showChips)
    <div class="mt-2 flex flex-wrap gap-2">
        <template x-for="id in selectedIds" :key="id">
            <div class="rounded-md selected-accent hover-accent border border-grey px-2.5 py-1.5 flex items-center space-x-1 transition-all duration-200 ">
                <span class="cursor-default text-sm font-medium" x-text="items[id]"></span>
                <button type="button" @click="remove(id)" class="ml-1 text-grey cursor-pointer hover:text-danger-500 transition-colors duration-200" x-bind:aria-label="'{{ __('Remove') }} ' + items[id]">
                    <flux:icon.x-mark variant="micro" />
                </button>
            </div>
        </template>
    </div>
    @endif
    <input type="hidden" name="{{ $name }}" x-model="selectedIds">
</div>
