<?php

namespace App\Livewire\Note;

use Flux\Flux;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Livewire\Component;
use App\Models\Note;
use App\Models\Frequency;
use Illuminate\Support\Facades\Auth;
use Masmerise\Toaster\Toaster;

class Index extends Component
{
    // La collection de notes à afficher
    public $notes;
    public $user;

    // Les champs du formulaire de création
    public $newNote = [
        'name'    => '',
        'content' => '',
    ];

    // Règles de validation pour la création
    protected $rules = [
        'newNote.name'    => 'required|string|max:255',
        'newNote.content' => 'required|string',
    ];

    // Initialisation : on charge toutes les notes
    public function mount()
    {
        $this->user = Auth::user();
        $this->notes = Note::all();
    }

    // Création d'une nouvelle note
    public function create()
    {
        $this->validate();

        $note = $this->user->notes()->create([
            'name'    => $this->newNote['name'],
            'content' => $this->newNote['content'],
        ]);

        $this->notes->push($note);
        $this->newNote = ['name' => '', 'content' => ''];
    }

    // Suppression d'une note
    public function delete($id)
    {
        if ($r = Note::find($id)) {
            $r->delete();
            Toaster::success("La note « {$r->name} » a été supprimée.");
            $this->notes = $this->notes->filter(fn($n) => $n->id !== $id);
        }
    }

    public function render()
    {
        return view('livewire.note.index');
    }
}
