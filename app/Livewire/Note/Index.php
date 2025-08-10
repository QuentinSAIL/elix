<?php

namespace App\Livewire\Note;

use App\Models\Note;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Component;
use Masmerise\Toaster\Toaster;

class Index extends Component
{
    public $notes;

    public $selectedNote;

    public $user;

    public function mount()
    {
        $this->user = Auth::user();
        $this->notes = Note::where('user_id', $this->user->id)->orderBy('created_at', 'desc')->get();
    }

    #[On('note-saved')]
    public function refresh($note)
    {
        $note = Note::where('user_id', $this->user->id)->find($note['id']);
        if (! $note) {
            return;
        }
        $index = $this->notes->search(fn ($n) => $n->id === $note->id);
        if ($index === false) {
            $this->notes->prepend($note);
        } else {
            $this->notes[$index] = $note;
        }
    }

    public function selectNote($noteId)
    {
        if (! $noteId) {
            $this->selectedNote = null;
        } else {
            $note = Note::where('user_id', $this->user->id)->findOrFail($noteId);
            $this->selectedNote = $note;
        }
    }

    public function delete($id)
    {
        if ($r = Note::where('user_id', $this->user->id)->find($id)) {
            $r->delete();
            Toaster::success(__('Note deleted successfully.'));
            $this->notes = $this->notes->filter(fn ($n) => $n->id !== $id);
            if ($this->selectedNote && $this->selectedNote->id === $id) {
                $this->selectedNote = null;
            }
        }
    }

    public function render()
    {
        return view('livewire.note.index');
    }
}
