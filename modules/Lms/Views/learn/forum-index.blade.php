@extends('layouts.lms-learner')
@section('title', 'Diễn đàn')
@section('content')
    @include('lms::forum._shared', ['layoutAdmin' => false])
@endsection
