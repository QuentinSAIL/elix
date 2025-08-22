@props([
    'label' => 'Durée',
    'name' => 'duration',
    'value' => 0,
])
@php
    $wireModel = $attributes->wire('model')->value();
    $initial = (int) $value;
@endphp

<div
    x-data="durationPicker()"
    @if($wireModel)
    x-init="
        // Init depuis Livewire si dispo, sinon fallback valeur initiale
        fromSeconds($wire.{{ $wireModel }} ?? {{ $initial }});
        // Livewire -> Alpine (édition, changement de tâche, etc.)
        $watch('$wire.{{ $wireModel }}', v => fromSeconds(v));
        // Alpine -> Livewire (saisie utilisateur)
        $watch('totalSeconds', v => $wire.set('{{ $wireModel }}', v));
    "
    @else
    x-init="fromSeconds({{ $initial }})"
    @endif
    class="space-y-3"
>
    @if($label)
        <label class="block text-sm font-medium opacity-80">{{ $label }}</label>
    @endif

    <div class="inline-flex items-center gap-3 px-4 py-3 rounded-xl border backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="23"
                    x-model.number="hours"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('hours') }}</span>
        </div>

        <div class="text-2xl -mt-4 font-light opacity-40 select-none">:</div>

        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="59"
                    x-model.number="minutes"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('minutes') }}</span>
        </div>

        <div class="text-2xl -mt-4 font-light opacity-40 select-none">:</div>

        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="59"
                    x-model.number="seconds"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('seconds') }}</span>
        </div>

        <div class="ml-2 pl-3 border-l border-current/20" x-show="totalSeconds > 0" x-transition>
            <div class="text-xs opacity-60">{{ __('Total') }}</div>
            <div class="text-sm font-medium" x-text="formatDuration()"></div>
        </div>
    </div>
</div>

<script>
function durationPicker() {
    return {
        hours: 0,
        minutes: 0,
        seconds: 0,

        fromSeconds(total) {
            total = parseInt(total ?? 0, 10) || 0;
            this.hours = Math.floor(total / 3600);
            this.minutes = Math.floor((total % 3600) / 60);
            this.seconds = total % 60;
        },

        get totalSeconds() {
            return (this.hours * 3600) + (this.minutes * 60) + this.seconds;
        },

        formatDuration() {
            const total = this.totalSeconds;
            if (total < 60) return `${total}s`;
            if (total < 3600) return `${Math.floor(total / 60)}min ${total % 60}s`;
            const h = Math.floor(total / 3600);
            const m = Math.floor((total % 3600) / 60);
            const s = total % 60;
            return `${h}h ${m}min ${s}s`;
        }
    }
}
</script>
