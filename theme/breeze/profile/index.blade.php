@extends('app')
@section('title', __('common.profile'))

@section('content')
    <div class="row">
        <div class="col-md-4 text-center">
            <div class="card">
                <div class="text-center pt-4">
                    @if ($user->avatar == null)
                        <img src="{{ $user->gravatar(512) }}" class="img-fluid card-img-top rounded-circle"
                            style="width: 123px;">
                    @else
                        <img src="{{ $user->avatar->url }}" class="img-fluid card-img-top rounded-circle"
                            style="width: 123px;">
                    @endif
                </div>
                <div class="card-body">
                    <h3 class="card-text">{{ $user->name_private }}</h3>
                    <p class="card-text">
                        {{ $user->ident }}
                        @if (filled($user->callsign))
                            {{ ' | ' . $user->callsign }}&nbsp;
                        @endif
                        <span class="fi fi-{{ $user->country }}"></span>
                    </p>
                    <p class="text-muted">
                        {{ $user->airline->name }}
                    </p>
                </div>
                <div>
                    @if (!empty($user->rank->image_url))
                        <img src="{{ $user->rank->image_url }}" class="img-fluid" style="width: 160px;">
                    @endif
                    <p>{{ $user->rank->name }} <br />
                        @if ($user->home_airport)
                            @lang('airports.home'): {{ $user->home_airport->icao }}
                        @endif
                    </p>
                </div>
                @if (Auth::check() && $user->id === Auth::user()->id)
                    <div class="card-footer d-grid gap-2">
                        @if (isset($acars) && $acars === true)
                            <a href="{{ route('frontend.profile.acars') }}" class="btn btn-info"
                                onclick="alert('Copy or Save to \'My Documents/vmsacars/profiles\'')">ACARS
                                Config</a>
                            &nbsp;
                        @endif
                        <a href="{{ route('frontend.profile.edit', [$user->id]) }}"
                            class="btn btn-primary">@lang('common.edit')</a>
                    </div>
                @endif
            </div>
        </div>
        <div class="col-md-8 mt-4 mt-sm-0">
            <div class="row mb-2 mb-sm-4">
                <div class="col-lg-6 mb-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h2 class="card-title">{{ $user->flights }}</h2>
                            <p class="card-text">{{ trans_choice('common.flight', $user->flights) }}</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card text-center">
                        <div class="card-body">
                            <div class="social-description">
                                <h2 class="card-title">@minutestotime($user->flight_time)</h2>
                                <p class="card-text">@lang('flights.flighthours')</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row">
                @if ($user->current_airport)
                    <div class="col-lg-6 mb-2">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="social-description">
                                    <h2 class="card-title">{{ $user->current_airport->icao }}</h2>
                                    <p class="card-text">@lang('airports.current')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif


                @if (setting('pilots.allow_transfer_hours') === true)
                    <div class="col-lg-6">
                        <div class="card text-center">
                            <div class="card-body">
                                <div class="social-description">
                                    <h2 class="card-title">@minutestohours($user->transfer_time)h</h2>
                                    <p class="card-text">@lang('profile.transferhours')</p>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{--
      Routes flown + flight log (Breeze addition).

      The stock public pilot page shows totals only. A pilot's ID now links here
      from the homepage boards and the roster, so this is where anyone can see
      where that pilot has actually flown and every report behind the totals.

      Only filed reports that count are listed: accepted, plus pending ones still
      waiting for review (labelled as such). Rejected, cancelled, draft and
      in-progress reports are left out, the same set the flight total is built from.
    --}}
    @php
        $bzStates = [\App\Models\Enums\PirepState::ACCEPTED, \App\Models\Enums\PirepState::PENDING];

        $bzLog = \App\Models\Pirep::with(['aircraft', 'airline', 'dpt_airport', 'arr_airport'])
            ->where('user_id', $user->id)
            ->whereIn('state', $bzStates)
            ->orderByDesc('submitted_at')
            ->orderByDesc('created_at')
            ->paginate(20, ['*'], 'log')
            ->withQueryString();

        // Every route the pilot has flown, across ALL pages of the log, with a
        // count per direction. A->B and B->A share one line on the map.
        $bzPairs = \App\Models\Pirep::where('user_id', $user->id)
            ->whereIn('state', $bzStates)
            ->selectRaw('dpt_airport_id, arr_airport_id, COUNT(*) AS n')
            ->groupBy('dpt_airport_id', 'arr_airport_id')
            ->get();

        $bzAirports = \App\Models\Airport::whereIn('id',
                $bzPairs->pluck('dpt_airport_id')->merge($bzPairs->pluck('arr_airport_id'))->unique()->values())
            ->get(['id', 'icao', 'name', 'lat', 'lon'])
            ->keyBy('id');

        $bzLines = [];
        foreach ($bzPairs as $pair) {
            $a = $bzAirports->get($pair->dpt_airport_id);
            $b = $bzAirports->get($pair->arr_airport_id);
            if (!$a || !$b || ($a->lat == 0 && $a->lon == 0) || ($b->lat == 0 && $b->lon == 0)) {
                continue;
            }
            $key = collect([$a->id, $b->id])->sort()->implode('-');
            $bzLines[$key] ??= ['from' => [(float) $a->lat, (float) $a->lon], 'to' => [(float) $b->lat, (float) $b->lon], 'legs' => []];
            $bzLines[$key]['legs'][] = $a->id.' &rarr; '.$b->id.' ('.$pair->n.'x)';
        }

        $bzPoints = $bzAirports
            ->filter(fn ($ap) => !($ap->lat == 0 && $ap->lon == 0))
            ->map(fn ($ap) => ['id' => $ap->id, 'name' => $ap->name, 'lat' => (float) $ap->lat, 'lon' => (float) $ap->lon])
            ->values();

        $bzStateLabel = function ($p) {
            return $p->state === \App\Models\Enums\PirepState::PENDING
                ? ['tone' => 'sched', 'icon' => 'bi-hourglass-split', 'label' => 'Pending']
                : ['tone' => 'air', 'icon' => 'bi-check-circle-fill', 'label' => 'Accepted'];
        };
    @endphp

    <div class="row mt-5">
        <div class="col-12">
            <div class="bz-panel">
                <div class="bz-panel__head">
                    <span>Routes Flown</span>
                    <span class="count">{{ count($bzLines) }} {{ count($bzLines) === 1 ? 'route' : 'routes' }}</span>
                </div>
                @if (count($bzLines))
                    <div id="bz-routes-map" style="height: 420px; width: 100%;"></div>
                @else
                    <div class="bz-empty">No routes flown yet.</div>
                @endif
            </div>

            <div class="bz-panel" id="flight-log">
                <div class="bz-panel__head">
                    <span>Flight Log</span>
                    <span class="count">{{ $bzLog->total() }} {{ $bzLog->total() === 1 ? 'flight' : 'flights' }}</span>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Flight No.</th>
                                <th>Route</th>
                                <th>Aircraft</th>
                                <th class="text-end">Block</th>
                                <th class="text-end">Landing Rate</th>
                                <th class="text-end">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($bzLog as $p)
                                @php($st = $bzStateLabel($p))
                                <tr>
                                    <td class="text-nowrap">{{ optional($p->submitted_at ?? $p->created_at)->format('d M Y') }}</td>
                                    <td>
                                        <a href="{{ route('frontend.pireps.show', [$p->id]) }}" class="text-decoration-none">
                                            <span class="badge bz-badge">{{ $p->ident }}</span>
                                        </a>
                                    </td>
                                    <td class="text-nowrap">
                                        <a class="text-decoration-none" href="{{ route('frontend.airports.show', [$p->dpt_airport_id]) }}">{{ $p->dpt_airport_id }}</a>
                                        <i class="bi bi-arrow-right text-body-secondary mx-1"></i>
                                        <a class="text-decoration-none" href="{{ route('frontend.airports.show', [$p->arr_airport_id]) }}">{{ $p->arr_airport_id }}</a>
                                    </td>
                                    <td class="text-body-secondary">{{ optional($p->aircraft)->ident }}</td>
                                    <td class="text-end text-nowrap">@minutestotime($p->flight_time)</td>
                                    <td class="text-end text-nowrap">
                                        @if (filled($p->landing_rate))
                                            {{ number_format($p->landing_rate) }} ft/min
                                        @else
                                            <span class="text-body-secondary">&mdash;</span>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <span class="bz-st bz-st--{{ $st['tone'] }}"><i class="bi {{ $st['icon'] }}"></i>{{ $st['label'] }}</span>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="bz-empty">No flights logged yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if ($bzLog->hasPages())
                <div class="d-flex justify-content-center">
                    {{ $bzLog->fragment('flight-log')->links('pagination.bootstrap-5') }}
                </div>
            @endif
        </div>
    </div>

    {{-- Show the user's award if they have any. The relation is a collection, so
         test isNotEmpty(): a bare @if on it is always true and printed an empty
         "Your Awards" heading on every pilot page. --}}
    @if ($user->awards->isNotEmpty())
        <div class="row mt-5">
            <div class="col-sm-12">
                <h3>Awards</h3>
                @foreach ($user->awards->chunk(3) as $awards)
                    <div class="row">
                        @foreach ($awards as $award)
                            <div class="col-md-4">
                                <div class="card">
                                    <div class="card-header bg-primary text-white bg-primary text-center">
                                        <h4 class="text-white">{{ $award->name }}</h4>
                                        @if ($award->image_url)
                                            <img src="{{ $award->image_url }}" alt="{{ $award->description }}"
                                                class="img-fluid card-img-top" style="width: 123px;">
                                        @endif
                                    </div>
                                    <div class="card-body text-center">
                                        {{ $award->description }}
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

    @endif
