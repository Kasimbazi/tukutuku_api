    <?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;

class OrderController extends Controller
{
    /**
     * Update the order status (e.g., accepted, in_transit, completed)
     */
    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|in:accepted,in_transit,completed,cancelled',
        ]);

        $order = Order::findOrFail($id);

        // Prevent modifying already completed or cancelled orders
        if (in_array($order->status, ['completed', 'cancelled'])) {
            return response()->json([
                'message' => 'This order has already been finalized.'
            ], 400);
        }

        try {
            DB::transaction(function () use ($order, $request) {
                $order->status = $request->status;
                
                // If the driver accepts, assign them to the order
                if ($request->status === 'accepted' && $request->has('driver_id')) {
                    $order->driver_id = $request->driver_id;
                }

                $order->save();
            });

            return response()->json([
                'success' => true,
                'message' => 'Order status updated successfully.',
                'data' => $order
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update order status.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

        public function store(Request $request)
    {
        // 1. Validate and create the order
        $order = Order::create($request->all());

        // 2. Send Push Notification to Driver(s) or Specific Driver FCM Token
        $driverFcmToken = $order->driver->fcm_token ?? null; // Assuming drivers store their device FCM token

        if ($driverFcmToken) {
            Http::withToken(config('services.firebase.server_key')) // Or use Google OAuth token for FCM v1
                ->post('https://fcm.googleapis.com/fcm/send', [
                    'to' => $driverFcmToken,
                    'notification' => [
                        'title' => 'New Delivery Request!',
                        'body' => 'Pickup at ' . $order->pickup_location . ' for UGX ' . number_format($order->total_amount),
                        'sound' => 'default',
                    ],
                    'data' => [
                        'order_id' => $order->id,
                        'type' => 'new_order',
                    ],
                ]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Order created and notification sent.',
            'data' => $order
        ], 201);
    }

}