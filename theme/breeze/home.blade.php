@extends('app')
@section('title', __('home.welcome.title'))
@section('body_class', 'd-none')

@php
    use App\Models\Bid;
    use App\Models\Enums\PirepSource;
    use App\Models\Enums\PirepState;
    use App\Models\Enums\UserState;
    use App\Models\Pirep;
    use App\Models\User;
    use Illuminate\Support\Facades\DB;

    // Anything that isn't a real, filed flight is excluded from the numbers
    $notFiled = [PirepState::DRAFT, PirepState::IN_PROGRESS, PirepState::CANCELLED, PirepState::REJECTED, PirepState::DELETED];

    $monthStart = now()->startOfMonth();
    $distUnit = setting('units.distance', 'nmi');

    $filed = DB::table('pireps')->whereNull('deleted_at')->whereNotIn('state', $notFiled);

    $tot_flights = (clone $filed)->count();
    $tot_distance = (float) (clone $filed)->sum('distance');
    $tot_minutes = (int) (clone $filed)->sum('flight_time');
    $tot_pilots = User::where('state', '!=', UserState::DELETED)->count();

    $mo_flights = (clone $filed)->where('submitted_at', '>=', $monthStart)->count();
    $mo_distance = (float) (clone $filed)->where('submitted_at', '>=', $monthStart)->sum('distance');
    $mo_minutes = (int) (clone $filed)->where('submitted_at', '>=', $monthStart)->sum('flight_time');
    $mo_pilots = (int) (clone $filed)->where('submitted_at', '>=', $monthStart)->distinct()->count('user_id');

    // "Dispatched" = a flight someone has bid on, or one that is airborne right now
    $dispatched = Bid::with(['flight.dpt_airport', 'flight.arr_airport', 'user', 'aircraft'])
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get();

    $airborne = Pirep::with(['user', 'aircraft'])
        ->where('state', PirepState::IN_PROGRESS)
        ->whereNull('deleted_at')
        ->orderBy('updated_at', 'desc')
        ->get();

    $latest = Pirep::with(['airline', 'aircraft', 'user'])
        ->whereNotIn('state', $notFiled)
        ->orderBy('submitted_at', 'desc')
        ->take(10)
        ->get();

    $hhmm = function ($minutes) {
        $minutes = (int) $minutes;

        return intdiv($minutes, 60).'h '.str_pad($minutes % 60, 2, '0', STR_PAD_LEFT).'m';
    };
@endphp

