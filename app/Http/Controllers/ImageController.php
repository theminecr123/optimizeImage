<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;

class ImageController extends Controller
{
    public function store(Request $request)
    {
        $optimizerChain = OptimizerChainFactory::create();

        $baseImagePath = storage_path('app/public/images/');
        $resizedImagePath = storage_path('app/public/images/resized/');

        $manager = new ImageManager(new Driver());

        // Validate the request
        $request->validate([
            'image' => 'required|image|mimes:jpeg,jpg,png,webp',
            'height' => 'nullable|integer',
            'width' => 'nullable|integer',
            'outputFileType' => 'required|in:original,jpg,png,webp,base64'
        ]);

        // Generate a unique name for the image
        $imageName = time().'.'.$request->image->getClientOriginalExtension();
        
        // Store the original image in the 'images' directory
        $originalImagePath = $request->image->storeAs('images', $imageName, 'public');
        $originalImageFullPath = $baseImagePath.$imageName;

        if ($request->has(['height', 'width']) && $request->height && $request->width) {
            $outputFileType = $request->outputFileType;

            // Check if outputFileType is 'original', use original extension
            if ($outputFileType == 'original') {
                $outputFileType = $request->image->getClientOriginalExtension();
            }

            $resizedImageName = 'resized_' . time() . '.' . $outputFileType;
            
            // Resize the image if height and width are provided
            $img = $manager->read($originalImageFullPath)->resize($request->width, $request->height);
            $resizedImageFullPath = $resizedImagePath . $resizedImageName;

            if (!file_exists($resizedImagePath)) {
                mkdir($resizedImagePath, 0755, true);
            }

            $img->save($resizedImageFullPath);

            // Optimize the resized image
            $optimizerChain->optimize($resizedImageFullPath);

            // Convert to Base64 if selected
            if ($outputFileType === 'base64') {
                $base64Image = base64_encode(file_get_contents($resizedImageFullPath));
                session()->forget(['resizeImage', 'image']);
                session(['base64Image' => $base64Image]);
            } else {
                session()->forget(['base64Image']);
                session(['resizeImage' => 'resized/' . $resizedImageName]);

            }
        }

        session(['image' => 'images/' . $imageName]);

        // Return a success response
        return back()->with('success', 'Image uploaded and manipulated successfully');
    }

    public function download()
    {
        $resizedImage = session('resizeImage');
        $originalImage = session('image');

        if ($resizedImage) {
            $imagePath = storage_path('app/public/images/' . $resizedImage);
        } elseif ($originalImage) {
            $imagePath = storage_path('app/public/' . $originalImage);
        } else {
            return back()->with('error', 'No image available for download');
        }

        // Clear the session
        session()->forget(['resizeImage', 'image']);

        return response()->download($imagePath);
    }

    
}
