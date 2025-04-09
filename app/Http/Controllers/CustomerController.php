<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CustomerController extends Controller
{
    /**
     * Show the form for editing customer information.
     */
    public function edit()
    {
        // Return a view for editing customer information
        return view('customer.edit');
    }

    public function test($eircode){
        $response = shell_exec("python3 path/to/python_script.py {$eircode}");

    }
    /**
     * Update the customer information.
     */
    public function update(Request $request)
    {
        // Validate and update customer information
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'address1' => 'nullable|string|max:255',
            'address2' => 'nullable|string|max:255',
            'address3' => 'nullable|string|max:255',
            'town' => 'nullable|string|max:255',
            'eircode' => 'nullable|string|max:10',
        ]);

        // Assuming the authenticated user is the customer
        $user = auth()->user();
        $user->update($request->only('name', 'email', 'address1', 'address2', 'address3', 'town', 'eircode'));

        return redirect()->route('customer.edit')->with('success', 'Customer information updated successfully.');
    }
}
