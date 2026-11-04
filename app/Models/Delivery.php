<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Delivery extends Model
{
    use HasFactory;

    protected $fillable = [
        'pickup_location',
        'dropoff_location',
        'cargo_type',
        'fare_ugx',
        'status',
    ];
    /**
     * Mark a delivery order as completed.
     */
    public function complete(Request $request, $id)
    {
        $delivery = Delivery::find($id);

        if (!$delivery) {
            return response()->json(['message' => 'Delivery not found'], 404);
        }

        if ($delivery->status !== 'accepted') {
            return response()->json(['message' => 'Only accepted orders can be completed.'], 400);
        }

        $delivery->update(['status' => 'completed']);

        return response()->json([
            'message'  => 'Delivery completed successfully',
            'delivery' => $delivery,
        ], 200);
    }
}