@section('fullwidth')

    {{-- ============================= HERO =============================== --}}
    {{-- Drop a ramp photo in as --bz-hero-image and it takes over the gradient:
         style="--bz-hero-image: url('{{ public_asset('/assets/breeze/hero.jpg') }}')" --}}
    <section class="bz-hero">
        <div class="bz-hero__inner">
            {{-- knockout version of the logo: the navy wordmark is recoloured white
                 so it reads against the dark hero. logo.png stays for the white header. --}}
            <img class="logo" src="{{ public_asset('/assets/breeze/logo-light.png') }}"
                alt="{{ config('app.name') }}">
            <p class="tagline">Fly the Breeze &mdash; on VATSIM &amp; IVAO</p>
            <div class="d-flex flex-wrap gap-2 justify-content-center">
                <a href="{{ url('/register') }}" class="btn btn-breeze btn-lg px-4">
                    <i class="bi bi-person-vcard me-1"></i> Join the Crew
                </a>
                <a href="{{ route('frontend.livemap.index') }}" class="btn btn-outline-light btn-lg px-4">
                    <i class="bi bi-globe me-1"></i> Live Map
                </a>
            </div>
        </div>
    </section>

    {{-- ========================== STATS BAND ============================ --}}
    <section class="bz-stats">
        <div class="container">
            <div class="row row-cols-2 row-cols-lg-4 g-3">
                <div class="col">
                    <div class="bz-stat">
                        <div class="lbl">Total flights</div>
                        <div class="val">{{ number_format($tot_flights) }}</div>
                        <div class="sub"><b>{{ number_format($mo_flights) }}</b> flights this month</div>
                    </div>
                </div>
                <div class="col">
                    <div class="bz-stat">
                        <div class="lbl">Total distance</div>
                        <div class="val">{{ number_format($tot_distance) }}</div>
                        <div class="sub"><b>{{ number_format($mo_distance) }}</b> {{ $distUnit }} this month</div>
                    </div>
                </div>
                <div class="col">
                    <div class="bz-stat">
                        <div class="lbl">Total hours</div>
                        <div class="val">{{ number_format(intdiv($tot_minutes, 60)) }}</div>
                        <div class="sub"><b>{{ number_format(intdiv($mo_minutes, 60)) }}</b> hours this month</div>
                    </div>
                </div>
                <div class="col">
                    <div class="bz-stat">
                        <div class="lbl">Total pilots</div>
                        <div class="val">{{ number_format($tot_pilots) }}</div>
                        <div class="sub"><b>{{ number_format($mo_pilots) }}</b> active this month</div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    {{-- ========================== WELCOME ============================== --}}
    {{-- Placeholder copy. Send me yours and it goes straight in here, or I can
         wire this block to an editable page in the phpVMS admin. --}}
    <section class="bz-welcome">
        <div class="container">
            <h2>Breeze Virtual Airways</h2>
            <hr>
            <div class="row">
                <div class="col-lg-10 offset-lg-1">
                    <p><strong>Welcome to Breeze Virtual Airways!</strong></p>
                    <p>CALLSIGN &mdash; <strong>Breeze</strong></p>
                    <p>
                        We fly on both <strong>VATSIM</strong> and <strong>IVAO</strong>, so bring whichever network
                        you already have an account on. If you don't have one yet, both are free to join and we'll
                        point you at the right place during your application.
                    </p>
                    <p>
                        All pilot reporting is handled through <strong>smartCARS</strong>. Bid on a route from the
                        dispatch system, fly it in your simulator, and smartCARS files the report for you &mdash;
                        block times, fuel burn, landing rate and the flown route all land in your logbook
                        automatically.
                    </p>
                    <p>
                        Our route network and fleet are built from real-world Breeze Airways operations, and the
                        schedules are refreshed as the real airline's change.
                    </p>
                </div>
            </div>
        </div>
    </section>

    {{-- =========================== LIVE MAP ============================= --}}
    <section class="bz-map-wrap">
        {{ Widget::liveMap() }}
    </section>

    {{-- ===================== DISPATCH + LATEST FLIGHTS ================== --}}
    <section class="bz-ops">
        <div class="container">

            <div class="bz-panel">
                <div class="bz-panel__head">
                    <span>Dispatched Flights</span>
                    <span class="count">{{ $dispatched->count() + $airborne->count() }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pilot</th>
                                <th>Flight No.</th>
                                <th>Dep ICAO</th>
                                <th>Arr ICAO</th>
                                <th>Aircraft</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($airborne as $p)
                                <tr>
                                    <td>{{ optional($p->user)->name_private }}</td>
                                    <td><span class="badge bz-badge">{{ $p->ident }}</span></td>
                                    <td>{{ $p->dpt_airport_id }}</td>
                                    <td>{{ $p->arr_airport_id }}</td>
                                    <td class="text-body-secondary">{{ optional($p->aircraft)->ident }}</td>
                                    <td><span class="badge text-bg-success">Airborne</span></td>
                                </tr>
                            @endforeach
                            @foreach ($dispatched as $bid)
                                <tr>
                                    <td>{{ optional($bid->user)->name_private }}</td>
                                    <td><span class="badge bz-badge">{{ optional($bid->flight)->ident }}</span></td>
                                    <td>{{ optional($bid->flight)->dpt_airport_id }}</td>
                                    <td>{{ optional($bid->flight)->arr_airport_id }}</td>
                                    <td class="text-body-secondary">{{ optional($bid->aircraft)->ident }}</td>
                                    <td><span class="badge text-bg-secondary">Booked</span></td>
                                </tr>
                            @endforeach
                            @if ($airborne->isEmpty() && $dispatched->isEmpty())
                                <tr>
                                    <td colspan="6" class="bz-empty">There are no active bookings.</td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="bz-panel">
                <div class="bz-panel__head">
                    <span>Latest 10 Flights</span>
                    <a class="count text-white text-decoration-none"
                        href="{{ route('frontend.pilots.index') }}">Roster</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Pilot</th>
                                <th>Flight Number</th>
                                <th>Dep ICAO</th>
                                <th>Arr ICAO</th>
                                <th>Aircraft</th>
                                <th class="text-center">ACARS</th>
                                <th class="text-end">Block</th>
                                <th class="text-end">Landing Rate</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($latest as $p)
                                <tr>
                                    <td>
                                        <i class="bi bi-person-circle me-1 text-body-secondary"></i>
                                        {{ optional($p->user)->name_private }}
                                    </td>
                                    <td><span class="badge bz-badge">{{ $p->ident }}</span></td>
                                    <td>
                                        <i class="bi bi-geo-alt text-body-secondary"></i>
                                        <a class="text-decoration-none"
                                            href="{{ route('frontend.airports.show', [$p->dpt_airport_id]) }}">{{ $p->dpt_airport_id }}</a>
                                    </td>
                                    <td>
                                        <i class="bi bi-geo-alt text-body-secondary"></i>
                                        <a class="text-decoration-none"
                                            href="{{ route('frontend.airports.show', [$p->arr_airport_id]) }}">{{ $p->arr_airport_id }}</a>
                                    </td>
                                    <td class="text-body-secondary">{{ optional($p->aircraft)->ident }}</td>
                                    <td class="text-center">
                                        @if ($p->source === PirepSource::ACARS)
                                            <i class="bi bi-check-lg" style="color: var(--bz-blue);"
                                                title="{{ $p->source_name ?? 'ACARS' }}"></i>
                                        @else
                                            <span class="text-body-secondary" title="Filed manually">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-end font-monospace">{{ $hhmm($p->flight_time) }}</td>
                                    <td class="text-end font-monospace">
                                        {{ $p->landing_rate ? number_format($p->landing_rate).'fpm' : '—' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="bz-empty">No flights have been filed yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>
@endsection
