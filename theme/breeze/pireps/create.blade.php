{{-- Manual PIREPs removed at the VA's request (15 Nov 2026): flights are reported through ACARS. --}}
@extends('app')
@section('title', trans_choice('common.pirep', 1))

@section('content')
  <div class="row justify-content-center">
    <div class="col-lg-8">
      <div class="card mt-4">
        <div class="card-body text-center p-5">
          <h3 class="mb-3">Flight reports are filed through ACARS</h3>
          <p class="mb-4">Manual flight reports are not used here. Fly your flight with ACARS and the report is filed automatically when you land.</p>
          <a href="{{ route('frontend.flights.index') }}" class="btn btn-primary me-2">Find a flight</a>
          <a href="{{ route('frontend.pireps.index') }}" class="btn btn-outline-secondary">My flight reports</a>
        </div>
      </div>
    </div>
  </div>
@endsection
