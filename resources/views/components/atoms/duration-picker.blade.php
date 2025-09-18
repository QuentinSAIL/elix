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
    wire:ignore
    x-data="durationPicker({
        modelPath: @js($wireModel),
        initial: @js($initial)
    })"
    x-init="init()"
    class="space-y-3"
>
    @if($label)
        <label class="block text-sm font-medium opacity-80">{{ $label }}</label>
    @endif

    <div class="inline-flex items-center gap-3 px-4 py-3 rounded-xl border backdrop-blur-sm shadow-sm hover:shadow-md transition-all duration-200">
        {{-- Hours --}}
        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="23"
                    x-model.number="hours"
                    @input="clamp(); pushToWire()"
                    @blur="clamp(); pushToWire()"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                    aria-label="{{ __('hours') }}"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('hours') }}</span>
        </div>

        <div class="text-2xl -mt-4 font-light opacity-40 select-none">:</div>

        {{-- Minutes --}}
        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="59"
                    x-model.number="minutes"
                    @input="clamp(); pushToWire()"
                    @blur="clamp(); pushToWire()"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                    aria-label="{{ __('minutes') }}"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('minutes') }}</span>
        </div>

        <div class="text-2xl -mt-4 font-light opacity-40 select-none">:</div>

        {{-- Seconds --}}
        <div class="flex flex-col items-center space-y-2 group">
            <div class="relative">
                <input
                    type="number"
                    min="0"
                    max="59"
                    x-model.number="seconds"
                    @input="clamp(); pushToWire()"
                    @blur="clamp(); pushToWire()"
                    class="w-16 h-12 text-center text-lg font-semibold rounded-lg border-0 bg-transparent focus:ring-2 focus:ring-offset-1 transition-all duration-200 hover:bg-black/5 dark:hover:bg-white/5"
                    placeholder="00"
                    aria-label="{{ __('seconds') }}"
                />
                <div class="absolute inset-0 rounded-lg border-2 border-transparent group-hover:border-current/20 transition-colors duration-200 pointer-events-none"></div>
            </div>
            <span class="text-xs font-medium opacity-60 group-hover:opacity-80 -mt-2 transition-opacity">{{ __('seconds') }}</span>
        </div>

        {{-- Total --}}
        <div class="ml-2 pl-3 border-l border-current/20" x-show="totalSeconds > 0" x-transition>
            <div class="text-xs opacity-60">{{ __('Total') }}</div>
            <div class="text-sm font-medium" x-text="formatDuration(totalSeconds)"></div>
        </div>
    </div>
</div>

@once
<script>
document.addEventListener('alpine:init', () => {
  Alpine.data('durationPicker', (cfg = {}) => {
    return {
      // === State ===
      hours: 0,
      minutes: 0,
      seconds: 0,

      // === Config ===
      modelPath: cfg.modelPath || null,
      initial: Number(cfg.initial || 0),

      // === Lifecycle ===
      init() {
        // 1) Init depuis Livewire si possible, sinon fallback "initial"
        const fromWire = this.readFromWire();
        this.fromSeconds(fromWire ?? this.initial);

        // 2) Quand Livewire a fini de (re)rendre, on resynchronise (édition/changement d’entité)
        Livewire.hook('message.processed', () => {
          const v = this.readFromWire();
          if (v !== null && v !== this.totalSeconds) {
            this.fromSeconds(v);
          }
        });
      },

      // === Computed ===
      get totalSeconds() {
        return (Number(this.hours||0)*3600) + (Number(this.minutes||0)*60) + Number(this.seconds||0);
      },

      // === Helpers ===
      clamp() {
        // borne les champs et corrige NaN
        this.hours   = Math.max(0, Math.min(23, Number.isFinite(+this.hours) ? +this.hours : 0));
        this.minutes = Math.max(0, Math.min(59, Number.isFinite(+this.minutes) ? +this.minutes : 0));
        this.seconds = Math.max(0, Math.min(59, Number.isFinite(+this.seconds) ? +this.seconds : 0));
      },

      fromSeconds(total) {
        total = Number(total || 0);
        if (!Number.isFinite(total) || total < 0) total = 0;
        this.hours   = Math.floor(total / 3600);
        this.minutes = Math.floor((total % 3600) / 60);
        this.seconds = total % 60;
      },

      formatDuration(total) {
        total = Number(total || 0);
        if (total < 60) return `${total}s`;
        if (total < 3600) return `${Math.floor(total/60)}min ${total%60}s`;
        const h = Math.floor(total/3600);
        const m = Math.floor((total%3600)/60);
        const s = total%60;
        return `${h}h ${m}min ${s}s`;
      },

      pushToWire() {
        if (!this.modelPath || !this.$wire) return;
        // true = "defer" pour limiter le nombre de requêtes, à ajuster selon ton besoin
        this.$wire.set(this.modelPath, this.totalSeconds, true);
      },

      readFromWire() {
        if (!this.modelPath || !this.$wire) return null;
        // Livewire v3 expose $wire.get(name). Si indispo, on retourne null.
        try {
          if (typeof this.$wire.get === 'function') {
            const v = this.$wire.get(this.modelPath);
            return Number(v ?? 0);
          }
        } catch (_) {}
        return null;
      },
    };
  });
});
</script>
@endonce
