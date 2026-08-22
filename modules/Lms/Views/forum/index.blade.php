@extends('layouts.admin')

@section('title', 'Diễn đàn')
@section('page-title', 'Diễn đàn khóa học')

@section('content')
    @include('lms::forum._shared', ['layoutAdmin' => true])
@endsection
