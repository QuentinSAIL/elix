<div>
    {{-- Modal de création --}}
    <flux:modal.trigger name="task-form-{{ $taskId }}" id="task-form-{{ $taskId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer">
        @if ($edition)
            <span class="flex items-center justify-center space-x-2">
                <flux:icon.pencil-square class="cursor-pointer ml-2" variant="micro" />
            </span>
        @else
            <span class="flex items-center justify-center space-x-2 rounded-lg">
                <span>Créer</span>
                <flux:icon.plus variant="micro" />
            </span>
        @endif
    </flux:modal.trigger>

    {{-- Modal de création --}}
    <flux:modal name="task-form-{{ $taskId }}" class="w-5/6">
        <div class="space-y-6">
            <div>
                @if ($edition)
                    <flux:heading size="2xl">Modifier votre tâche « {{ $task->name }} »</flux:heading>
                @else
                    <flux:heading size="2xl">Créez votre tâche</flux:heading>
                @endif
            </div>

            <flux:input label="Nom de la tâche" placeholder="task matinal" wire:model.lazy="taskForm.name" />
            <flux:textarea label="Description (optionnel)" wire:model.lazy="taskForm.description" />
            <flux:input label="Temps de la tâche en seconde" placeholder="3600" wire:model.lazy="taskForm.duration"
                type="number" min="1" />
            <flux:input label="Ordre dans la routine" placeholder="3" wire:model.lazy="taskForm.order" type="number"
                min="1" />

            <flux:switch label="Autoskip" wire:model.lazy="taskForm.autoskip" />
            <flux:switch label="Active" wire:model.lazy="taskForm.is_active" />

            <div class="flex mt-6 justify-between">

                <flux:button wire:click="save" variant="primary" wire:keydown.enter="save">
                    @if ($edition)
                        Modifier
                    @else
                        Créer
                    @endif
                </flux:button>
            </div>
        </div>
    </flux:modal>
</div>
