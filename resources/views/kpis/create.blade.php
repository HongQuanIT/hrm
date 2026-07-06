@extends('layouts.app')

@section('title', 'Thêm mục tiêu KPI')
@section('page-title', 'Thêm mục tiêu KPI')

@section('content')
    @include('kpis._form', ['action' => route('kpis.store'), 'method' => 'POST'])
@endsection
