@props(['title' => null])

@include('layouts.client', ['title' => $title, 'slot' => $slot])
