<?php

namespace App\Http\Controllers;

use App\Models\QuestionNote;
use Illuminate\Http\Request;

class QuestionNoteController extends Controller
{
    public function index()
    {
        $questionNotes = QuestionNote::all();
        return view('admin.question_notes.index', compact('questionNotes'));
    }

    public function create()
    {
        return view('admin.question_notes.create');
    }

    public function store(Request $request)
    {
        $request->validate([
            'quize_note' => 'required|string',
        ]);

        QuestionNote::create($request->all());

        return redirect()->route('question_notes.index')
            ->with('success','Question Note created successfully.');
    }

    public function show(QuestionNote $questionNote)
    {
        return view('admin.question_notes.show',compact('questionNote'));
    }

    public function edit(QuestionNote $questionNote)
    {
        return view('admin.question_notes.edit',compact('questionNote'));
    }

    public function update(Request $request, QuestionNote $questionNote)
    {
        $request->validate([
            'quize_note' => 'required|string',
        ]);

        $questionNote->update($request->all());

        return redirect()->route('question_notes.index')
            ->with('success','Question Note updated successfully');
    }

    public function destroy(QuestionNote $questionNote)
    {
        $questionNote->delete();

        return redirect()->route('question_notes.index')
            ->with('success','Question Note deleted successfully');
    }
}
