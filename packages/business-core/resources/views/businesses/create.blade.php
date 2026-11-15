@extends(config('business-core.layout'))
@section('title', 'Add a business')

@section('content')
<div class="row justify-content-center"><div class="col-lg-7">
    <h1 class="h3 mb-3">Add a business</h1>
    <form method="POST" action="{{ route('bc.store') }}" class="card"><div class="card-body">
        @csrf
        <div class="mb-3">
            <label class="form-label" for="name">Name</label>
            <input class="form-control" id="name" name="name" value="{{ old('name') }}" required maxlength="120" placeholder="e.g. 12 Harbour Road, or Project 027 Ltd">
            <div class="form-text">One record per house, business or piece of land.</div>
        </div>
        <div class="mb-3">
            <label class="form-label" for="type">What is it?</label>
            <select class="form-select" id="type" name="type">
                @foreach (config('business-core.types') as $value => $label)
                    <option value="{{ $value }}" @selected(old('type', 'business') === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="row g-3 mb-3">
            <div class="col-md-7">
                <label class="form-label" for="legal_name">Registered company name <span class="text-muted">(if it has one)</span></label>
                <input class="form-control" id="legal_name" name="legal_name" value="{{ old('legal_name') }}" maxlength="191">
            </div>
            <div class="col-md-5">
                <label class="form-label" for="registration_number">Registration number</label>
                <input class="form-control" id="registration_number" name="registration_number" value="{{ old('registration_number') }}" maxlength="60">
            </div>
        </div>
        <p class="small text-muted">
            It starts <strong>in storage</strong>: a private sandbox where you can set things up and practise with sample statements.
            It is marked live once it has been checked against a real company and that company's own bank account.
            You become its director here. This software never holds or moves the business's money.
        </p>
        <button class="btn btn-primary">Create</button>
        <a href="{{ route('bc.index') }}" class="btn btn-link">Cancel</a>
    </div></form>
</div></div>
@endsection
