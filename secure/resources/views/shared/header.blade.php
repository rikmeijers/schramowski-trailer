<nav class="navbar navbar-expand-lg fixed-top modern-header">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}">
            <img src="{{ url('/assets/images/branding/icon.png') }}" width="50" alt="Icon">
            <span class="ms-2 fw-semibold app-name mobile-hidden">{{ config('app.name', 'App') }}</span>
        </a>

        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav"
                aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list fs-3 d-block" id="menu-open-icon"></i>
            <i class="bi bi-x-lg fs-3 d-none" id="menu-close-icon"></i>
        </button>

        <div class="collapse navbar-collapse" id="navbarNav">
            @if ($agent->isMobile())
                <br />
            @endif

            <ul class="navbar-nav ms-auto">
                @if(auth()->check())
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 me-2 dashboard {{ request()->routeIs('dashboard*') ? 'active' : '' }}" href="{{ route('dashboard') }}">Übersicht</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 me-2 trailers {{ (request()->routeIs('trailers.*') || request()->routeIs('reservations.*')) ? 'active' : '' }}" href="{{ route('trailers.index') }}">Anhänger</a>
                    </li>
                    @can('admin')
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 me-2 users {{ (request()->routeIs('users.index') || request()->routeIs('users.destroy')) ? 'active' : '' }}" href="{{ route('users.index') }}">Benutzer</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link px-3 py-2 me-2 register {{ request()->routeIs('register.*') ? 'active' : '' }}" href="{{ route('register.form') }}">Benutzer hinzufügen</a>
                        </li>
                    @endcan
                @endif
            </ul>

            <ul class="navbar-nav ms-auto">
                @if(auth()->check())
                    <li class="nav-item d-flex align-items-center me-2">
                        <a class="nav-link px-3 py-2 settings {{ request()->routeIs('settings.*') ? 'active' : '' }}" href="{{ url('/settings') }}">
                            <i class="bi bi-gear"></i>
                        </a>
                    </li>
                    <li class="nav-item">
                        <form id="logout-form" action="{{ route('logout') }}" method="POST">
                            @csrf
                            <button type="submit"
                                    class="nav-link px-3 py-2 fw-normal btn d-flex align-items-center">
                                <i class="bi bi-box-arrow-right me-2"></i> Abmelden
                            </button>
                        </form>
                    </li>
                @else
                    <li class="nav-item">
                        <a class="nav-link px-3 py-2 login d-flex align-items-center"
                           href="{{ url('/login') }}">
                            <i class="bi bi-box-arrow-in-right me-2"></i> Anmelden
                        </a>
                    </li>
                @endif
            </ul>
        </div>
    </div>
</nav>

<div style="height: 80px;"></div>
