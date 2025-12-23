@extends('layouts.public')

@section('content')
  <div class="card">
    <h2>Edit Ticket #{{ $ticket->id }}</h2>
    <form class="mt-3" action="{{ route('tickets.update',$ticket) }}" method="post">
      @csrf @method('PUT')

      <div class="grid grid-2">
        <div>
          <label>Judul</label>
          <input class="input mt-2" name="title" value="{{ old('title',$ticket->title) }}">
          @error('title')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>
        <div>
          <label>Pelapor (Nama)</label>
          <input class="input mt-2" name="requester_name" value="{{ old('requester_name',$ticket->requester_name) }}">
          @error('requester_name')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>
        <div>
          <label>Email Pelapor</label>
          <input class="input mt-2" name="requester_email" value="{{ old('requester_email',$ticket->requester_email) }}">
          @error('requester_email')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>
        <div>
          <label>Kategori</label>
          <select class="mt-2" name="category">
            @foreach($categories as $c)
              <option value="{{ $c }}" @selected(old('category',$ticket->category)==$c)>{{ ucfirst($c) }}</option>
            @endforeach
          </select>
          @error('category')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="grid grid-2 mt-3">
        <div>
          <label>Prioritas</label>
          <select class="mt-2" name="priority">
            @foreach($priorities as $p)
              <option value="{{ $p }}" @selected(old('priority',$ticket->priority)==$p)>{{ ucfirst($p) }}</option>
            @endforeach
          </select>
          @error('priority')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>

        <div>
          <label>Status</label>
          <select class="mt-2" name="status">
            @foreach($statuses as $s)
              <option value="{{ $s }}" @selected(old('status',$ticket->status)==$s)>
                {{ $s === 'in_progress' ? 'In Progress' : ucfirst($s) }}
              </option>
            @endforeach
          </select>
          @error('status')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
        </div>
      </div>

      <div class="mt-3">
        <label>Deskripsi</label>
        <textarea class="mt-2" rows="6" name="description">{{ old('description',$ticket->description) }}</textarea>
        @error('description')<div class="mt-2" style="color:#ffb4b4">{{ $message }}</div>@enderror
      </div>

      <div class="mt-4">
        <button class="btn" type="submit">Update</button>
        <a class="btn" href="{{ route('tickets.index') }}">Kembali</a>
      </div>
    </form>
  </div>
@endsection
