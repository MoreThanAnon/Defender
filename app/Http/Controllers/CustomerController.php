<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Address;

class CustomerController extends Controller
{
    public function edit(Request $request)
    {
        $user = Auth::user();
        $id = $user->id;
        $customer = $user;
        $address = $user->address;

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
                $eircode = $request->input('eircode');
                $eircodeRegion = $eircode
                    ? strtoupper(substr(preg_replace('/\s+/', '', $eircode), 0, 3))
                    : null;

                $customer->update([
                    'real_name' => $request->input('real_name'),
                    'email' => $request->input('email'),
                    'eircode_region' => $eircodeRegion,
                ]);

                $addressData = [
                    'address1' => $request->input('address1'),
                    'address2' => $request->input('address2'),
                    'address3' => $request->input('address3'),
                    'city'     => $request->input('town'),
                    'eircode'  => $eircode,
                ];

                if ($address) {
                    $address->update($addressData);
                } else {
                    $addressData['user_id'] = $id;
                    $address = Address::create($addressData);
                }

                $request->session()->flash('success', 'Customer information updated successfully.');
            } catch (\Exception $e) {
                $request->session()->flash('error', 'An error occurred: ' . $e->getMessage());
            }
        }

        return view('customer.edit', ['id' => $id, 'customer' => $customer, 'address' => $address]);
    }
}
