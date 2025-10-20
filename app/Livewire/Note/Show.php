<?php

namespace App\Livewire\Note;

use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Show extends Component
{
    public $note;

    public $markdownContent;

    public $user;

    public function mount($note)
    {
        $this->user = Auth::user();
        $this->note = $note;
        $this->markdownContent = $note ? ($note->content ?? '') : '';
    }

    public function updatedMarkdownContent()
    {
        if ($this->markdownContent) {
            $this->save();
        }
    }

    public function save()
    {
        try {
            $this->validate([
                'markdownContent' => [
                    'required',
                    'string',
                    function ($attribute, $value, $fail) {
                        if ($value === $this->note?->content) {
                            $fail(__('The note content must be different.'));
                        }
                    },
                ],
            ]);
        } catch (ValidationException $e) {
            Toaster::error(__('Note content is required.'));

            return;
        }

        if ($this->note && $this->note->id) {
            // Note existante - mise à jour
            $this->note->content = $this->markdownContent;
            $this->note->save();
            Toaster::success(__('Note updated successfully.'));
        } else {
            // Nouvelle note - création
            $this->note = $this->user->notes()->create([
                'content' => $this->markdownContent,
            ]);
            $this->dispatch('note-created', $this->note->id);
            Toaster::success(__('Note created successfully.'));
        }

        $this->dispatch('note-saved', $this->note);
    }

    public function closeNote()
    {
        $this->dispatch('close-note');
    }

    public function deleteNote($id)
    {
        $this->dispatch('delete-note', (string) $id);
        $this->dispatch('close-note');
    }

    public function render()
    {
        return view('livewire.note.show');
    }
}
