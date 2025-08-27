<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ServiceController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        
        $services = Service::with('user')
            ->when($user->role !== 'admin', function ($query) use ($user) {
                return $query->where('user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return response()->json([
            'success' => true,
            'data' => $services,
            'message' => 'Services retrieved successfully'
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'device_type' => 'nullable|string|max:255',
            'device_brand' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:255',
            'problem_description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'estimated_completion_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $user = $request->user();

            $service = Service::create([
                'user_id' => $user->id,
                'service_number' => Service::generateServiceNumber(),
                'customer_name' => $request->customer_name,
                'customer_phone' => $request->customer_phone,
                'customer_email' => $request->customer_email,
                'device_type' => $request->device_type,
                'device_brand' => $request->device_brand,
                'device_model' => $request->device_model,
                'service_type' => $request->service_type,
                'problem_description' => $request->problem_description,
                'estimated_cost' => $request->estimated_cost,
                'status' => $request->status ?? 'pending',
                'priority' => $request->priority ?? 'medium',
                'estimated_completion_date' => $request->estimated_completion_date,
                'notes' => $request->notes,
            ]);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $service->load('user'),
                'message' => 'Service created successfully'
            ], 201);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, Service $service)
    {
        $user = $request->user();
        
        // Check if user can view this service
        if ($user->role !== 'admin' && $service->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $service->load('user'),
            'message' => 'Service retrieved successfully'
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Service $service)
    {
        $user = $request->user();
        
        // Check if user can update this service
        if ($user->role !== 'admin' && $service->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        $request->validate([
            'customer_name' => 'nullable|string|max:255',
            'customer_phone' => 'nullable|string|max:20',
            'customer_email' => 'nullable|email|max:255',
            'device_type' => 'nullable|string|max:255',
            'device_brand' => 'nullable|string|max:255',
            'device_model' => 'nullable|string|max:255',
            'service_type' => 'nullable|string|max:255',
            'problem_description' => 'nullable|string',
            'estimated_cost' => 'nullable|numeric|min:0',
            'status' => 'nullable|in:pending,in_progress,completed,cancelled',
            'priority' => 'nullable|in:low,medium,high,urgent',
            'estimated_completion_date' => 'nullable|date',
            'notes' => 'nullable|string',
        ]);

        try {
            DB::beginTransaction();

            $data = [];

            if ($request->has('customer_name')) {
                $data['customer_name'] = $request->customer_name;
            }
            if ($request->has('customer_phone')) {
                $data['customer_phone'] = $request->customer_phone;
            }
            if ($request->has('customer_email')) {
                $data['customer_email'] = $request->customer_email;
            }
            if ($request->has('device_type')) {
                $data['device_type'] = $request->device_type;
            }
            if ($request->has('device_brand')) {
                $data['device_brand'] = $request->device_brand;
            }
            if ($request->has('device_model')) {
                $data['device_model'] = $request->device_model;
            }
            if ($request->has('service_type')) {
                $data['service_type'] = $request->service_type;
            }
            if ($request->has('problem_description')) {
                $data['problem_description'] = $request->problem_description;
            }
            if ($request->has('estimated_cost')) {
                $data['estimated_cost'] = $request->estimated_cost;
            }
            if ($request->has('status')) {
                $data['status'] = $request->status;
            }
            if ($request->has('priority')) {
                $data['priority'] = $request->priority;
            }
            if ($request->has('estimated_completion_date')) {
                $data['estimated_completion_date'] = $request->estimated_completion_date;
            }
            if ($request->has('notes')) {
                $data['notes'] = $request->notes;
            }

            $service->update($data);

            DB::commit();

            return response()->json([
                'success' => true,
                'data' => $service->load('user'),
                'message' => 'Service updated successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Service $service)
    {
        $user = $request->user();
        
        // Check if user can delete this service
        if ($user->role !== 'admin' && $service->user_id !== $user->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 403);
        }

        try {
            DB::beginTransaction();

            $service->delete();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Service deleted successfully'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Get service statistics
     */
    public function statistics(Request $request)
    {
        $user = $request->user();
        
        $query = Service::query();
        
        if ($user->role !== 'admin') {
            $query->where('user_id', $user->id);
        }

        $statistics = [
            'total_services' => $query->count(),
            'pending_services' => $query->where('status', 'pending')->count(),
            'in_progress_services' => $query->where('status', 'in_progress')->count(),
            'completed_services' => $query->where('status', 'completed')->count(),
            'cancelled_services' => $query->where('status', 'cancelled')->count(),
            'total_estimated_cost' => $query->sum('estimated_cost'),
        ];

        return response()->json([
            'success' => true,
            'data' => $statistics,
            'message' => 'Service statistics retrieved successfully'
        ]);
    }
}
