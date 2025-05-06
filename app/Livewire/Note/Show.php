<?php

namespace App\Livewire\Note;

use App\Models\Note;
use Livewire\Component;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class Show extends Component
{
    public $note;
    public $markdownContent;
    public $user;

    public function mount($note)
    {
        $this->user = Auth::user();
        $this->note = $note;
        $this->markdownContent = $note?->content;
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
                'markdownContent' => 'required|string|different:' . $this->note?->content,
            ]);
        } catch (ValidationException $e) {
            Toaster::error(__('Note content is required.'));
            return;
        }

        if ($this->note) {
            $this->note->content = $this->markdownContent;
            $this->note->save();
            Toaster::success(__('Note updated successfully.'));
        } else {
            $this->note = $this->user->notes()->create([
                'content' => $this->markdownContent,
            ]);
            Toaster::success(__('Note created successfully.'));
        }

        $this->dispatch('note-saved', $this->note);
    }

    public function render()
    {
        return view('livewire.note.show');
    }
}
