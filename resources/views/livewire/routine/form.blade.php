<div>
    {{-- Modal de création --}}
    <flux:modal.trigger name="routine-form-{{ $routineId }}" id="routine-form-{{ $routineId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer">
        <div class="w-full text-center px-2 py-2 hover-custom rounded-t-lg">
            @if ($edition)
                <span class="flex items-center justify-center space-x-2">
                    <span>{{ __('Edit') }}</span>
                    <flux:icon.pencil-square variant="micro" />
                </span>
            @else
                <span class="flex items-center justify-center space-x-2 rounded-lg">
                    <span>{{ __('Create') }}</span>
                    <flux:icon.plus variant="micro" />
                </span>
            @endif
        </div>
    </flux:modal.trigger>

    {{-- Modal de création --}}
    <flux:modal name="routine-form-{{ $routineId }}" class="w-5/6">
        <div class="space-y-6">
            <div>
                @if ($edition)
                    <flux:heading size="2xl">{{ __('Modify your routine') }} « {{ $routine->name }} »</flux:heading>
                @else
                    <flux:heading size="2xl">{{ __('Create a new routine') }}</flux:heading>
                @endif
                <flux:text class="mt-2">
                    {{ __('Configure your routine and its recurrence.') }}
                </flux:text>
            </div>

            {{-- Nom & Description --}}
            <flux:input :label="__('Name of the routine')" placeholder="Routine matinal"
                wire:model.lazy="routineForm.name" />
            <flux:textarea :label="__('Description (optional)')" wire:model.lazy="routineForm.description" />

            {{-- Récurrence --}}
            <div class="space-y-4 p-4 bg-custom-accent rounded-lg">
                <flux:heading size="lg">{{ __('Recurrence Settings') }}</flux:heading>

                {{-- Date/heure de premiere occurrence --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input :label="__('Start of recurrence')" type="datetime-local"
                        wire:model.lazy="frequencyForm.start_date" />
                </div>

                {{-- Condition de fin --}}
                <flux:radio.group :label="__('End of recurrence')" wire:model.lazy="frequencyForm.end_type">
                    @foreach ($endTypes as $type => $label)
                        <flux:radio :value="$type" :label="$label"></flux:radio>
                    @endforeach
                </flux:radio.group>

                {{-- Date de fin --}}
                @if ($frequencyForm['end_type'] === 'until_date')
                    <flux:input :label="__('End date')" type="date" wire:model.lazy="frequencyForm.end_at" />
                @endif

                @if ($frequencyForm['end_type'] === 'occurrences')
                    <flux:input :label="__('Maximum number of occurrences')" type="number" min="1"
                        wire:model.lazy="frequencyForm.occurrence_count" />
                @endif

                {{-- Intervalle & unité --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input :label="__('Every X')" type="number" min="1"
                        wire:model.lazy="frequencyForm.interval" />
                    <flux:select :label="__('Unit')" wire:model.lazy="frequencyForm.unit">
                        @foreach ($units as $unit => $label)
                            <flux:select.option :value="$unit" :label="$label"></flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Options selon unité --}}
                {{-- Semaines --}}
                @if ($frequencyForm['unit'] === 'week')
                    <div class="grid grid-cols-7 gap-2 mt-2">
                        @foreach ($days as $num => $lbl)
                            <button type="button" wire:click.prevent="toggleWeekday({{ $num }})"
                                class="w-8 h-8 flex items-center justify-center border rounded
                                {{ in_array($num, $frequencyForm['weekdays'] ?? []) ? 'bg-custom text-elix border-elix' : 'bg-custom' }}">
                                {{ strtoupper(substr($lbl, 0, 1)) }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Mois --}}
                @if ($frequencyForm['unit'] === 'month')
                    <flux:radio.group :label="__('Monthly recurrence')" wire:model.lazy="freqMonthType"
                        wire:change="updateMonthType">
                        @foreach ($freqMonthTypes as $type => $label)
                            <flux:radio :value="$type" :label="$label"></flux:radio>
                        @endforeach
                    </flux:radio.group>

                    @if ($freqMonthType === 'daysNum')
                        <div class="grid grid-cols-7 gap-2 mt-2">
                            @foreach (range(1, 31) as $day)
                                <button type="button" wire:click.prevent="toggleMonthDay({{ $day }})"
                                    class="w-8 h-8 flex items-center justify-center border rounded
                    {{ in_array($day, $frequencyForm['month_days'] ?? []) ? 'bg-custom text-elix border-elix' : 'bg-custom' }}">
                                    {{ $day }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($freqMonthType === 'ordinal')
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select :label="__('Occurrence')" wire:model.lazy="frequencyForm.month_occurrences.0.ordinal">
                                @foreach ($freqMonthTypesOrdinalList as $num => $lbl)
                                    <flux:select.option :value="$num" :label="$lbl" />
                                @endforeach
                            </flux:select>
                            <flux:select :label="__('Day')" wire:model.lazy="frequencyForm.month_occurrences.0.weekday">
                                @foreach ($days as $num => $lbl)
                                    <flux:select.option :value="$num" :label="$lbl" />
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                @endif
            </div>

            <flux:switch :label="__('Active')" wire:model.lazy="routineForm.is_active" />

            <div class="flex mt-6 justify-between">
                <div class="italic cursor-default mr-4">
                    {{ $this->getFrequencySummaryProperty() }}
                </div>

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
