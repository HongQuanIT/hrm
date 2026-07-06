@extends('layouts.app')

@section('title', 'Chỉnh sửa nhân viên')
@section('page-title', 'Chỉnh sửa nhân viên')

@section('content')
    @include('employees._form', [
        'action' => route('employees.update', $employee),
        'method' => 'PUT',
    ])
@endsection