@endsection

@section('scripts')
    @if (count($bzLines))
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
        <style>
            .bz-routes-map--dark .leaflet-tile-pane { filter: invert(1) hue-rotate(180deg) brightness(.85) contrast(.9); }
        </style>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var el = document.getElementById('bz-routes-map');
                if (!el || typeof L === 'undefined') { return; }
                var lines = @json(array_values($bzLines));
                var points = @json($bzPoints);
                var dark = document.documentElement.getAttribute('data-bs-theme') === 'dark';
                var map = L.map(el, { scrollWheelZoom: false, worldCopyJump: true });
                // Same OpenStreetMap tiles the live map uses (CARTO now wants an
                // API key). In dark mode the tiles are inverted rather than
                // swapped, so no second provider is involved.
                if (dark) { el.classList.add('bz-routes-map--dark'); }
                L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
                    maxZoom: 18,
                    attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
                }).addTo(map);
                var bounds = [];
                lines.forEach(function (ln) {
                    L.polyline([ln.from, ln.to], { color: '#0780e9', weight: 2.5, opacity: .85 })
                        .bindTooltip(ln.legs.join('<br>'), { sticky: true })
                        .addTo(map);
                });
                points.forEach(function (ap) {
                    L.circleMarker([ap.lat, ap.lon], { radius: 5, color: '#02193a', weight: 2, fillColor: '#ffffff', fillOpacity: 1 })
                        .bindTooltip('<strong>' + ap.id + '</strong><br>' + String(ap.name).replace(/[<>&"]/g, ''))
                        .addTo(map);
                    bounds.push([ap.lat, ap.lon]);
                });
                if (bounds.length === 1) { map.setView(bounds[0], 7); }
                else { map.fitBounds(bounds, { padding: [30, 30] }); }
            });
        </script>
    @endif
@endsection
