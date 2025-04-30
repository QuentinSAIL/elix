<?php

namespace App\Livewire\Note;

use Flux\Flux;
use Carbon\Carbon;
use App\Models\Note;
use Livewire\Component;
use App\Models\Frequency;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Masmerise\Toaster\Toaster;
use Illuminate\Support\Facades\Auth;

class Index extends Component
{
    public $notes;
    public $selectedNote;
    public $user;

    public function mount()
    {
        $this->user = Auth::user();
        $this->notes = Note::all();
    }

    #[On('noteSaved')]
    public function refresh($note)
    {
        $note = Note::findOrFail($note['id']);
        $index = $this->notes->search(fn($n) => $n->id === $note->id);
        if (!$index) {
            $this->notes->prepend($note);
        } else {
            $this->notes[$index] = $note;
        }
    }

    public function selectNote($noteId)
    {
        if (!$noteId) {
            $this->selectedNote = null;
        } else {
            $note = Note::findOrFail($noteId);
            $this->selectedNote = $note;
        }
    }

    public function delete($id)
    {
        if ($r = Note::find($id)) {
            if (!$r) {
                Toaster::error('Vous ne pouvez pas supprimer cette note.');
                return;
            }
            $r->delete();
            Toaster::success('Note supprimée.');
            $this->notes = $this->notes->filter(fn($n) => $n->id !== $id);
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
