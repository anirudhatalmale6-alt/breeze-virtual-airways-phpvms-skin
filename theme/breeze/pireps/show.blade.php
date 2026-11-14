{{--
  Flight report (PIREP) page - theme override of the stock seven template.

  What the stock page got wrong, and what this changes:
  1. The departure/arrival block (cities, airports, times) and the progress bar
     were printed twice, once above the map and once below it. Now once.
  2. The map was included twice: the second copy was an empty 800px box with the
     same id, so it never drew anything. Now one map, 520px tall.
  3. The progress bar is flown distance / planned distance. A finished flight
     rarely flies exactly the planned distance, so an arrived flight stopped at
     ~93%. Only a flight still in progress shows partial progress now.
  4. The two columns were fixed at 8/4 even on phones. They stack on small screens.
  5. The sidebar also shows who flew it (linked to their pilot page), the aircraft,
     block time and distance, which the stock page left out.
  Everything else (state, status, fields, fares, ACARS log, SimBrief OFP, the
  Edit/Submit buttons for an unfiled report) works exactly as before.
--}}
@extends('app')
@section('title', trans_choice('common.pirep', 1).' '.$pirep->ident)

@php
    $inProgress = $pirep->state === PirepState::IN_PROGRESS;
    $progress = $inProgress ? min(100, max(0, (float) $pirep->progress_percent)) : 100;
    $distanceText = null;
    if ($pirep->distance instanceof \App\Contracts\Unit && $pirep->distance->local(0) > 0) {
        $distanceText = number_format($pirep->distance->local(0)).' '.setting('units.distance');
    }
@endphp

