@extends('layouts.app')

@section('title', 'Thêm nhân viên')
@section('page-title', 'Thêm mới nhân viên')

@section('content')
    @include('employees._form', [
        'action' => route('employees.store'),
        'method' => 'POST',
        'employee' => null,
    ])
@endsection
