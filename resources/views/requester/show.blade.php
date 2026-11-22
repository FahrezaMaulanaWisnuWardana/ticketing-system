@extends('layouts.app')
@section('title', $ticket->key)
@section('content')
@livewire('requester-conversation', ['ticket' => $ticket->id])
@endsection
