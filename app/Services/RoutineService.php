<?php

namespace App\Services;

use App\Models\Frequency;
use App\Models\Routine;
use Illuminate\Support\Facades\Auth;

class RoutineService
{
    public function saveRoutine(array $routineData, array $frequencyData, $routine = null)
    {
        $user = Auth::user();

        // Create or update frequency
        if ($routine && $routine->frequency) {
            $frequency = $routine->frequency;
            $frequency->update($frequencyData);
        } else {
            $frequency = Frequency::create($frequencyData);
        }

        // Create or update routine
        if ($routine) {
            $routine->update($routineData);
        } else {
            $routineData['frequency_id'] = $frequency->id;
            $routine = $user->routines()->create($routineData);
        }

        return $routine;
    }

    public function deleteRoutine(string $id): bool
    {
        $routine = Routine::find($id);
        if ($routine) {
            $routine->delete();

            return true;
        }

        return false;
    }

    public function updateTaskOrder(array $orderedIds): void
    {
        foreach ($orderedIds as $i => $id) {
            RoutineTask::where('id', $id)->update(['order' => $i + 1]);
        }
    }

    public function deleteTask(RoutineTask $task): void
    {
        DB::transaction(function () use ($task) {
            $order = $task->order;
            $task->delete();

            RoutineTask::where('routine_id', $task->routine_id)->where('order', '>', $order)->decrement('order');
        });
    }

    public function duplicateTask(RoutineTask $task): void
    {
        DB::transaction(function () use ($task) {
            RoutineTask::where('routine_id', $task->routine_id)->where('order', '>', $task->order)->increment('order');

            $newTask = $task->replicate();
            $newTask->order = $task->order + 1;
            $newTask->routine_id = $task->routine_id;
            $newTask->save();
        });
    }

    public function saveTask(array $taskData, $routine, $task = null)
    {
        if ($task) {
            $task->update($taskData);
        } else {
            $routine->tasks()->create($taskData);
        }
    }
}
