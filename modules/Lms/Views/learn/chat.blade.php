@extends('layouts.lms-learner')
@section('title', 'Chat')
@section('content')
    @include('lms::chat._room', ['layoutAdmin' => false])
@endsection
