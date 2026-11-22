@extends('layouts.app')
@section('title', $ticket->key)
@section('content')
@livewire('ticket-detail', ['ticket' => $ticket->id])
@endsection
