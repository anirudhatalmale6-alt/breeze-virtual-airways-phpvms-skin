@extends('app')
@section('title', trans_choice('common.pilot', 2))

@section('content')
    {{--
      Default order = pilot ID (FFT1, FFT2, FFT36 ... / MXY1, MXY2 ...). phpVMS orders the roster by
      internal account number, which put Ray (account 1, MXY2) above Mike (MXY1) on Breeze.
      Clicking a column header still sorts by that column as before.
    --}}
    @php
        // phpVMS writes its own default (sort=id) into the request, so "no sort chosen" arrives as
        // sort=id. The ID header now sorts by pilot_id, so sort=id only ever means the default.
        if (!request()->filled('sort') || request()->query('sort') === 'id') {
            $frQuery = \App\Models\User::with(['airline', 'current_airport', 'fields.field', 'home_airport', 'rank'])
                ->withCount(['awards']);
            if (setting('pilots.hide_inactive')) {
                $frQuery->where('state', \App\Models\Enums\UserState::ACTIVE);
            }
            $users = $frQuery->orderBy('pilot_id')->paginate(20)->withQueryString();
        }
    @endphp
    <div class="row">
        <div class="col-md-12">
            <h2>{{ trans_choice('common.pilot', 2) }}</h2>
            <div class="card">
                <div class="card-body p-4">
                    @include('users.table')
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 text-center">
            {{ $users->withQueryString()->links('pagination.bootstrap-5') }}
        </div>
    </div>
@endsection
