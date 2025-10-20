<?php

use App\Livewire\Note\Index;
use App\Models\Note;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

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
        ->call('closeModal')
        ->assertSet('selectedNote', null);
});

test('can create new note', function () {
    Livewire::test(Index::class)
        ->call('selectNote', null)
        ->assertSet('selectedNote.content', '')
        ->assertNotSet('selectedNote', null);
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
    Note::factory()->for($this->user)->create();
    $initialCount = Note::count();

    Livewire::test(Index::class)
        ->call('delete', '00000000-0000-0000-0000-000000000000')
        ->assertSet('selectedNote', null);

    $this->assertEquals($initialCount, Note::count());
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
