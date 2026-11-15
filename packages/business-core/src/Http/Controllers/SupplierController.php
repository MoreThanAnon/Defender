<?php

namespace BusinessCore\Http\Controllers;

use BusinessCore\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index()
    {
        $this->allow('view');

        return $this->view('suppliers.index', [
            'suppliers' => Supplier::orderBy('name')->get(),
            // Payee bank details are only shown to people who prepare or approve payments.
            'showPaymentDetails' => $this->current()->can('finance') || $this->current()->can('decide'),
        ]);
    }

    public function store(Request $request)
    {
        abort_unless($this->current()->can('operate') || $this->current()->can('finance'), 403);

        $supplier = Supplier::create($this->validated($request));
        $this->audit('supplier.created', $supplier, ['name' => $supplier->name]);

        return back()->with('status', 'Supplier added.');
    }

    public function update(Request $request, Supplier $supplier)
    {
        abort_unless($this->current()->can('operate') || $this->current()->can('finance'), 403);

        $data = $this->validated($request);
        // Changing where money should be sent is a classic fraud route: finance or directors only, and always logged.
        if (($data['payment_details'] ?? null) !== $supplier->payment_details) {
            abort_unless($this->current()->can('finance') || $this->current()->can('decide'), 403, 'Only finance or a director can change payee details.');
        }

        $supplier->update($data);
        $this->audit('supplier.updated', $supplier, ['fields' => array_keys($supplier->getChanges())]);

        return back()->with('status', 'Supplier saved.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:120'],
            'email' => ['nullable', 'email', 'max:191'],
            'phone' => ['nullable', 'string', 'max:40'],
            'payment_details' => ['nullable', 'string', 'max:1000'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);
    }
}
