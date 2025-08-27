<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $products = Product::with('category')->get();
        return response()->json([
            'success' => true,
            'data' => $products,
            'message' => 'Products retrieved successfully'
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'stock' => 'required|integer|min:0',
            'category_id' => 'required|exists:categories,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048', // Multiple images
            'sku' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'buy_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
        ]);

        $data = $request->all();

        // Handle multiple image uploads
        $imagePaths = [];
        
        if ($request->hasFile('images')) {
            $images = $request->file('images');
            
            // Handle both single file and multiple files
            if (!is_array($images)) {
                $images = [$images];
            }
            
            foreach ($images as $image) {
                if ($image && $image->isValid()) {
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    
                    // Store image in public/images/products directory
                    $imagePath = $image->storeAs('images/products', $imageName, 'public');
                    $imagePaths[] = $imagePath;
                }
            }
        } elseif ($request->has('images') && is_array($request->images)) {
            // Handle base64 image data array
            foreach ($request->images as $imageData) {
                if (is_string($imageData) && preg_match('/^data:image\/(\w+);base64,/', $imageData, $matches)) {
                    $imageType = $matches[1];
                    $imageData = substr($imageData, strpos($imageData, ',') + 1);
                    $imageData = base64_decode($imageData);
                    
                    if ($imageData !== false) {
                        $imageName = time() . '_' . Str::random(10) . '.' . $imageType;
                        $imagePath = 'images/products/' . $imageName;
                        
                        // Store the image
                        Storage::disk('public')->put($imagePath, $imageData);
                        $imagePaths[] = $imagePath;
                    }
                }
            }
        }

        // Store image paths as JSON in the database
        if (!empty($imagePaths)) {
            $data['image'] = json_encode($imagePaths);
        }

        $product = Product::create($data);

        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'message' => 'Product created successfully'
        ], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product)
    {
        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'message' => 'Product retrieved successfully'
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Product $product)
    {
    
        $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'stock' => 'sometimes|required|integer|min:0',
            'category_id' => 'sometimes|required|exists:categories,id',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'existing_images.*' => 'nullable|string',
            'removed_images.*' => 'nullable|string',
            'sku' => 'nullable|string|max:255',
            'brand' => 'nullable|string|max:255',
            'storage' => 'nullable|string|max:255',
            'color' => 'nullable|string|max:255',
            'condition' => 'nullable|string|max:255',
            'model_number' => 'nullable|string|max:255',
            'buy_price' => 'nullable|numeric|min:0',
            'sell_price' => 'nullable|numeric|min:0',
        ]);

        // Build data array using input() method
        $data = [];
        if ($request->input('name')) $data['name'] = $request->input('name');
        if ($request->input('description')) $data['description'] = $request->input('description');
        if ($request->input('stock')) $data['stock'] = $request->input('stock');
        if ($request->input('category_id')) $data['category_id'] = $request->input('category_id');
        if ($request->input('sku')) $data['sku'] = $request->input('sku');
        if ($request->input('brand')) $data['brand'] = $request->input('brand');
        if ($request->input('storage')) $data['storage'] = $request->input('storage');
        if ($request->input('color')) $data['color'] = $request->input('color');
        if ($request->input('condition')) $data['condition'] = $request->input('condition');
        if ($request->input('model_number')) $data['model_number'] = $request->input('model_number');
        if ($request->input('buy_price')) $data['buy_price'] = $request->input('buy_price');
        if ($request->input('sell_price')) $data['sell_price'] = $request->input('sell_price');
    
        // Get current images from the product (decode JSON string)
        $currentImages = [];
        if ($product->image) {
            try {
                $currentImages = json_decode($product->image, true) ?: [];
            } catch (Exception $e) {
                // If it's not JSON, treat as single image
                $currentImages = [$product->image];
            }
        }
    
        // Get images that should be preserved (from frontend)
        $existingImages = $request->input('existing_images', []);
        if (!is_array($existingImages)) {
            $existingImages = [$existingImages];
        }
    
        // Get images that should be removed (from frontend)
        $removedImages = $request->input('removed_images', []);
        if (!is_array($removedImages)) {
            $removedImages = [$removedImages];
        }
    
        // Remove deleted images from storage
        foreach ($removedImages as $removedImage) {
            if (!empty($removedImage)) {
                // Extract the file path from the full URL if needed
                $imagePath = $removedImage;
                if (strpos($removedImage, '/storage/') !== false) {
                    $imagePath = str_replace('/storage/', '', $removedImage);
                }
                
                // Remove from storage
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
        }
    
        // Start with images that should be preserved
        $finalImages = $existingImages;
    
        // Handle new uploaded images
        if ($request->hasFile('images')) {
            
            $images = $request->file('images');
            if (!is_array($images)) {
                $images = [$images];
            }
            
            $newImagePaths = [];
            foreach ($images as $image) {
                if ($image && $image->isValid()) {
                    $imageName = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
                    $imagePath = $image->storeAs('images/products', $imageName, 'public');
                    $newImagePaths[] = $imagePath;
                }
            }
            
            // Add new images to the preserved ones
            $finalImages = array_merge($finalImages, $newImagePaths);
        }
    
        // Update the image field
        if (!empty($finalImages)) {
            $data['image'] = json_encode($finalImages);
        } else {
            // If no images left, set to null or empty array
            $data['image'] = null;
        }
    
        $product->update($data);
    
        return response()->json([
            'success' => true,
            'data' => $product->load('category'),
            'message' => 'Product updated successfully'
        ]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product)
    {
        // Delete all images if they exist
        if ($product->image && is_array($product->image)) {
            foreach ($product->image as $imagePath) {
                if (Storage::disk('public')->exists($imagePath)) {
                    Storage::disk('public')->delete($imagePath);
                }
            }
        }

        $product->delete();

        return response()->json([
            'success' => true,
            'message' => 'Product deleted successfully'
        ]);
    }
}
