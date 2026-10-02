@extends('app')
@section('title', __('home.welcome.title'))
@section('body_class', 'd-none')

@php
    use App\Models\Bid;
    use App\Models\Enums\PirepSource;
    use App\Models\Enums\PirepState;
    use App\Models\Enums\PirepStatus;
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
    $airborne = Pirep::with(['user', 'aircraft', 'flight', 'dpt_airport'])
        ->where('state', PirepState::IN_PROGRESS)
        ->whereNull('deleted_at')
        ->orderBy('updated_at', 'desc')
        ->get();

    /*
     * A pilot keeps his bid while he flies it, so without this the same flight
     * shows twice - once live, once still sitting as "Booked". Drop the bid row
     * when that pilot already has this flight in the air.
     */
    $flyingNow = $airborne->filter(fn ($p) => filled($p->flight_id))
        ->map(fn ($p) => $p->user_id.'|'.$p->flight_id)
        ->all();

    $dispatched = Bid::with(['flight.dpt_airport', 'flight.arr_airport', 'user', 'aircraft'])
        ->orderBy('created_at', 'desc')
        ->take(10)
        ->get()
        ->reject(fn ($bid) => in_array($bid->user_id.'|'.$bid->flight_id, $flyingNow, true));

    $latest = Pirep::with(['airline', 'aircraft', 'user', 'flight', 'dpt_airport'])
        ->whereNotIn('state', $notFiled)
        ->orderBy('submitted_at', 'desc')
        ->take(10)
        ->get();

    $hhmm = function ($minutes) {
        $minutes = (int) $minutes;

        return intdiv($minutes, 60).'h '.str_pad($minutes % 60, 2, '0', STR_PAD_LEFT).'m';
    };

    /*
     * Live status pill. The wording comes from phpVMS's own ACARS status list
     * (PirepStatus), so whatever smartCARS reports - boarding, pushback, taxi,
     * enroute, final approach - is what shows here. The map below only decides
     * the colour and the icon for each phase.
     */
    $statusStyle = [
        PirepStatus::INITIATED     => ['sched',  'bi-clock'],
        PirepStatus::SCHEDULED     => ['sched',  'bi-clock'],
        PirepStatus::BOARDING      => ['board',  'bi-people-fill'],
        PirepStatus::RDY_START     => ['ground', 'bi-power'],
        PirepStatus::PUSHBACK_TOW  => ['ground', 'bi-arrow-left-right'],
        PirepStatus::DEPARTED      => ['ground', 'bi-box-arrow-right'],
        PirepStatus::RDY_DEICE     => ['ground', 'bi-snow'],
        PirepStatus::STRT_DEICE    => ['ground', 'bi-snow2'],
        PirepStatus::GRND_RTRN     => ['alert',  'bi-arrow-counterclockwise'],
        PirepStatus::TAXI          => ['ground', 'bi-signpost-split'],
        PirepStatus::TAKEOFF       => ['air',    'bi-airplane-engines'],
        PirepStatus::INIT_CLIM     => ['air',    'bi-arrow-up-right'],
        PirepStatus::AIRBORNE      => ['air',    'bi-airplane-fill'],
        PirepStatus::ENROUTE       => ['air',    'bi-airplane-fill'],
        PirepStatus::DIVERTED      => ['alert',  'bi-exclamation-triangle-fill'],
        PirepStatus::APPROACH      => ['arrive', 'bi-arrow-down-right'],
        PirepStatus::APPROACH_ICAO => ['arrive', 'bi-arrow-down-right'],
        PirepStatus::ON_FINAL      => ['arrive', 'bi-arrow-down-right'],
        PirepStatus::LANDING       => ['arrive', 'bi-airplane'],
        PirepStatus::LANDED        => ['arrive', 'bi-airplane'],
        PirepStatus::ON_BLOCK      => ['done',   'bi-check-circle-fill'],
        PirepStatus::ARRIVED       => ['done',   'bi-check-circle-fill'],
        PirepStatus::CANCELLED     => ['alert',  'bi-x-circle-fill'],
        PirepStatus::EMERG_DESCENT => ['alert',  'bi-exclamation-octagon-fill'],
        PirepStatus::PAUSED        => ['paused', 'bi-pause-fill'],
    ];

    $statusPill = function ($code) use ($statusStyle) {
        [$tone, $icon] = $statusStyle[$code] ?? ['sched', 'bi-dot'];

        return [
            'tone'  => $tone,
            'icon'  => $icon,
            'label' => PirepStatus::label($code),
        ];
    };

    /*
     * smartCARS reports one flat "Enroute" from takeoff until descent - it never
     * sends a climb or cruise state, and its vertical speed field arrives as 0.
     * Altitude IS recorded on every position report, so work the phase out from
     * the last few altitudes instead: comparing the newest against one from a
     * couple of minutes back tells us whether he's going up, down or holding.
     */
    /*
     * ---------------------------------------------------------------------
     * ON TIME / DELAYED
     * ---------------------------------------------------------------------
     * flights.dpt_time is just a clock string like "06:00" with no date and no
     * timezone. Ray confirmed on 1 Oct that the September 2026 schedule export
     * holds ZULU times, so they are compared straight against UTC and no airport
     * timezone is involved.
     *
     * If a future schedule ever comes off the export in LOCAL time at the
     * departure airport, set this back to true: the local branch converts using
     * that airport's own timezone against the DATE THE FLIGHT ACTUALLY HAPPENED,
     * not a fixed offset, so it stays correct across the daylight saving change.
     */
    $SCHEDULE_TIMES_ARE_LOCAL = false;
    $ON_TIME_MINUTES = 15; // the usual airline definition

    $departurePunctuality = function ($pirep) use ($SCHEDULE_TIMES_ARE_LOCAL, $ON_TIME_MINUTES) {
        $std = optional($pirep->flight)->dpt_time;

        /*
         * CAREFUL: phpVMS casts block_off_time through CarbonCast, which hands
         * back a Carbon object even when the column is NULL. So ask the raw
         * database value whether this flight has actually pushed back.
         *
         * A flight that has pushed is measured against when it pushed. One still
         * sitting on stand is measured against right now - that is how an airport
         * board works: sit past your slot and you go Delayed while still boarding.
         */
        $hasPushed = filled($pirep->getRawOriginal('block_off_time'));
        $actual = $hasPushed ? $pirep->block_off_time : now();

        // Nothing to compare against
        if (blank($std) || !preg_match('/^(\d{1,2}):(\d{2})/', trim($std), $m)) {
            return null;
        }

        $tz = $SCHEDULE_TIMES_ARE_LOCAL
            ? (optional($pirep->dpt_airport)->timezone ?: 'UTC')
            : 'UTC';

        try {
            $actualLocal = $actual->copy()->setTimezone($tz);
        } catch (\Throwable $e) {
            return null; // unknown timezone string on the airport row
        }

        // The clock time has no date, so take whichever day sits closest to the
        // actual push - that handles a 23:50 departure pushing at 00:10.
        $best = null;
        foreach ([-1, 0, 1] as $dayShift) {
            $candidate = $actualLocal->copy()
                ->addDays($dayShift)
                ->setTime((int) $m[1], (int) $m[2], 0);

            $diff = $candidate->diffInMinutes($actualLocal, false);
            if ($best === null || abs($diff) < abs($best)) {
                $best = $diff;
            }
        }

        if ($best === null) {
            return null;
        }

        $late = (int) round($best); // positive = pushed after the scheduled time

        $describe = function ($mins) {
            $mins = abs($mins);
            return $mins >= 60
                ? intdiv($mins, 60).'h '.($mins % 60).'m'
                : $mins.'m';
        };

        if (abs($late) <= $ON_TIME_MINUTES) {
            return ['tone' => 'ontime', 'icon' => 'bi-check-circle-fill', 'label' => 'On time'];
        }

        if ($late > 0) {
            return ['tone' => 'late', 'icon' => 'bi-exclamation-circle-fill', 'label' => 'Delayed '.$describe($late)];
        }

        // Still on stand and the slot hasn't come round yet - that's just on time,
        // not "early". Only a flight that actually pushed early gets called early.
        return $hasPushed
            ? ['tone' => 'early',  'icon' => 'bi-arrow-up-circle-fill', 'label' => 'Early '.$describe($late)]
            : ['tone' => 'ontime', 'icon' => 'bi-check-circle-fill', 'label' => 'On time'];
    };

    $enrouteCodes = [PirepStatus::ENROUTE, PirepStatus::AIRBORNE, PirepStatus::TAKEOFF, PirepStatus::INIT_CLIM];
    $LEVEL_BAND_FT = 400; // anything inside this over the sample window counts as level

    $verticalPhase = function ($pirepId) use ($LEVEL_BAND_FT) {
        $alts = DB::table('acars')
            ->where('pirep_id', $pirepId)
            ->whereNotNull('altitude_msl')
            ->orderByDesc('created_at')
            ->limit(8)
            ->pluck('altitude_msl');

        if ($alts->count() < 2) {
            return null;
        }

        $change = (float) $alts->first() - (float) $alts->last();

        if ($change > $LEVEL_BAND_FT) {
            return ['tone' => 'air', 'icon' => 'bi-arrow-up-right', 'label' => 'Climbing'];
        }

        if ($change < -$LEVEL_BAND_FT) {
            return ['tone' => 'arrive', 'icon' => 'bi-arrow-down-right', 'label' => 'Descending'];
        }

        return ['tone' => 'air', 'icon' => 'bi-airplane-fill', 'label' => 'Cruise'];
    };
