<?php

use App\Models\User;
use App\Models\Note;
use Livewire\Livewire;
use App\Livewire\Note\Index;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->actingAs($this->user);
});

test('note index component can be rendered', function () {
    $note = Note::factory()->for($this->user)->create();

    Livewire::test(Index::class)
        ->assertStatus(200)
        ->assertSet('notes.0.content', $note->content);
});

test('can select note', function () {
    $note = Note::factory()->for($this->user)->create();

    Livewire::test(Index::class)
        ->call('selectNote', $note->id)
        ->assertSet('selectedNote.id', $note->id);
});

test('can deselect note', function () {
    $note = Note::factory()->for($this->user)->create();

    Livewire::test(Index::class)
        ->set('selectedNote', $note)
        ->call('selectNote', null)
        ->assertSet('selectedNote', null);
});

test('can delete note', function () {
    $note = Note::factory()->for($this->user)->create();

    Livewire::test(Index::class)
        ->set('selectedNote', $note)
        ->call('delete', $note->id)
        ->assertSet('selectedNote', null);

    $this->assertSoftDeleted('notes', ['id' => $note->id]);
});

test('handles deleting non-existent note', function () {
    Livewire::test(Index::class)
        ->call('delete', '00000000-0000-0000-0000-000000000000');
});

test('can refresh notes after save', function () {
    $note = Note::factory()->for($this->user)->create([
        'content' => 'Original Content',
    ]);

    Livewire::test(Index::class)
        ->call('refresh', ['id' => $note->id])
        ->assertSet('notes.0.content', 'Original Content');

    // Update the note
    $note->update(['content' => 'Updated Content']);

    Livewire::test(Index::class)
        ->call('refresh', ['id' => $note->id])
        ->assertSet('notes.0.content', 'Updated Content');
});

test('can add new note to list', function () {
    $note = Note::factory()->for($this->user)->create();

    Livewire::test(Index::class)
        ->call('refresh', ['id' => $note->id])
        ->assertSet('notes.0.content', $note->content);
});

test('can handle note not found during refresh', function () {
    Livewire::test(Index::class)
        ->call('refresh', ['id' => '00000000-0000-0000-0000-000000000000'])
        ->assertStatus(200);
});
