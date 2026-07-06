@extends('layouts.app')

@section('title', 'Chỉnh sửa KPI')
@section('page-title', 'Chỉnh sửa KPI')

@section('content')
    @include('kpis._form', ['action' => route('kpis.update', $kpi), 'method' => 'PUT'])
@endsection
