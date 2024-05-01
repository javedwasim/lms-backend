@extends('layouts.master')

@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Question Notes</div>

                    <div class="card-body">
                        @if (isset($questionNotes[0]->id))
                            <a href="{{ route('question_notes.create') }}" class="btn btn-success mb-2 disabled">Create Note</a>
                        @else
                            <a href="{{ route('question_notes.create') }}" class="btn btn-success mb-2">Create New</a>
                        @endif
                        <table class="table">
                            <thead>
                            <tr>
                                <th>ID</th>
                                <th>Question Note</th>
                                <th>Action</th>
                            </tr>
                            </thead>
                            <tbody>
                            @foreach ($questionNotes as $questionNote)
                                <tr>
                                    <td>{{ $questionNote->id }}</td>
                                    <td style="white-space: pre-line;">{{ $questionNote->quize_note }}</td>
                                    <td>
                                        <form action="{{ route('question_notes.destroy', $questionNote->id) }}" method="POST">
{{--                                            <a class="btn btn-info" href="{{ route('question_notes.show', $questionNote->id) }}">Show</a>--}}
                                            <a class="btn btn-primary" href="{{ route('question_notes.edit', $questionNote->id) }}">Edit</a>
{{--                                            @csrf--}}
{{--                                            @method('DELETE')--}}
{{--                                            <button type="submit" class="btn btn-danger">Delete</button>--}}
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
