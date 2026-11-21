{{-- Every picture is shown as the same 56px circle. Uploaded avatars (200px) used to
     appear at full size next to 80px default ones. --}}
{{-- Bootstrap 5 only scrolls a table sideways inside a .table-responsive WRAPPER; on the
     table itself it did nothing, so on phones the whole page scrolled sideways. --}}
<style>
  /* Rank badge: its own column after the name so every badge lines up (under the name on phones) (the small roster copy, ~20 KB, not the full-size patch) */
  #users-table img.rank-badge { height: 30px; width: auto; vertical-align: middle; }
  /* Phones: smaller pictures and tighter cells so all the columns fit the screen */
  @media (max-width: 575.98px) {
    #users-table img.rounded-circle { width: 36px !important; height: 36px !important; }
    #users-table img.rank-badge { height: 20px; }
    #users-table th, #users-table td { padding: .5rem .35rem; font-size: .9rem; }
    .card-body:has(#users-table) { padding: .4rem !important; }
  }
</style>
<div class="table-responsive">
<table class="table table-striped table-hover align-middle" id="users-table">
    <thead>
        <th scope="col">@sortablelink('pilot_id', 'ID')</th>
        <th scope="col">@sortablelink('name', __('common.name'))</th>
        <th scope="col" class="d-none d-md-table-cell">Rank</th>
        <th scope="col" class="d-none d-md-table-cell">@sortablelink('airline_id', __('common.airline'))</th>
        <th scope="col">@sortablelink('curr_airport_id', __('user.location'))</th>
        <th scope="col">@sortablelink('flights', trans_choice('common.flight', 2))</th>
        <th scope="col">@sortablelink('flight_time', trans_choice('common.hour', 2))</th>
    </thead>
    <tbody>
        @foreach ($users as $user)
            <tr class="align-middle">
                {{-- Rank badge. Uses the small copy in ranks/small/ when there is one,
                     otherwise the rank's own image. Hover shows the rank name. --}}
                @php
                    $rankBadge = null;
                    if ($user->rank && filled($user->rank->image_url)) {
                        $rankBadge = $user->rank->image_url;
                        $rankSmall = 'assets/img/ranks/small/'.basename((string) parse_url($rankBadge, PHP_URL_PATH));
                        if (is_file(public_path($rankSmall))) {
                            $rankBadge = public_asset($rankSmall);
                        }
                    }
                @endphp
                <td scope="row">
                    @if ($user->avatar == null)
                        <img class="rounded-circle" src="{{ $user->gravatar(112) }}&s=112" alt="" style="width: 56px; height: 56px; object-fit: cover;" />
                    @else
                        <img class="rounded-circle" src="{{ $user->avatar->url }}" alt="" style="width: 56px; height: 56px; object-fit: cover;">
                    @endif
                </td>
                <td>
                    <a href="{{ route('frontend.users.show.public', [$user->id]) }}">
                        {{ $user->ident }}&nbsp;{{ $user->name_private }}
                    </a>
                    @if (filled($user->country))
                        <span class="fi fi-{{ $user->country }} ml-2"
                            title="{{ $country->alpha2($user->country)['name'] }}"></span>
                    @endif
                    {{-- Phones: no room for a Rank column, so the badge goes on its own line under the name --}}
                    @if ($rankBadge)
                        <div class="d-md-none mt-1"><img class="rank-badge" src="{{ $rankBadge }}" alt="{{ $user->rank->name }}" title="{{ $user->rank->name }}"></div>
                    @endif
                </td>
                <td class="text-nowrap d-none d-md-table-cell">
                    @if ($rankBadge)
                        <img class="rank-badge" src="{{ $rankBadge }}" alt="{{ $user->rank->name }}" title="{{ $user->rank->name }}">
                    @endif
                </td>
                <td class="d-none d-md-table-cell">{{ $user->airline->icao }}</td>
                <td>
                    @if ($user->current_airport)
                        {{ $user->curr_airport_id }}
                    @else
                        -
                    @endif
                </td>
                <td>{{ $user->flights }}</td>
                <td>@minutestotime($user->flight_time)</td>
            </tr>
        @endforeach
    </tbody>
</table>
</div>