@endphp

@section('fullwidth')

    {{-- ============================= HERO =============================== --}}
    {{-- Swap the artwork by replacing public/assets/breeze/hero.jpg. It carries the
         wordmark and the tagline itself, so nothing is overlaid on top of it. --}}
    <section class="bz-hero">
        <img class="bz-hero__art" src="{{ public_asset('/assets/breeze/hero.jpg') }}"
            alt="{{ config('app.name') }} - Fly Further Together">
        <div class="bz-hero__cta">
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
    <section class="bz-stats" id="bz-live-stats">
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
    {{-- table => false hides phpVMS's own flight strip under the map; the
         Dispatched Flights panel below says the same thing, better. --}}
    <section class="bz-map-wrap">
        {{ Widget::liveMap(['table' => false]) }}
    </section>

    {{-- ===================== DISPATCH + LATEST FLIGHTS ================== --}}
    <section class="bz-ops" id="bz-live-ops">
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
                                <th>Departure</th>
                                <th class="text-end d-none d-md-table-cell">Last report</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($airborne as $p)
                                @php
                                    $st = $statusPill($p->status);
                                    // Replace the flat "Enroute" with the real phase of flight
                                    if (in_array($p->status, $enrouteCodes, true) && ($phase = $verticalPhase($p->id))) {
                                        $st = $phase;
                                    }
                                @endphp
                                <tr>
                                    <td>{{ optional($p->user)->name_private }}</td>
                                    <td><span class="badge bz-badge">{{ $p->ident }}</span></td>
                                    <td>{{ $p->dpt_airport_id }}</td>
                                    <td>{{ $p->arr_airport_id }}</td>
                                    <td class="text-body-secondary">{{ optional($p->aircraft)->ident }}</td>
                                    <td>
                                        <span class="bz-st bz-st--{{ $st['tone'] }}">
                                            <i class="bi {{ $st['icon'] }}"></i>{{ $st['label'] }}
                                        </span>
                                    </td>
                                    <td>
                                        @php($otpLive = $departurePunctuality($p))
                                        @if ($otpLive)
                                            <span class="bz-st bz-st--{{ $otpLive['tone'] }}">
                                                <i class="bi {{ $otpLive['icon'] }}"></i>{{ $otpLive['label'] }}
                                            </span>
                                        @else
                                            <span class="text-body-secondary">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-end text-body-secondary d-none d-md-table-cell">
                                        {{ $p->updated_at ? $p->updated_at->diffForHumans(null, true).' ago' : '—' }}
                                    </td>
                                </tr>
                            @endforeach
                            @foreach ($dispatched as $bid)
                                <tr>
                                    <td>{{ optional($bid->user)->name_private }}</td>
                                    <td><span class="badge bz-badge">{{ optional($bid->flight)->ident }}</span></td>
                                    <td>{{ optional($bid->flight)->dpt_airport_id }}</td>
                                    <td>{{ optional($bid->flight)->arr_airport_id }}</td>
                                    <td class="text-body-secondary">{{ optional($bid->aircraft)->ident }}</td>
                                    <td>
                                        <span class="bz-st bz-st--sched">
                                            <i class="bi bi-bookmark-check-fill"></i>Booked
                                        </span>
                                    </td>
                                    <td><span class="text-body-secondary">&mdash;</span></td>
                                    <td class="text-end text-body-secondary d-none d-md-table-cell">
                                        @if (filled(optional($bid->flight)->dpt_time))
                                            STD {{ $bid->flight->dpt_time }}Z
                                        @else
                                            &mdash;
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                            @if ($airborne->isEmpty() && $dispatched->isEmpty())
                                <tr>
                                    <td colspan="8" class="bz-empty">There are no active bookings.</td>
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
                                <th>Departure</th>
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
                                    <td>
                                        @php($otp = $departurePunctuality($p))
                                        @if ($otp)
                                            <span class="bz-st bz-st--{{ $otp['tone'] }}">
                                                <i class="bi {{ $otp['icon'] }}"></i>{{ $otp['label'] }}
                                            </span>
                                        @elseif (blank(optional($p->flight)->dpt_time))
                                            {{--
                                                Replacing a schedule deletes the old route rows, so a report
                                                flown against a retired route has nothing left to be judged
                                                against. Say that, rather than showing a bare dash that reads
                                                as a fault.
                                            --}}
                                            <span class="text-body-secondary small"
                                                title="This route is no longer in the schedule, so there is no scheduled departure time to compare against. The flight report itself is intact.">
                                                No schedule
                                            </span>
                                        @else
                                            <span class="text-body-secondary">&mdash;</span>
                                        @endif
                                    </td>
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
                                    <td colspan="9" class="bz-empty">No flights have been filed yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

        </div>
    </section>

    {{--
          Keep the board current without reloading the page. Every 60 seconds this
          fetches the home page in the background and swaps in just the stats band
          and the two flight panels. The map is deliberately left alone - it already
          updates itself, and reloading it would reset the zoom and make it blink.
        --}}
    <script>
            (function () {
                var REFRESH_MS = 60000;
                var TARGETS = ['bz-live-stats', 'bz-live-ops'];
                var busy = false;
    
                function refresh() {
                    // don't hammer the server for a tab nobody is looking at
                    if (busy || document.hidden) {
                        return;
                    }
                    busy = true;
    
                    fetch(window.location.pathname + '?_=' + Date.now(), {
                        cache: 'no-store',
                        headers: { 'X-Requested-With': 'fetch' }
                    })
                        .then(function (r) {
                            if (!r.ok) {
                                throw new Error('HTTP ' + r.status);
                            }
                            return r.text();
                        })
                        .then(function (html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
    
                            TARGETS.forEach(function (id) {
                                var fresh = doc.getElementById(id);
                                var here = document.getElementById(id);
                                if (fresh && here && fresh.innerHTML !== here.innerHTML) {
                                    here.innerHTML = fresh.innerHTML;
                                }
                            });
                        })
                        .catch(function () {
                            // a failed refresh is not worth bothering anyone about;
                            // the next tick will try again
                        })
                        .finally(function () {
                            busy = false;
                        });
                }
    
                setInterval(refresh, REFRESH_MS);
    
                // catch up immediately when someone comes back to the tab
                document.addEventListener('visibilitychange', function () {
                    if (!document.hidden) {
                        refresh();
                    }
                });
            })();
        </script>
@endsection
