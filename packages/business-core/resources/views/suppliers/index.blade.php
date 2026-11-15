@extends(config('business-core.layout'))
@section('title', 'Suppliers')

@section('content')
@php($canEdit = app(\BusinessCore\Support\CurrentBusiness::class)->can('operate') || app(\BusinessCore\Support\CurrentBusiness::class)->can('finance'))
<div class="row g-4">
    <div class="col-lg-8">
        <h1 class="h4">Suppliers &amp; contractors</h1>
        <div class="card"><table class="table mb-0 align-middle">
            <thead><tr><th>Supplier</th><th>Contact</th>@if ($showPaymentDetails)<th>Payee details</th>@endif</tr></thead>
            <tbody>
            @forelse ($suppliers as $supplier)
                <tr>
                    <td>{{ $supplier->name }}<div class="small text-muted">{{ $supplier->notes }}</div></td>
                    <td class="small">{{ $supplier->email }}<br>{{ $supplier->phone }}</td>
                    @if ($showPaymentDetails)<td class="small" style="white-space: pre-line">{{ $supplier->payment_details ?: '—' }}</td>@endif
                </tr>
                @if ($canEdit)
                    <tr><td colspan="3" class="pt-0 border-0">
                        <details class="small"><summary>Edit</summary>
                            <form method="POST" action="{{ route('bc.suppliers.update', $supplier) }}" class="row g-2 mt-1">
                                @csrf @method('PUT')
                                @include('business-core::suppliers._fields', ['supplier' => $supplier])
                                <div class="col-12"><button class="btn btn-sm btn-outline-primary">Save</button></div>
                            </form>
                        </details>
                    </td></tr>
                @endif
            @empty
                <tr><td colspan="3" class="text-muted">No suppliers yet.</td></tr>
            @endforelse
            </tbody>
        </table></div>
    </div>
    @if ($canEdit)
    <div class="col-lg-4">
        <form method="POST" action="{{ route('bc.suppliers.store') }}" class="card"><div class="card-body row g-2">
            @csrf
            <h2 class="h6">Add a supplier</h2>
            @include('business-core::suppliers._fields', ['supplier' => null])
            <div class="col-12"><button class="btn btn-primary btn-sm">Add supplier</button></div>
        </div></form>
    </div>
    @endif
</div>
@endsection
