@extends('layouts.admin')
@section('title', 'Chat khóa học')
@section('page-title', 'Chat khóa học')
@section('content')
    @include('lms::chat._room', ['layoutAdmin' => true])
@endsection
