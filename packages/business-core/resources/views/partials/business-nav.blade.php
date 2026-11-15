@php
    $bcCurrent = app(\BusinessCore\Support\CurrentBusiness::class);
    $bcBusiness = $bcCurrent->get();
    $bcTabs = [
        ['bc.show', 'Dashboard', 'view', 'bc.show'],
        ['bc.bank.index', 'Bank', 'reports', 'bc.bank.*'],
        ['bc.projects.index', 'Budgets & projects', 'view', 'bc.projects.*'],
        ['bc.proposals.index', 'Proposals', 'view', 'bc.proposals.*'],
        ['bc.payments.index', 'Payments', 'reports', 'bc.payments.*'],
        ['bc.tasks.index', 'Tasks', 'view', 'bc.tasks.*'],
        ['bc.suppliers.index', 'Suppliers', 'view', 'bc.suppliers.*'],
        ['bc.documents.index', 'Records', 'view', 'bc.documents.*'],
        ['bc.members.index', 'People', 'view', 'bc.members.*'],
        ['bc.reports.index', 'Reports', 'reports', 'bc.reports.*'],
        ['bc.settings', 'Settings', 'manage', 'bc.settings*'],
    ];
@endphp
@if ($bcBusiness)
    @if ($bcBusiness->isStored())
        <div class="bc-storage small py-1">
            <div class="container-xl">
                <strong>In storage.</strong> This business is a sandbox and is not linked to a live company or bank account. Treat every figure as practice data.
            </div>
        </div>
    @elseif ($bcBusiness->status === 'archived')
        <div class="bc-storage small py-1"><div class="container-xl"><strong>Archived.</strong> Read-only.</div></div>
    @endif
    <div class="bc-subnav">
        <div class="container-xl">
            <div class="d-flex align-items-center gap-2 pt-2">
                <span class="fw-bold">{{ $bcBusiness->name }}</span>
                <span class="badge text-bg-light border">{{ $bcBusiness->typeLabel() }}</span>
                <span class="badge {{ $bcBusiness->isLive() ? 'text-bg-success' : 'text-bg-warning' }}">{{ ucfirst($bcBusiness->status) }}</span>
                <span class="text-muted small ms-auto">Your role: {{ config('business-core.roles.'.$bcCurrent->role().'.label', $bcCurrent->role()) }}</span>
            </div>
            <nav class="nav">
                @foreach ($bcTabs as $bcTab)
                    @if ($bcCurrent->can($bcTab[2]))
                        <a class="nav-link {{ request()->routeIs($bcTab[3]) ? 'active' : '' }}" href="{{ route($bcTab[0]) }}">{{ $bcTab[1] }}</a>
                    @endif
                @endforeach
            </nav>
        </div>
    </div>
@endif
