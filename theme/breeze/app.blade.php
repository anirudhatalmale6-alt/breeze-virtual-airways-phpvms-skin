<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8" />
    <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1" />
    <meta content='width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=0, shrink-to-fit=no'
        name='viewport' />

    <title>@yield('title') - {{ config('app.name') }}</title>
    <script>
        (function() {
            if (localStorage.getItem('theme') === 'dark' || ((!localStorage.getItem('theme') || localStorage.getItem(
                    'theme') === 'system') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
                document.documentElement.setAttribute('data-bs-theme', "dark")
            }
        })();
    </script>

    {{-- Start of required lines block. DON'T REMOVE THESE LINES! --}}
    <meta name="base-url" content="{!! url('') !!}">
    <meta name="api-key" content="{!! Auth::check() ? Auth::user()->api_key : '' !!}">
    <meta name="csrf-token" content="{!! csrf_token() !!}">
    {{-- End the required lines block --}}

    <link rel="shortcut icon" type="image/png" href="{{ public_asset('/assets/breeze/logo.png') }}" />
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@500;600;700;800&family=Open+Sans:wght@400;600;700&display=swap"
        rel="stylesheet" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cookieconsent/3.1.1/cookieconsent.min.css"
        integrity="sha512-LQ97camar/lOliT/MqjcQs5kWgy6Qz/cCRzzRzUCfv0fotsCTC9ZHXaPQmJV8Xu/PVALfJZ7BDezl5lW3/qBxg=="
        crossorigin="anonymous" referrerpolicy="no-referrer" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/lipis/flag-icons@7.2.3/css/flag-icons.min.css" />
    <link href="{{ public_asset('/assets/vendor/tomselect/tom-select.bootstrap5.css') }}" rel="stylesheet">

    @yield('css')
    @yield('scripts_head')

    {{--
      BREEZE VIRTUAL AIRWAYS SKIN
      The whole palette comes off the logo and lives in these variables.
      Change them here and the navbar, buttons, bands, tables and footer all follow.
    --}}
    <style>
        :root {
            --bz-navy: #02193a;
            --bz-navy-2: #052c5f;
            --bz-blue: #0780e9;
            --bz-blue-2: #3ba1f5;
            --bz-line: rgba(2, 25, 58, .12);
            --bs-primary: #02193a;
            --bs-primary-rgb: 2, 25, 58;
            --bs-link-color: #0666bb;
            --bs-link-hover-color: #02193a;
            --bs-body-font-family: 'Open Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', sans-serif;
        }

        [data-bs-theme=light] {
            --bs-body-bg: #ffffff;
        }

        [data-bs-theme=dark] {
            --bz-line: rgba(255, 255, 255, .14);
            --bs-primary: #063a78;
            --bs-link-color: #4aa8f7;
            --bs-link-hover-color: #8cc8fb;
        }

        body {
            font-family: var(--bs-body-font-family);
        }

        h1, h2, h3, h4, .bz-display {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
        }

        .bg-primary {
            background-color: var(--bz-navy) !important;
        }

        .btn-primary {
            background-color: var(--bz-navy) !important;
            border-color: var(--bz-navy) !important;
        }

        .btn-primary:hover {
            background-color: var(--bz-navy-2) !important;
            border-color: var(--bz-navy-2) !important;
        }

        .btn-breeze {
            background-color: var(--bz-blue);
            border-color: var(--bz-blue);
            color: #fff;
            font-weight: 600;
        }

        .btn-breeze:hover {
            background-color: #0569c1;
            border-color: #0569c1;
            color: #fff;
        }

        /* ---- Header (white bar, like frontierva.org) --------------------- */
        .bz-header {
            background: #fff;
            border-bottom: 3px solid var(--bz-blue);
            box-shadow: 0 1px 10px rgba(2, 25, 58, .10);
            position: sticky;
            top: 0;
            z-index: 1030;
        }

        .bz-header .navbar-brand img {
            height: 46px;
            width: auto;
        }

        .bz-header .nav-link {
            color: var(--bz-navy) !important;
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: .92rem;
            padding: .5rem .85rem !important;
        }

        .bz-header .nav-link:hover,
        .bz-header .nav-link:focus {
            color: var(--bz-blue) !important;
        }

        .bz-header .dropdown-menu {
            border: 0;
            border-top: 3px solid var(--bz-blue);
            box-shadow: 0 .5rem 1.5rem rgba(2, 25, 58, .16);
            border-radius: 0 0 .35rem .35rem;
        }

        .bz-header .dropdown-item {
            font-weight: 600;
            font-size: .9rem;
            color: var(--bz-navy);
        }

        .bz-header .dropdown-item:hover {
            background: #eef6fe;
            color: var(--bz-blue);
        }

        .bz-zulu {
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            font-size: .95rem;
            color: var(--bz-navy);
            letter-spacing: .04em;
            white-space: nowrap;
        }

        .bz-zulu .z {
            color: var(--bz-blue);
        }

        /* ---- Hero -------------------------------------------------------- */
        .bz-hero {
            position: relative;
            min-height: 460px;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: #fff;
            background:
                linear-gradient(180deg, rgba(2, 25, 58, .72) 0%, rgba(2, 25, 58, .55) 45%, rgba(2, 25, 58, .88) 100%),
                var(--bz-hero-image, linear-gradient(160deg, #0780e9 0%, #052c5f 55%, #02193a 100%));
            background-size: cover;
            background-position: center;
        }

        .bz-hero__inner {
            padding: 3.5rem 1rem;
            max-width: 54rem;
        }

        .bz-hero img.logo {
            width: min(430px, 78vw);
            margin-bottom: 1.5rem;
            filter: drop-shadow(0 6px 18px rgba(0, 0, 0, .45));
        }

        .bz-hero p.tagline {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: clamp(1rem, 2.3vw, 1.35rem);
            letter-spacing: .04em;
            text-transform: uppercase;
            color: rgba(255, 255, 255, .92);
            margin-bottom: 1.75rem;
        }

        /* ---- Stats band -------------------------------------------------- */
        .bz-stats {
            background: var(--bz-navy);
            color: #fff;
            padding: 2.25rem 0;
        }

        .bz-stat {
            text-align: center;
            padding: .5rem 1rem;
        }

        .bz-stat .lbl {
            font-family: 'Montserrat', sans-serif;
            font-weight: 600;
            font-size: 1rem;
            color: #ffffff;
        }

        .bz-stat .val {
            font-family: 'Montserrat', sans-serif;
            font-weight: 800;
            font-size: clamp(2rem, 4vw, 3rem);
            line-height: 1.15;
            color: var(--bz-blue-2);
        }

        .bz-stat .sub {
            font-size: .88rem;
            color: rgba(255, 255, 255, .82);
        }

        .bz-stat .sub b {
            color: #fff;
        }

        /* ---- Dark welcome band ------------------------------------------- */
        .bz-welcome {
            background: #0a1220;
            color: #e7eef7;
            padding: 3rem 0 3.25rem;
        }

        .bz-welcome h2 {
            text-align: center;
            color: #fff;
            margin-bottom: 1.25rem;
        }

        .bz-welcome hr {
            border-color: rgba(255, 255, 255, .25);
            opacity: 1;
            margin-bottom: 2rem;
        }

        .bz-welcome a {
            color: var(--bz-blue-2);
        }

        /* ---- Panels (dispatch / latest flights) -------------------------- */
        .bz-ops {
            background: #f2f5f9;
            padding: 2.5rem 0 3rem;
        }

        [data-bs-theme=dark] .bz-ops {
            background: #121820;
        }

        .bz-panel {
            border: 1px solid var(--bz-line);
            border-radius: .4rem;
            overflow: hidden;
            background: var(--bs-body-bg);
            margin-bottom: 1.75rem;
        }

        .bz-panel__head {
            background: var(--bz-navy);
            color: #fff;
            padding: .7rem 1rem;
            font-family: 'Montserrat', sans-serif;
            font-weight: 700;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1rem;
        }

        .bz-panel__head .count {
            font-size: .78rem;
            font-weight: 600;
            background: var(--bz-blue);
            border-radius: 999px;
            padding: .1rem .6rem;
        }

        .bz-panel table {
            margin-bottom: 0;
        }

        .bz-panel thead th {
            font-family: 'Montserrat', sans-serif;
            font-size: .74rem;
            letter-spacing: .09em;
            text-transform: uppercase;
            color: var(--bz-navy);
            background: #e9eff7;
            border-bottom: 1px solid var(--bz-line);
            white-space: nowrap;
        }

        [data-bs-theme=dark] .bz-panel thead th {
            color: #cfe3fa;
            background: #1a2432;
        }

        .bz-badge {
            background: var(--bz-blue);
            color: #fff;
            font-weight: 600;
            letter-spacing: .04em;
        }

        .bz-empty {
            padding: 1.1rem 1rem;
            color: var(--bs-secondary-color);
        }

        /* ---- Map --------------------------------------------------------- */
        .bz-map-wrap {
            border-top: 3px solid var(--bz-blue);
            border-bottom: 3px solid var(--bz-blue);
        }

        /* ---- Footer ------------------------------------------------------ */
        footer.bz-footer {
            background: var(--bz-navy);
            color: rgba(255, 255, 255, .75);
            border-top: 4px solid var(--bz-blue);
            padding: 1.75rem 0;
        }

        footer.bz-footer a {
            color: #fff;
        }

        .bz-section-head {
            display: flex;
            align-items: center;
            gap: .9rem;
            margin-bottom: 1rem;
        }

        .bz-section-head h2 {
            margin: 0;
            font-size: 1.35rem;
            color: var(--bz-navy);
        }

        [data-bs-theme=dark] .bz-section-head h2 {
            color: #cfe3fa;
        }

        .bz-section-head .line {
            flex: 1;
            height: 2px;
            background: var(--bz-line);
        }
    </style>
</head>

<body>
    <div class="wrapper d-flex flex-column min-vh-100">
        @include('nav')

        {{-- Full-bleed sections (hero, stats band, map) render outside the container --}}
        @yield('fullwidth')

        {{-- Pages that build their own full-width sections set body_class to 'd-none'
             so this container doesn't leave an empty strip above the footer. --}}
        <div class="body container flex-grow-1 pt-4 @yield('body_class')">
            @include('flash.message')
            @yield('content')
        </div>

        <footer class="bz-footer mt-auto">
            <div class="container d-flex flex-wrap justify-content-between align-items-center">
                <div class="col-md-7">
                    <span>&copy; {{ date('Y') }} {{ config('app.name') }} &mdash; a virtual airline for flight
                        simulation. Not affiliated with Breeze Airways or any real-world operator.</span>
                </div>
                <div class="col-md-5 text-md-end">
                    <span>Powered by <a href="https://www.phpvms.net" target="_blank">phpVMS</a></span>
                </div>
            </div>
        </footer>
    </div>

    @include('external_redirect_modal')

    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.8/dist/umd/popper.min.js"
        integrity="sha384-I7E8VVD/ismYTF4hNIPjVp/Zjvgyol6VFvRkX/vR+Vc4jQkC+hVqc2pM8ODewa9r" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous">
    </script>
    <script src="https://cdn.jsdelivr.net/npm/tom-select@2.4.1/dist/js/tom-select.complete.min.js"></script>

    <script>
        const popoverTriggerList = document.querySelectorAll('[data-bs-toggle="popover"]')
        const popoverList = [...popoverTriggerList].map(popoverTriggerEl => new bootstrap.Popover(popoverTriggerEl))
    </script>

    {{-- Zulu clock in the header, same idea as frontierva.org --}}
    <script>
        (function() {
            const el = document.getElementById('bz-zulu-clock');
            if (!el) return;

            function tick() {
                const d = new Date();
                const p = (n) => String(n).padStart(2, '0');
                el.textContent = p(d.getUTCHours()) + ':' + p(d.getUTCMinutes()) + ':' + p(d.getUTCSeconds());
            }
            tick();
            setInterval(tick, 1000);
        })();
    </script>

    <script src="{{ public_mix('/assets/global/js/vendor.js') }}"></script>
    <script src="{{ public_mix('/assets/frontend/js/vendor.js') }}"></script>
    <script src="{{ public_mix('/assets/frontend/js/app.js') }}"></script>
    @yield('scripts')

    @include('scripts.bs_theme')

    <script>
        window.addEventListener("load", function() {
            window.cookieconsent.initialise({
                palette: {
                    popup: {
                        background: "#02193a",
                        text: "#dbe7f4"
                    },
                    button: {
                        "background": "#0780e9",
                        "text": "#ffffff"
                    }
                },
                position: "bottom",
            })
        });
    </script>

    @php
        $gtag = setting('general.google_analytics_id');
    @endphp
    @if ($gtag)
        <script async src="https://www.googletagmanager.com/gtag/js?id={{ $gtag }}"></script>
        <script>
            window.dataLayer = window.dataLayer || [];

            function gtag() {
                dataLayer.push(arguments);
            }
            gtag('js', new Date());

            gtag('config', '{{ $gtag }}');
        </script>
    @endif

</body>

</html>
