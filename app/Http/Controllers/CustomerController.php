<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    public function edit(Request $request)
    {
        $user = Auth::user();
        $id = $user->id;
        $customer = $user;

        if ($request->isMethod('post')) {
            $request->validate([
                'id' => 'required|integer|exists:users,id',
                'real_name' => 'required|string|max:255',
                'email' => 'required|email|max:255',
                'address1' => 'nullable|string|max:255',
                'address2' => 'nullable|string|max:255',
                'address3' => 'nullable|string|max:255',
                'town' => 'nullable|string|max:255',
                'eircode' => 'nullable|string|max:10',
            ]);

            try {
                $customer->update($request->only([
                    'real_name', 'email', 'address1', 'address2', 'address3', 'town', 'eircode'
                ]));

                $request->session()->flash('success', 'Customer information updated successfully.');
            } catch (\Exception $e) {
                $request->session()->flash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        return view('customer.edit', ['id' => $id, 'customer' => $customer]);
    }
}
