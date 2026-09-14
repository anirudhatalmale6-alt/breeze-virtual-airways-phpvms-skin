{{--
  Header modelled on frontierva.org: white bar, logo left, grouped dropdown nav,
  live Zulu clock on the right.
--}}
<header class="bz-header">
    <nav class="navbar navbar-expand-lg navbar-light py-2">
        <div class="container">
            <a class="navbar-brand py-0" href="{{ url('/') }}">
                <img src="{{ public_asset('/assets/breeze/logo.png') }}" alt="{{ config('app.name') }}">
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false"
                aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarSupportedContent">
                <ul class="navbar-nav ms-auto mb-2 mb-lg-0 align-items-lg-center">

                    {{-- Operations Center ------------------------------------------------ --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            Operations Center
                        </a>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('frontend.livemap.index') }}">
                                    <i class="bi bi-globe me-1"></i> Live Map
                                </a>
                            </li>
                            @if (Auth::check())
                                <li>
                                    <a class="dropdown-item" href="{{ route('frontend.flights.index') }}">
                                        <i class="bi bi-airplane me-1"></i> Schedules &amp; Dispatch
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('frontend.flights.bids') }}">
                                        <i class="bi bi-bookmark-check me-1"></i> My Bids
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('frontend.pireps.index') }}">
                                        <i class="bi bi-journal-check me-1"></i> My Flight Reports
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ route('frontend.downloads.index') }}">
                                        <i class="bi bi-download me-1"></i> Downloads &amp; smartCARS
                                    </a>
                                </li>
                            @endif
                            <li>
                                <a class="dropdown-item" href="{{ route('frontend.pilots.index') }}">
                                    <i class="bi bi-people me-1"></i> Pilot Roster
                                </a>
                            </li>
                        </ul>
                    </li>

                    {{-- About Us ---------------------------------------------------------- --}}
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown"
                            aria-expanded="false">
                            About Us
                        </a>
                        <ul class="dropdown-menu">
                            {{-- phpVMS "pages" created in the admin appear here automatically --}}
                            @forelse ($page_links as $page)
                                <li>
                                    <a class="dropdown-item" href="{{ $page->url }}"
                                        target="{{ $page->new_window ? '_blank' : '_self' }}">
                                        <i class="{{ $page['icon'] }} me-1"></i>{{ $page['name'] }}
                                    </a>
                                </li>
                            @empty
                                <li>
                                    <span class="dropdown-item-text small text-body-secondary">
                                        Add pages in Admin &rarr; Pages
                                    </span>
                                </li>
                            @endforelse
                        </ul>
                    </li>

                    {{-- Module links that don't need a login --}}
                    @foreach ($moduleSvc->getFrontendLinks($logged_in = false) as &$link)
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url($link['url']) }}">
                                <i class="{{ $link['icon'] }} me-1"></i>{{ $link['title'] }}
                            </a>
                        </li>
                    @endforeach

                    @if (!Auth::check())
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/register') }}">We Are Hiring!</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/login') }}">
                                <i class="bi bi-box-arrow-in-right me-1"></i>Flight Crew
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('frontend.dashboard.index') }}">
                                <i class="bi bi-speedometer2 me-1"></i>Dashboard
                            </a>
                        </li>

                        @foreach ($moduleSvc->getFrontendLinks($logged_in = true) as &$link)
                            <li class="nav-item">
                                <a class="nav-link" href="{{ url($link['url']) }}">
                                    <i class="{{ $link['icon'] }} me-1"></i>{{ $link['title'] }}
                                </a>
                            </li>
                        @endforeach

                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" role="button"
                                data-bs-toggle="dropdown" aria-expanded="false">
                                @if (Auth::user()->avatar == null)
                                    <img src="{{ Auth::user()->gravatar(36) }}"
                                        style="height: 36px; width: 36px; border-radius: 50%; border: 2px solid var(--bz-blue);">
                                @else
                                    <img src="{{ Auth::user()->avatar->url }}"
                                        style="height: 36px; width: 36px; border-radius: 50%; border: 2px solid var(--bz-blue);">
                                @endif
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li>
                                    <a class="dropdown-item" href="{{ route('frontend.profile.index') }}">
                                        <i class="bi bi-person me-1"></i>@lang('common.profile')
                                    </a>
                                </li>
                                @can('access_admin')
                                    <li>
                                        <hr class="dropdown-divider">
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ url('/admin') }}">
                                            <i class="bi bi-gear me-1"></i>@lang('common.administration')
                                        </a>
                                    </li>
                                @endcan
                                <li>
                                    <hr class="dropdown-divider">
                                </li>
                                <li>
                                    <a class="dropdown-item" href="{{ url('/logout') }}">
                                        <i class="bi bi-box-arrow-right me-1"></i>@lang('common.logout')
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    {{-- Zulu clock --}}
                    <li class="nav-item ms-lg-3 d-flex align-items-center">
                        <span class="bz-zulu"><span id="bz-zulu-clock">--:--:--</span> <span class="z">Z</span></span>
                    </li>

                    {{-- Light / dark switch --}}
                    <li class="nav-item dropdown ms-lg-2">
                        <button class="btn btn-link nav-link py-2 px-0 px-lg-2 dropdown-toggle d-flex align-items-center"
                            id="bd-theme" type="button" aria-expanded="false" data-bs-toggle="dropdown"
                            data-bs-display="static" aria-label="Toggle theme">
                            <i class="bi-sun-fill" id="theme-icon-active"></i>
                            <span class="d-lg-none ms-2" id="bd-theme-text">@lang('common.toggleColors')</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="bd-theme-text"
                            data-bs-popper="static">
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center active"
                                    data-bs-theme-value="light" aria-pressed="true">
                                    <i class="bi-sun-fill"></i>&nbsp;@lang('common.light')
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-bs-theme-value="dark" aria-pressed="false">
                                    <i class="bi-moon-stars-fill"></i>&nbsp;@lang('common.dark')
                                </button>
                            </li>
                            <li>
                                <button type="button" class="dropdown-item d-flex align-items-center"
                                    data-bs-theme-value="system" aria-pressed="false">
                                    <i class="bi-circle-half"></i>&nbsp;@lang('common.auto')
                                </button>
                            </li>
                        </ul>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
</header>
