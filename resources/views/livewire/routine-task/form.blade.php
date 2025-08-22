<div>
    <flux:modal.trigger name="task-form-{{ $taskId }}" id="task-form-{{ $taskId }}"
        class="w-full h-full flex items-center justify-center cursor-pointer" role="button" tabindex="0"
        aria-label="{{ $edition ? __('Edit task') : __('Create task') }}">
        <div class="w-full text-center px-2 py-2 hover rounded-t-lg">
            @if ($edition)
                <span class="flex items-center justify-center space-x-2">
                    <flux:icon.pencil-square class="cursor-pointer ml-2" variant="micro" aria-hidden="true" />
                </span>
            @else
                <span class="flex items-center justify-center space-x-2 rounded-lg">
                    <span>{{ __('Create') }}</span>
                    <flux:icon.plus variant="micro" aria-hidden="true" />
                </span>
            @endif
        </div>
    </flux:modal.trigger>

    <flux:modal name="task-form-{{ $taskId }}" class="w-5/6" wire:cancel="resetForm">
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
            <x:atoms.duration-picker :label="__('Duration (optional)')" wire:model.lazy="duration" />

            <flux:input :label="__('Order in the routine')" placeholder="3" wire:model.lazy="taskForm.order"
                type="number" min="1" />
            <flux:switch :label="__('Skip automatically')" wire:model.lazy="taskForm.autoskip" />
            <flux:switch :label="__('Active')" wire:model.lazy="taskForm.is_active" />

            <div class="flex gap-2 mt-6 justify-end pt-4">
                <flux:modal.close>
                    <flux:button variant="ghost" class="px-4">
                        {{ __('Annuler') }}
                    </flux:button>
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
