@extends('layouts.app')
@section('content')
<div class="card max-w-xl">
    <h1 class="mb-2 text-xl font-semibold">{{ __('admin.import_users') }}</h1>
    <p class="mb-5 text-sm text-slate-600">CSV: name,email,role</p>
    <form method="POST" action="{{ route('admin.users.import.store') }}" enctype="multipart/form-data" class="space-y-4">
        @csrf
        <label class="sr-only" for="csv">CSV</label>
        <input id="csv" name="csv" type="file" accept=".csv,text/csv" class="input w-full" required>
        <button class="btn btn-primary" type="submit">{{ __('admin.import_users') }}</button>
    </form>
</div>
@endsection