@section('content')
  <div class="row align-items-center mb-3">
    <div class="col-md-8">
      <h2 class="mb-2 mb-md-0">{{ $pirep->ident }} : {{ $pirep->dpt_airport_id }} to {{ $pirep->arr_airport_id }}</h2>
    </div>

    <div class="col-md-4 d-flex flex-wrap gap-2 justify-content-md-end">
      @if (!empty($pirep->simbrief))
        <a href="{{ url(route('frontend.simbrief.briefing', [$pirep->simbrief->id])) }}"
           class="btn btn-outline-info">View SimBrief</a>
      @endif

      @if(!$pirep->read_only && $user && $pirep->user_id === $user->id)
        <form method="get" action="{{ route('frontend.pireps.edit', $pirep->id) }}" style="display: inline">
          @csrf
          <button class="btn btn-outline-info">@lang('common.edit')</button>
        </form>
        <form method="post" action="{{ route('frontend.pireps.submit', $pirep->id) }}" style="display: inline">
          @csrf
          <button class="btn btn-outline-success">@lang('common.submit')</button>
        </form>
      @endif
    </div>
  </div>

  <div class="row g-4">
    <div class="col-lg-8">
      <div class="card">
        <div class="card-body">
          <div class="d-flex flex-column flex-md-row justify-content-between gap-3">
            {{-- DEPARTURE --}}
            <div>
              <h4 class="mb-1">{{ optional($pirep->dpt_airport)->location ?: $pirep->dpt_airport_id }}</h4>
              <a href="{{ route('frontend.airports.show', $pirep->dpt_airport_id) }}">
                {{ optional($pirep->dpt_airport)->full_name ?: $pirep->dpt_airport_id }}</a>
              @if($pirep->block_off_time)
                <div class="text-body-secondary small mt-1">{{ $pirep->block_off_time->toDayDateTimeString() }}</div>
              @endif
            </div>

            {{-- ARRIVAL --}}
            <div class="text-md-end">
              <h4 class="mb-1">{{ optional($pirep->arr_airport)->location ?: $pirep->arr_airport_id }}</h4>
              <a href="{{ route('frontend.airports.show', $pirep->arr_airport_id) }}">
                {{ optional($pirep->arr_airport)->full_name ?: $pirep->arr_airport_id }}</a>
              @if($pirep->block_on_time)
                <div class="text-body-secondary small mt-1">{{ $pirep->block_on_time->toDayDateTimeString() }}</div>
              @endif
            </div>
          </div>

          @if(!empty($pirep->distance))
            <div class="progress mt-3" role="progressbar" aria-label="Flight progress"
                 aria-valuenow="{{ round($progress) }}" aria-valuemin="0" aria-valuemax="100">
              <div class="progress-bar @if($inProgress) bg-primary progress-bar-striped progress-bar-animated @else bg-success @endif"
                   style="width: {{ $progress }}%;"></div>
            </div>
          @endif
        </div>

        {{-- One map. The stock page included it twice; the second was an empty box. --}}
        <div id="map" style="width: 100%; height: 520px;"></div>
      </div>
    </div>

    {{-- RIGHT SIDEBAR --}}
    <div class="col-lg-4">
      <div class="list-group">
        @if ($pirep->user)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>Pilot</span>
            <a class="text-end" href="{{ route('frontend.users.show.public', [$pirep->user_id]) }}">
              <strong>{{ $pirep->user->ident }}</strong> {{ $pirep->user->name_private }}</a>
          </div>
        @endif

        <div class="d-flex justify-content-between gap-3 list-group-item">
          <span>@lang('common.state')</span>
          @php
            $stateClass = 'bg-info';
            if ($pirep->state === PirepState::PENDING) {
              $stateClass = 'bg-warning';
            } elseif ($pirep->state === PirepState::ACCEPTED) {
              $stateClass = 'bg-success';
            } elseif ($pirep->state === PirepState::REJECTED) {
              $stateClass = 'bg-danger';
            } elseif ($pirep->state === PirepState::IN_PROGRESS) {
              $stateClass = 'bg-primary';
            }
          @endphp
          <span class="badge {{ $stateClass }}">{{ PirepState::label($pirep->state) }}</span>
        </div>

        @if ($pirep->state !== PirepState::DRAFT)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>@lang('common.status')</span>
            @php
              $statusClass = 'bg-info';
              if ($pirep->status === PirepStatus::SCHEDULED) {
                $statusClass = 'bg-secondary';
              } elseif ($pirep->status === PirepStatus::ENROUTE) {
                $statusClass = 'bg-primary';
              } elseif ($pirep->status === PirepStatus::ARRIVED) {
                $statusClass = 'bg-success';
              } elseif ($pirep->status === PirepStatus::CANCELLED) {
                $statusClass = 'bg-danger';
              } elseif ($pirep->status === PirepStatus::DIVERTED) {
                $statusClass = 'bg-warning';
              }
            @endphp
            <span class="badge {{ $statusClass }}">{{ PirepStatus::label($pirep->status) }}</span>
          </div>
        @endif

        @if ($pirep->aircraft)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>@lang('common.aircraft')</span>
            <span class="text-end">{{ $pirep->aircraft->ident }}</span>
          </div>
        @endif

        @if ($pirep->flight_time)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>Block time</span>
            <span>@minutestotime($pirep->flight_time)</span>
          </div>
        @endif

        @if ($distanceText)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>Distance</span>
            <span>{{ $distanceText }}</span>
          </div>
        @endif

        @if($pirep->landing_rate)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>Landing Rate</span>
            <span>{{ number_format($pirep->landing_rate) }} ft/min</span>
          </div>
        @endif

        @if($pirep->score)
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>Score</span>
            <span>{{ $pirep->score }}</span>
          </div>
        @endif

        <div class="d-flex justify-content-between gap-3 list-group-item">
          <span>@lang('pireps.source')</span>
          <span class="text-end">{{ PirepSource::label($pirep->source) }}</span>
        </div>

        <div class="d-flex justify-content-between gap-3 list-group-item">
          <span>@lang('flights.flighttype')</span>
          <span class="text-end">{{ \App\Models\Enums\FlightType::label($pirep->flight_type) }}</span>
        </div>

        @if (filled($pirep->route))
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span class="text-nowrap">@lang('pireps.filedroute')</span>
            <span class="text-end text-break">{{ $pirep->route }}</span>
          </div>
        @endif

        @if (filled($pirep->notes))
          <div class="d-flex justify-content-between gap-3 list-group-item">
            <span>{{ trans_choice('common.note', 2) }}</span>
            <span class="text-end text-break">{{ $pirep->notes }}</span>
          </div>
        @endif

        <div class="d-flex justify-content-between gap-3 list-group-item">
          <span>@lang('pireps.filedon')</span>
          <span class="text-end">{{ show_datetime($pirep->created_at) }}</span>
        </div>
      </div>

      @if(count($pirep->fields) > 0)
        <h5 class="mt-4">{{ trans_choice('common.field', 2) }}</h5>
        <div class="list-group">
          @foreach($pirep->fields as $field)
            <div class="d-flex justify-content-between gap-3 list-group-item">
              <span>{{ $field->name }}</span>
              <span class="text-end text-break">{{ $field->value }}</span>
            </div>
          @endforeach
        </div>
      @endif

      @if(count($pirep->fares) > 0)
        <h5 class="mt-4">{{ trans_choice('pireps.fare', 2) }}</h5>
        <div class="list-group">
          @foreach($pirep->fares as $fare)
            <div class="d-flex justify-content-between gap-3 list-group-item">
              <span>{{ $fare->name }} ({{ $fare->code }})</span>
              <span>{{ $fare->count }}</span>
            </div>
          @endforeach
        </div>
      @endif
    </div>
  </div>

  @if(count($pirep->acars_logs) > 0)
    <div class="row mt-5">
      <div class="col-12">
        <h5>@lang('pireps.flightlog')</h5>
        <div class="table-responsive">
          <table class="table table-hover table-sm">
            <tbody>
              @foreach($pirep->acars_logs->sortBy('created_at') as $log)
                <tr>
                  <td class="text-nowrap">{{ show_datetime($log->created_at) }}</td>
                  <td>{{ $log->log }}</td>
                </tr>
              @endforeach
            </tbody>
          </table>
        </div>
      </div>
    </div>
  @endif

  @if(!empty($pirep->simbrief))
    <div class="row mt-5">
      <div class="col-12">
        <div class="form-container">
          <h6><i class="bi bi-info-circle"></i>&nbsp;OFP</h6>
          <div class="form-container-body border border-dark">
            <div class="overflow-auto" style="height: 600px;">
              {!! $pirep->simbrief->xml->text->plan_html !!}
            </div>
          </div>
        </div>
      </div>
    </div>
  @endif
@endsection

@section('scripts')
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/leaflet/1.9.4/leaflet.css" />
  <script type="text/javascript">
    phpvms.map.render_route_map({
      pirep_uri: '{!! url('/api/pireps/'.$pirep->id.'/acars/geojson') !!}',
      route_points: {!! json_encode($map_features['planned_rte_points']) !!},
      planned_route_line: {!! json_encode($map_features['planned_rte_line']) !!},
      actual_route_line: {!! json_encode($map_features['actual_route_line']) !!},
      actual_route_points: {!! json_encode($map_features['actual_route_points']) !!},
      aircraft_icon: '{!! public_asset('/assets/img/acars/aircraft.png') !!}',
      flown_route_color: '#067ec1',
      circle_color: '#056093',
      flightplan_route_color: '#8B008B',
      leafletOptions: {
        scrollWheelZoom: false,
      },
    });
  </script>
@endsection
