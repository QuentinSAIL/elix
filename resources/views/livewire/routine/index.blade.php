<div>
    <div class="flex flex-row gap-4 overflow-x-scroll py-4 h-96">
        {{-- Bouton d’ouverture du modal --}}
        <div
            class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover-custom transition-shadow flex items-center justify-center">
            <flux:modal.trigger name="create-routine"
                class="w-full h-full flex items-center justify-center cursor-pointer">
                <span class="m-1">Ajouter une routine</span>
                <flux:icon.plus class="text-2xl text-white" />
            </flux:modal.trigger>
        </div>

        {{-- Liste des routines existantes --}}
        @forelse($routines as $routine)
            <div class="flex-shrink-0 w-1/4 bg-custom p-6 shadow-sm hover:shadow-md transition-shadow relative">
                <div wire:click="delete('{{ $routine->id }}')"
                    class="cursor-pointer absolute top-4 right-4 hover-custom hover:text-red-600">
                    <flux:icon.x-mark />
                </div>
                <h3 class="text-xl font-semibold">{{ $routine->name }}</h3>
                <p class="mt-2 text-sm">{{ $routine->description }}</p>
                @if ($routine->tasks->count())
                    <div class="mt-4 space-y-2">
                        @foreach ($routine->tasks->take(2) as $task)
                            <div class="rounded-xl p-3 bg-custom-accent">
                                <h4 class="font">{{ $task->name }}</h4>
                                <p class="text-sm 0">@limit($task->description, 10)</p>
                            </div>
                        @endforeach
                    </div>
                @endif
            </div>
        @empty
            <div class="flex-shrink-0 w-full text-center py-10">
                Vous n'avez aucune routine pour le moment...
            </div>
        @endforelse
    </div>

    {{-- Modal de création --}}
    <flux:modal name="create-routine" class="w-5/6">
        <div class="space-y-6">
            <div>
                <flux:heading size="2xl">Créez votre routine</flux:heading>
                <flux:text class="mt-2">
                    Configurez votre routine et sa récurrence.
                </flux:text>
            </div>

            {{-- Nom & Description --}}
            <flux:input label="Nom de la routine" placeholder="Routine matinal" wire:model.lazy="newRoutine.name" />
            <flux:textarea label="Description (optionnel)" wire:model.lazy="newRoutine.description" />

            {{-- Récurrence --}}
            <div class="space-y-4 p-4 bg-custom-accent rounded-lg">
                <flux:heading size="lg">Paramètres de récurrence</flux:heading>

                {{-- Date/heure de premiere occurrence --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Début de la récurrence" type="datetime-local"
                        wire:model.lazy="newFrequency.start_date" />
                </div>

                {{-- Condition de fin --}}
                <flux:radio.group label="Arrêt de la récurrence" wire:model.lazy="newFrequency.end_type">
                    @foreach ($endTypes as $type => $label)
                        <flux:radio :value="$type" :label="$label"></flux:radio>
                    @endforeach
                </flux:radio.group>

                {{-- Date de fin --}}
                @if ($newFrequency['end_type'] === 'until_date')
                    <flux:input label="Date de fin" type="date" wire:model.lazy="newFrequency.end_at" />
                @endif

                @if ($newFrequency['end_type'] === 'occurrences')
                    <flux:input label="Nombre max d’occurrences" type="number" min="1"
                        wire:model.lazy="newFrequency.occurrence_count" />
                @endif

                {{-- Intervalle & unité --}}
                <div class="grid grid-cols-2 gap-4">
                    <flux:input label="Tous les X" type="number" min="1"
                        wire:model.lazy="newFrequency.interval" />
                    <flux:select label="Unité" wire:model.lazy="newFrequency.unit">
                        @foreach ($units as $unit => $label)
                            <flux:select.option :value="$unit" :label="$label"></flux:select.option>
                        @endforeach
                    </flux:select>
                </div>

                {{-- Options selon unité --}}
                {{-- Semaines --}}
                @if ($newFrequency['unit'] === 'week')
                    <div class="grid grid-cols-7 gap-2 mt-2">
                        @foreach ($days as $num => $lbl)
                            <button type="button" wire:click.prevent="toggleWeekday({{ $num }})"
                                class="w-8 h-8 flex items-center justify-center border rounded
                                {{ in_array($num, $newFrequency['weekdays'] ?? [])
                                    ? 'bg-custom text-elix border-elix'
                                    : 'bg-custom' }}">
                                {{ strtoupper(substr($lbl, 0, 1)) }}
                            </button>
                        @endforeach
                    </div>
                @endif

                {{-- Mois --}}
                @if ($newFrequency['unit'] === 'month')
                    <flux:radio.group label="Récurrence mensuelle" wire:model.lazy="freqMonthType" wire:change="updateMonthType">
                        @foreach ($freqMonthTypes as $type => $label)
                            <flux:radio :value="$type" :label="$label"></flux:radio>
                        @endforeach
                    </flux:radio.group>

                    @if ($freqMonthType === 'daysNum')
                        <div class="grid grid-cols-7 gap-2 mt-2">
                            @foreach (range(1, 31) as $day)
                                <button type="button" wire:click.prevent="toggleMonthDay({{ $day }})"
                                    class="w-8 h-8 flex items-center justify-center border rounded
                    {{ in_array($day, $newFrequency['month_days'] ?? [])
                                    ? 'bg-custom text-elix border-elix'
                                    : 'bg-custom' }}">
                                    {{ $day }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    @if ($freqMonthType === 'ordinal')
                        <div class="grid grid-cols-2 gap-4">
                            <flux:select label="Occurrence" wire:model.lazy="newFrequency.month_occurrences.0.ordinal">
                                @foreach ($freqMonthTypesOrdinalList as $num => $lbl)
                                    <flux:select.option :value="$num" :label="$lbl"/>
                                @endforeach
                            </flux:select>
                            <flux:select label="Jour" wire:model.lazy="newFrequency.month_occurrences.0.weekday">
                                @foreach ($days as $num => $lbl)
                                    <flux:select.option :value="$num" :label="$lbl"/>
                                @endforeach
                            </flux:select>
                        </div>
                    @endif
                @endif
            </div>

            <flux:switch label="Active" wire:model.lazy="newRoutine.is_active" />

            <div class="flex mt-6 justify-between">
                <div class="italic cursor-default mr-4">
                    {{ $this->getFrequencySummaryProperty() }}
                </div>
                <flux:button wire:click="create" variant="primary">
                    Créer la routine
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
