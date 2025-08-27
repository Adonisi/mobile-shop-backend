<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $orders = Order::with(['orderItems.product', 'user'])
            ->when($user->role !== 'admin', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $orders,
            'message' => 'Orders retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // \Log::info('Order request: ' . json_encode($request->all()));
        $request->validate([
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
            'shipping_address' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();
            $items = $request->input('items');
            $subtotal = 0;
            $orderItems = [];

            // Validate stock and calculate totals
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->stock < $item['quantity']) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}"
                    ]);
                }

                $unitPrice = $product->sell_price;
                $discount = $item['discount'] ?? 0;
                $finalUnitPrice = $unitPrice - $discount;
                $totalPrice = $finalUnitPrice * $item['quantity'];
                $subtotal += $totalPrice;

                $orderItems[] = [
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $unitPrice,
                    'discount' => $discount,
                    'total_price' => $totalPrice,
                ];
            }

            // Calculate totals
            $tax = $subtotal * 0.081; // 8.1% tax
            $total = $subtotal + $tax;

            // Create order
            $order = Order::create([
                'user_id' => $user->id,
                'order_number' => Order::generateOrderNumber(),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'total' => $total,
                'shipping_address' => $request->input('shipping_address'),
                'customer_name' => $request->input('customer_name'),
                'customer_email' => $request->input('customer_email'),
                'customer_phone' => $request->input('customer_phone'),
                'notes' => $request->input('notes'),
            ]);

            // Create order items and update stock
            foreach ($orderItems as $item) {
                $order->orderItems()->create($item);
                
                // Update product stock
                $product = Product::find($item['product_id']);
                $product->decrement('stock', $item['quantity']);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['orderItems.product', 'user']),
                'message' => 'Order created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Check if user can view this order
        if ($user->role !== 'admin' && $order->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $order->load(['orderItems.product', 'user']),
            'message' => 'Order retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $user = $request->user();
        \Log::info('Order request: ' . json_encode($request->all()));
        
        // Only admins can update orders
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'notes' => 'nullable|string',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'shipping_address' => 'nullable|string',
            'items' => 'nullable|array',
            'items.*.id' => 'nullable|exists:order_items,id',
            'items.*.product_id' => 'required_without:items.*.id|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.discount' => 'nullable|numeric|min:0',
        ]);

        try {
            DB::beginTransaction();

            $data = [];
            
            if ($request->has('notes')) {
                $data['notes'] = $request->input('notes');
            }
            
            if ($request->has('customer_name')) {
                $data['customer_name'] = $request->input('customer_name');
            }
            
            if ($request->has('customer_email')) {
                $data['customer_email'] = $request->input('customer_email');
            }
            
            if ($request->has('customer_phone')) {
                $data['customer_phone'] = $request->input('customer_phone');
            }
            
            if ($request->has('shipping_address')) {
                $data['shipping_address'] = $request->input('shipping_address');
            }

            // Update order basic info
            $order->update($data);

            // Update order items if provided
            if ($request->has('items')) {
                $subtotal = 0;
                
                foreach ($request->input('items') as $itemData) {
                    if (isset($itemData['id'])) {
                        // Update existing order item
                        $orderItem = $order->orderItems()->findOrFail($itemData['id']);
                        $product = $orderItem->product;
                        
                        // Calculate new values
                        $oldQuantity = $orderItem->quantity;
                        $newQuantity = $itemData['quantity'];
                        $discount = $itemData['discount'] ?? $orderItem->discount; // Use existing discount if not provided
                        $unitPrice = $orderItem->unit_price; // Use the original unit price from the order item
                        $finalUnitPrice = $unitPrice - $discount;
                        $totalPrice = $finalUnitPrice * $newQuantity;
                        
                        // Handle stock adjustment
                        if ($newQuantity > $oldQuantity) {
                            // Increasing quantity - check if we have enough stock
                            $additionalQuantity = $newQuantity - $oldQuantity;
                            if ($product->stock < $additionalQuantity) {
                                throw ValidationException::withMessages([
                                    'items' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}, needed: {$additionalQuantity}"
                                ]);
                            }
                            // Decrease stock by the additional quantity
                            $product->decrement('stock', $additionalQuantity);
                        } elseif ($newQuantity < $oldQuantity) {
                            // Decreasing quantity - add back the difference to stock
                            $returnedQuantity = $oldQuantity - $newQuantity;
                            $product->increment('stock', $returnedQuantity);
                        }
                        // If quantities are equal, no stock adjustment needed
                        
                        // Update order item
                        $orderItem->update([
                            'quantity' => $newQuantity,
                            'discount' => $discount,
                            'total_price' => $totalPrice,
                        ]);
                        
                        $subtotal += $totalPrice;
                    } else {
                        // Create new order item
                        $product = Product::findOrFail($itemData['product_id']);
                        
                        // Check stock availability
                        if ($product->stock < $itemData['quantity']) {
                            throw ValidationException::withMessages([
                                'items' => "Insufficient stock for product: {$product->name}. Available: {$product->stock}"
                            ]);
                        }
                        
                        $unitPrice = $product->sell_price;
                        $discount = $itemData['discount'] ?? 0;
                        $finalUnitPrice = $unitPrice - $discount;
                        $totalPrice = $finalUnitPrice * $itemData['quantity'];
                        
                        // Create new order item
                        $order->orderItems()->create([
                            'product_id' => $product->id,
                            'quantity' => $itemData['quantity'],
                            'unit_price' => $unitPrice,
                            'discount' => $discount,
                            'total_price' => $totalPrice,
                        ]);
                        
                        // Update product stock
                        $product->decrement('stock', $itemData['quantity']);
                        
                        $subtotal += $totalPrice;
                    }
                }
                
                // Recalculate order totals
                $tax = $subtotal * 0.081; // 8.1% tax
                $total = $subtotal + $tax;
                
                $order->update([
                    'subtotal' => $subtotal,
                    'tax' => $tax,
                    'total' => $total,
                ]);
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $order->load(['orderItems.product', 'user']),
                'message' => 'Order updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Order $order)
    {
        $user = $request->user();
        
        // Only admins can delete orders
        if ($user->role !== 'admin') {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        // Only allow deletion of pending orders
        

        try {
            DB::beginTransaction();

            // Restore product stock
            foreach ($order->orderItems as $item) {
                $product = Product::find($item->product_id);
                $product->increment('stock', $item->quantity);
            }

            // Delete order (order items will be deleted automatically due to cascade)
            $order->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Order deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get order statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $query = Order::query();
        
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $statistics = [
            'total_orders' => $query->count(),
            'total_revenue' => $query->sum('total'),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Order statistics retrieved successfully'
        ]);
    }
}
