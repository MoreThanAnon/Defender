@php
    $current = Request::path()
@endphp

<nav class="navbar navbar-expand-lg navbar-light bg-light mb-4">
    <a class="navbar-brand" href="#">Defender</a>
    <div class="collapse navbar-collapse">
        <ul class="navbar-nav mr-auto">
            <li class="nav-item">
                <a class="nav-link {{ Request::is('home') ? 'text-primary fw-bold' : '' }}" href="{{ url('/home') }}">
                    Home
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::is('properties') ? 'text-primary fw-bold' : '' }}" href="{{ url('/properties') }}">
                    Properties
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ Request::routeIs('customer.edit') ? 'text-primary fw-bold' : '' }}" href="{{ route('customer.edit') }}">
                    My Details
                </a>
            </li>
            @auth
            <li class="nav-item">
                <a class="nav-link {{ Request::is('logout') ? 'text-primary fw-bold' : '' }}" href="{{ url('/logout') }}">
                    Logout
                </a>
            </li>
            @endauth
            @guest
            <li class="nav-item">
                <a class="nav-link {{ Request::is('Login') ? 'text-primary fw-bold' : '' }}" href="{{ route('login') }}">
                    Login
                </a>
            </li>
            @endguest
        </ul>
        <a class="nav-link {{ Request::routeIs('deposit.funds') ? 'text-primary fw-bold' : '' }}" href="{{ route('deposit.funds') }}">
            Deposit Funds
        </a>
    </div>
</nav>