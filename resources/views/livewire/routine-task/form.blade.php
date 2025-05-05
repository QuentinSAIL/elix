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
                <span>{{ __('Create') }}</span>
                <flux:icon.plus variant="micro" />
            </span>
        @endif
    </flux:modal.trigger>

    {{-- Modal de création --}}
    <flux:modal name="task-form-{{ $taskId }}" class="w-5/6">
        <div class="space-y-6">
            <div>
                @if ($edition)
                    <flux:heading size="2xl">{{ __('Edit your task') }} « {{ $task->name }} »</flux:heading>
                @else
                    <flux:heading size="2xl">{{ __('Create your task') }}</flux:heading>
                @endif
            </div>

            <flux:input :label="__('Name of the task')" :placeholder="__('Morning task')"
                wire:model.lazy="taskForm.name" />
            <flux:textarea :label="__('Description (optional)')" wire:model.lazy="taskForm.description" />
            <flux:input :label="__('Time of the task (in second)')" placeholder="3600" wire:model.lazy="taskForm.duration"
                type="number" min="1" />
            <flux:input :label="__('Order in the routine')" placeholder="3" wire:model.lazy="taskForm.order" type="number"
                min="1" />

            <flux:switch :label="__('Skip automatically')" wire:model.lazy="taskForm.autoskip" />
            <flux:switch :label="__('Active')" wire:model.lazy="taskForm.is_active" />

            <div class="flex mt-6 justify-between">

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
