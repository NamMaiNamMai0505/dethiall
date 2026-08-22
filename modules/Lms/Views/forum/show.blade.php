@extends('layouts.admin')
@section('title', $topic->title)
@section('page-title', 'Chủ đề diễn đàn')
@section('content')
    @include('lms::forum._show-body', ['layoutAdmin' => true])
@endsection
