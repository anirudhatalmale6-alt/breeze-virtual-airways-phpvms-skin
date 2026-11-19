{{--
  NOTAM box (Nov 2026). Shown at the top of the Flights and My Bids pages.
  Edit the text in Admin > Pages > "NOTAM" (slug: notam). Switch that page OFF to hide
  the box, ON to show it again. The page is filtered out of the About Us menu.
--}}
@php
    $bzNotam = \App\Models\Page::where('slug', 'notam')->where('enabled', true)->first();
@endphp
@if ($bzNotam && filled(trim(strip_tags($bzNotam->body ?? ''))))
    <style>
        .bz-notam { border: 0; border-left: 6px solid #f59e0b; border-radius: .75rem; background: #fff8e6;
                    color: #3b2a00; padding: .9rem 1.1rem; margin-bottom: 1.25rem; box-shadow: 0 4px 14px rgba(0,0,0,.06); }
        .bz-notam .bz-notam__tag { display: inline-flex; align-items: center; gap: .35rem; font-weight: 800; letter-spacing: .08em;
                    font-size: .8rem; background: #f59e0b; color: #fff; border-radius: 999px; padding: .15rem .65rem; margin-bottom: .5rem; }
        .bz-notam .bz-notam__body > :last-child { margin-bottom: 0; }
        [data-bs-theme=dark] .bz-notam { background: #2a2210; color: #fde68a; }
        [data-bs-theme=dark] .bz-notam a { color: #fcd34d; }
    </style>
    <div class="bz-notam" role="alert">
        <div class="bz-notam__tag"><i class="bi bi-exclamation-triangle-fill"></i> NOTAM</div>
        <div class="bz-notam__body">{!! $bzNotam->body !!}</div>
    </div>
@endif
