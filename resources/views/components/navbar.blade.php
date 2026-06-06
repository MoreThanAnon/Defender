@php
    $current = Request::path();
    $impersonator = session()->has('impersonator_id') ? \App\Models\User::find(session('impersonator_id')) : null;
    $adminUser = $impersonator ?: auth()->user();
    $canUseAdminTools = $adminUser && ($adminUser->hasRole('admin') || $adminUser->hasRole('moderator'));
@endphp
<nav class="navbar navbar-expand-lg navbar-light bg-white border-bottom shadow-sm mb-4">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary" href="{{ url('/home') }}">Defender</a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar"
            aria-controls="mainNavbar" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse justify-content-end" id="mainNavbar">
            <ul class="navbar-nav align-items-center gap-2">
                <li class="nav-item">
                    <a class="nav-link {{ Request::is('home') ? 'active fw-semibold text-primary' : '' }}" href="{{ url('/home') }}">
                        Home
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::is('properties') ? 'active fw-semibold text-primary' : '' }}" href="{{ url('/properties') }}">
                        Properties
                    </a>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('test-runner.*') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('test-runner.index') }}">
                        Test Runner
                    </a>
                </li>

                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle {{ Request::routeIs('customer.edit') || Request::routeIs('card.add') ? 'text-primary fw-semibold' : '' }}"
                       href="#" id="detailsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        My Details
                    </a>
                    <ul class="dropdown-menu" aria-labelledby="detailsDropdown">
                        <li>
                            <a class="dropdown-item {{ Request::routeIs('customer.edit') ? 'active' : '' }}" href="{{ route('customer.edit') }}">
                                Edit Info
                            </a>
                        </li>
                        <li>
                            <a class="dropdown-item {{ Request::routeIs('card.add') ? 'active' : '' }}" href="{{ route('card.add') }}">
                                Add Card
                            </a>
                        </li>
                    </ul>
                </li>

                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('stripe.deposit') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('stripe.deposit') }}">
                        Deposit Funds
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link {{ Request::routeIs('transactions.index') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('transactions.index') }}">
                        My Transactions
                    </a>
                </li>

                @auth
                    @if($canUseAdminTools)
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle {{ Request::routeIs('admin.*') ? 'text-primary fw-semibold' : '' }}"
                               href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Admin
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="adminDropdown">
                                @if($adminUser->hasRole('admin'))
                                    <li>
                                        <a class="dropdown-item {{ Request::routeIs('admin.dashboard') ? 'active' : '' }}" href="{{ route('admin.dashboard') }}">
                                            Dashboard
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item {{ Request::routeIs('admin.properties.index') ? 'active' : '' }}" href="{{ route('admin.properties.index') }}">
                                            Properties
                                        </a>
                                    </li>
                                @endif
                                <li>
                                    <a class="dropdown-item {{ Request::routeIs('admin.users.index') ? 'active' : '' }}" href="{{ route('admin.users.index') }}">
                                        Act as User
                                    </a>
                                </li>
                            </ul>
                        </li>
                    @endif

                    @if($impersonator)
                        <li class="nav-item">
                            <span class="navbar-text small text-warning">
                                Acting as user #{{ auth()->id() }}
                            </span>
                        </li>
                        <li class="nav-item">
                            <form method="POST" action="{{ route('admin.impersonate.stop') }}">
                                @csrf
                                <button type="submit" class="btn btn-sm btn-warning ms-2">
                                    Back to Admin
                                </button>
                            </form>
                        </li>
                    @endif

                    <li class="nav-item">
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-secondary ms-2">
                                Logout
                            </button>
                        </form>
                    </li>
                @endauth

                @guest
                    <li class="nav-item">
                        <a class="nav-link {{ Request::routeIs('login') ? 'active fw-semibold text-primary' : '' }}" href="{{ route('login') }}">
                            Login
                        </a>
                    </li>
                @endguest
            </ul>
        </div>
    </div>
</nav>
