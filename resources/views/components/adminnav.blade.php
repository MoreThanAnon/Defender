@auth
    {{-- Show admin navigation only to authenticated users with role === 'admin' --}}
    @if (auth()->user()->role === 'admin')
        <nav class="admin-nav">
            <ul class="nav">
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.dashboard') }}">Admin Dashboard</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.users.index') }}">Users</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.properties.index') }}">Properties</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.offerings.index') }}">Offerings</a></li>
                <li class="nav-item"><a class="nav-link" href="{{ route('admin.reports.index') }}">Reports</a></li>
            </ul>
        </nav>
    @endif
@endauth

<nav class="navbar navbar-expand-lg navbar-light bg-light">
  <div class="container">
    <!-- ... your existing nav brand/toggler ... -->

    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        <!-- ... other nav items ... -->

        @php
            // Try to find a property id from route parameters (supports routes that bind 'id' or a Property model named 'property')
            $propertyId = request()->route(param: 'id') ?? optional(request()->route('property'))->id;
        @endphp

        @if($propertyId && auth()->check())
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

      <!-- ... right side nav / auth links ... -->
    </div>
  </div>
</nav>