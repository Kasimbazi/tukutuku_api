<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Delivery;
use Illuminate\Http\Request;

class DeliveryController extends Controller
{
    /**
     * Get all delivery orders.
     */
    public function index()
    {
        return response()->json(Delivery::latest()->get(), 200);
    }

                /**
             * Accept a pending delivery order.
             */
            public function accept(Request $request, $id)
            {
                $delivery = Delivery::find($id);

                if (!$delivery) {
                    return response()->json(['message' => 'Delivery not found'], 404);
                }

                if ($delivery->status !== 'pending') {
                    return response()->json(['message' => 'This order has already been taken or completed.'], 400);
                }

                $delivery->update([
                    'status' => 'accepted',
                    // 'driver_id' => $request->input('driver_id'), // Uncomment when you add driver auth
                ]);

                return response()->json([
                    'message'  => 'Delivery accepted successfully',
                    'delivery' => $delivery,
                ], 200);
            }

    /**
     * Create a new delivery order and calculate UGX fare.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'pickup_location'  => 'required|string|max:255',
            'dropoff_location' => 'required|string|max:255',
            'cargo_type'       => 'required|string',
        ]);

        // Base fare calculation in UGX
        $baseFare = 3000;
        
        // Multiplier based on cargo weight/type class
        $cargoMultiplier = match ($validated['cargo_type']) {
            'Heavy Duty Cargo (Up to 800kg)' => 1.5,
            'Enclosed Box (Weatherproof)'    => 1.3,
            default                          => 1.0,
        };

        // Flat estimated distance charge for MVP (UGX 1,500 per km for ~5km avg)
        $estimatedDistanceKm = 5; 
        $perKmRate = 1500;

        $totalFare = ($baseFare + ($estimatedDistanceKm * $perKmRate)) * $cargoMultiplier;

        $delivery = Delivery::create([
            'pickup_location'  => $validated['pickup_location'],
            'dropoff_location' => $validated['dropoff_location'],
            'cargo_type'       => $validated['cargo_type'],
            'fare_ugx'         => $totalFare,
            'status'           => 'pending',
        ]);

        return response()->json([
            'message'  => 'Delivery order created successfully',
            'delivery' => $delivery,
        ], 201);
    }

    /**
     * Show details for a specific delivery order.
     */
    public function show($id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        return response()->json($delivery, 200);
    }
}