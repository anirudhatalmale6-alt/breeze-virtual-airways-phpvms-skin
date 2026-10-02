<div class="card-body" style="min-height: 0px">
  <div class="row">
    <div class="col-sm-10">
      <p>
        <a href="{{ route('frontend.pireps.show', [$pirep->id]) }}">{{ $pirep->ident }}</a>
        -
        {{ $pirep->dpt_airport->name }}
        (<a href="{{route('frontend.airports.show', [
                          'id' => $pirep->dpt_airport->icao
                          ])}}">{{$pirep->dpt_airport->icao}}</a>)
        <span class="description">to</span>
        {{ $pirep->arr_airport->name }}
        (<a href="{{route('frontend.airports.show', [
                          'id' => $pirep->arr_airport->icao
                          ])}}">{{$pirep->arr_airport->icao}}</a>)
      </p>
    </div>
    <div class="col-sm-2 float-right">
      <div class="col-sm-2 text-center">
          @if($pirep->state === PirepState::PENDING)
            <div class="badge bg-warning">
          @elseif($pirep->state === PirepState::ACCEPTED)
              <div class="badge bg-success">
          @elseif($pirep->state === PirepState::REJECTED)
              <div class="badge bg-danger">
          @else
             <div class="badge bg-info">
          @endif
            {{ PirepState::label($pirep->state) }}</div>
          {{--
            Only offer Edit while the report can actually be edited.

            phpVMS already guards this exact link in pireps/table.blade.php with
            the same condition; the dashboard card is the one place that was
            missing it, so an Accepted report showed an Edit button that always
            died with a 500. The crash is a separate core bug: for a read-only
            PIREP, pireps/fields.blade.php line 97 prints $flight_type_id, which
            only exists as the @foreach loop variable in the OTHER branch, so it
            is undefined exactly when the report is locked. Hiding the button
            removes the only way in, and matches what phpVMS intends anyway -
            an approved report is not meant to be editable.
          --}}
          @if (!$pirep->read_only)
            <a href="{{ route('frontend.pireps.edit', [$pirep->id]) }}"
              class="btn btn-sm btn-info">@lang('common.edit')</a>
          @endif
      </div>
    </div>
  </div>
</div>
