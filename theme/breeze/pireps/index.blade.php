{{-- Manual PIREPs removed at the VA's request (15 Nov 2026): flights are reported through ACARS. --}}
@extends('app')
@section('title', trans_choice('common.pirep', 2))

@section('content')
  <div class="row">
    <div class="col-md-12">
      <h2>{{ trans_choice('pireps.pilotreport', 2) }}</h2>
      @include('flash::message')
      @include('pireps.table')
    </div>
  </div>
  
@endsection

