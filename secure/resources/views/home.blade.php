@extends('shared.layout')

@section('customSeoTags')
    <meta name="description" content="Willkommen auf der Startseite von {{ config('app.name', 'App') }}. Hier findest du die neuesten Updates und Informationen.">
    <meta name="keywords" content="startseite, home, {{ config('app.name', 'App') }}, sicherheit, updates">
@endsection

@section('customStyles')
    <style>
        .nav-link.home {
            background-color: var(--bs-primary);
            color: #fff;
        }

        .nav-link.home:hover {
            background-color: var(--bs-primary);
            color: #fff !important;
            transform: none;
            cursor: default;
        }
    </style>
@endsection

@section('content')
    <br />
    <h1 class="fw-bold">Startseite</h1>
    <p class="text-body-secondary">Dies ist die Startseite.</p>
@endsection
