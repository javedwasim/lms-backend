@extends('layouts.master')
@section('content')
    <div class="container">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">Edit Question Note</div>

                    <div class="card-body">
                        <form action="{{ route('question_notes.update', $questionNote->id) }}" method="POST">
                            @csrf
                            @method('PUT')

                            <div class="form-group">
                                <label for="quize_note">Question Note:</label>
                                <textarea class="form-control" id="quize_note" name="quize_note" rows="3" required>{{ old('quize_note', $questionNote->quize_note) }}</textarea>
                                @error('quize_note')
                                <div class="text-danger">{{ $message }}</div>
                                @enderror
                            </div>

                            <button type="submit" class="btn btn-primary">Update</button>
                            <a href="{{ route('question_notes.index') }}" class="btn btn-secondary">Cancel</a>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
