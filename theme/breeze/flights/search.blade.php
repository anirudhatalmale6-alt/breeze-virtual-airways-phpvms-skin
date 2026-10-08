{{--
  Flight search sidebar.

  Breeze override of the stock seven partial. The airline, flight type, subfleet
  and ICAO type boxes were dropdowns offering exactly one choice each, because
  the airline flies one type on one AOC. A select with a single option is a
  control that cannot do anything, so those render as plain text instead.

  This is decided from the data, not hard-coded: a list collapses to text only
  while it holds a single usable option. Add a second aircraft type or a second
  airline and that box becomes a working dropdown again on its own, with no
  change needed here.

  Nothing is submitted for a collapsed box, so the search behaves exactly as it
  did with the field left blank - the results are unchanged, the dead control is
  simply gone.
--}}
@php
    // Options with a real label. The stock lists carry a leading blank entry
    // ('' => ''), and for flight types that blank sits under integer key 0 -
    // so filter on the LABEL, never on the key: filled(0) is true.
    $bz_usable = function ($list) {
        return collect($list)->filter(fn ($label) => filled($label));
    };

    $bz_airlines = $bz_usable($airlines ?? []);
    $bz_types = $bz_usable($flight_types ?? []);
    $bz_subfleets = $bz_usable($subfleets ?? []);
    $bz_icaos = $bz_usable($icao_codes ?? []);
@endphp

<div class="row">
  <div class="col-12">
    <div class="form-group search-form">
      <form method="get" action="{{ route('frontend.flights.search') }}">
        @csrf
        <div>
          <div class="mb-3">
            <label for="airline_id" class="form-label">@lang('common.airline')</label>
            @if ($bz_airlines->count() === 1)
              <div class="form-control-plaintext py-0">{{ $bz_airlines->first() }}</div>
            @else
              <select name="airline_id" id="airline_id" class="form-select">
                @foreach($airlines as $airline_id => $airline_label)
                  <option value="{{ $airline_id }}" @if(request()->get('airline_id') == $airline_id) selected @endif>{{ $airline_label }}</option>
                @endforeach
              </select>
            @endif
          </div>
        </div>

        <div class="mb-3">
          <label for="flight_type" class="form-label">@lang('flights.flighttype')</label>
          @if ($bz_types->count() === 1)
            <div class="form-control-plaintext py-0">{{ $bz_types->first() }}</div>
          @else
            <select name="flight_type" id="flight_type" class="form-select">
              @foreach($flight_types as $flight_type_id => $flight_type_label)
                <option value="{{ $flight_type_id }}" @if(request()->get('flight_type') == $flight_type_id) selected @endif>{{ $flight_type_label }}</option>
              @endforeach
            </select>
          @endif
        </div>

        <div class="mb-3">
          <label for="flight_number" class="form-label">@lang('flights.flightnumber')</label>
          <input type="text" name="flight_number" id="flight_number" class="form-control" value="{{ request()->get('flight_number') }}" />
        </div>

        <div class="mb-3">
          <label for="route_code" class="form-label">@lang('flights.code')</label>
          <input type="text" name="route_code" id="route_code" class="form-control" value="{{ request()->get('route_code') }}" />
        </div>

        {{--
          Placeholder shortened from the stock "Type To Begin Search", which is
          too wide to read in this sidebar column. "Code or name" is accurate:
          /api/airports/search matches on icao, iata AND name, so KCHS, CHS and
          Charleston all find the airport. The label above already says which
          end of the route it is, so the placeholder only has to teach the
          format.
        --}}
        <div class="mb-3">
          <label for="dep_icao" class="form-label">@lang('airports.departure')</label>
          <select name="dep_icao" placeholder="Code or name" id="dep_icao" class="form-select airport_search">
          </select>
        </div>

        <div class="mb-3">
          <label for="arr_icao" class="form-label">@lang('airports.arrival')</label>
          <select name="arr_icao" placeholder="Code or name" id="arr_icao" class="form-select airport_search">
          </select>
        </div>

        <div class="mb-3">
          <label for="subfleet_id" class="form-label">@lang('common.subfleet')</label>
          @if ($bz_subfleets->count() === 1)
            <div class="form-control-plaintext py-0">{{ $bz_subfleets->first() }}</div>
          @else
            <select name="subfleet_id" id="subfleet_id" class="form-select select2">
              @foreach($subfleets as $subfleet_id => $subfleet_label)
                <option value="{{ $subfleet_id }}" @if(request()->get('subfleet_id') == $subfleet_id) selected @endif>{{ $subfleet_label }}</option>
              @endforeach
            </select>
          @endif
        </div>

        @if(filled($type_ratings))
          <div class="mb-3">
            <label for="type_rating_id" class="form-label">Type Rating</label>
            <select name="type_rating_id" id="type_rating_id" class="form-select select2">
              <option value=""></option>
              @foreach($type_ratings as $tr)
                <option value="{{ $tr->id }}" @if(request()->get('type_rating_id') == $tr->id) selected @endif>{{ $tr->type.' | '.$tr->name }}</option>
              @endforeach
            </select>
          </div>
        @endif

        @if(filled($icao_codes))
          <div class="mb-3">
            <label for="icao_type" class="form-label">ICAO Type</label>
            @if ($bz_icaos->count() === 1)
              <div class="form-control-plaintext py-0">{{ $bz_icaos->first() }}</div>
            @else
              <select name="icao_type" id="icao_type" class="form-select select2">
                <option value=""></option>
                @foreach($icao_codes as $icao)
                  <option value="{{ $icao }}" @if(request()->get('icao_type') == $icao) selected @endif>{{ $icao }}</option>
                @endforeach
              </select>
            @endif
          </div>
        @endif

        <div class="d-flex justify-content-between mt-3">
          <button type="submit" class="btn btn-primary">@lang('common.find')</button>
          <a href="{{ route('frontend.flights.index') }}" class="btn btn-secondary">@lang('common.reset')</a>
        </div>
      </form>
    </div>
  </div>
</div>
