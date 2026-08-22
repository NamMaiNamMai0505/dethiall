@extends('layouts.lms-learner')
@section('title', $topic->title)
@section('content')
    @include('lms::forum._show-body', ['layoutAdmin' => false])
@endsection
