@auth
    @php
        $impersonator = session()->has('impersonator_id') ? \App\Models\User::find(session('impersonator_id')) : null;
        $adminUser = $impersonator ?: auth()->user();
    @endphp

    @if ($adminUser && $adminUser->hasRole('admin'))
        <nav class="admin-nav">
            <ul class="nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Admin Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.properties.index') }}">Properties</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('property.create') }}">Create Property</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Act as User</a></li>
            </ul>
        </nav>
    @endif
@endauth

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        @php
            $propertyId = request()->route(param: 'id') ?? optional(request()->route('property'))->id;
        @endphp

        @if($propertyId && auth()->check() && $adminUser && ($adminUser->hasRole('admin') || $adminUser->hasRole('moderator')))
            <li class="nav-item">
                <a
                  class="nav-link {{ request()->routeIs('properties.edit') && (int)request()->route('id') === (int)$propertyId ? 'active' : '' }}"
                  href="{{ route('properties.edit', ['id' => $propertyId]) }}"
                >
                  Edit Property
                </a>
            </li>
        @endif
      </ul>
    </div>
  </div>
</nav>
