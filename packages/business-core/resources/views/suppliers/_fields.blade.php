<div class="col-12"><input class="form-control form-control-sm" name="name" value="{{ $supplier?->name }}" placeholder="Name" required aria-label="Supplier name"></div>
<div class="col-6"><input class="form-control form-control-sm" name="email" type="email" value="{{ $supplier?->email }}" placeholder="Email" aria-label="Email"></div>
<div class="col-6"><input class="form-control form-control-sm" name="phone" value="{{ $supplier?->phone }}" placeholder="Phone" aria-label="Phone"></div>
@if ($showPaymentDetails)
    <div class="col-12"><textarea class="form-control form-control-sm" name="payment_details" rows="2" placeholder="Payee details from their invoice (account name, IBAN)" aria-label="Payee details">{{ $supplier?->payment_details }}</textarea></div>
@else
    <input type="hidden" name="payment_details" value="{{ $supplier?->payment_details }}">
@endif
<div class="col-12"><input class="form-control form-control-sm" name="notes" value="{{ $supplier?->notes }}" placeholder="Notes" aria-label="Notes"></div>
