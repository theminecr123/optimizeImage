<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use App\Models\Image;
use Intervention\Image\Drivers\Gd\Driver;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function index()
    {
        $images = Image::paginate(5);
        return view('index', compact('images'));
    }

    public function store(Request $request)
    {
        ini_set('memory_limit', '1G');
        $optimizerChain = OptimizerChainFactory::create();
        $manager = new ImageManager(new Driver()); // Điều này làm giảm nguy cơ xảy ra lỗi nếu thư viện GD không được cài đặt đúng.

        // Validate the request
        $request->validate([
            'images.*' => 'required|image|mimes:jpeg,jpg,png,webp',
            'heights.*' => 'required|integer',
            'widths.*' => 'required|integer',
            'names.*' => 'required|string',
            'tags.*' => 'required|string',
            'globalFileType' => 'required|in:jpg,png,webp,base64',
            'globalCategory' => 'required|string'
        ],[
            'names.*.required' => 'Tên hình bắt buộc phải nhập',
            'heights.*.required' => 'Chiều cao bắt buộc phải nhập',
            'widths.*.required' => 'Chiều rộng bắt buộc phải nhập',
            'tags.*.required' => 'Tags sản phẩm bắt buộc phải nhập'
        ]);

        $images = $request->file('images');
        $globalCategory = $request->input('globalCategory');
        $globalFileType = $request->input('globalFileType');

        foreach ($images as $index => $image) {
            $extension = $globalFileType == 'base64' ? 'jpg' : $globalFileType;
            $imageName = time() . '_' . $index . '.' . $extension;
            $resizedImageFullPath = storage_path('app/public/images/resized/' . $imageName);

            $width = $request->input("widths.$index");
            $height = $request->input("heights.$index");

            if (!file_exists(storage_path('app/public/images/resized'))) {
                mkdir(storage_path('app/public/images/resized'), 0755, true);
            }

            try {
                $img = $manager->read($image->getRealPath())->resize($width, $height, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });

                // Điều chỉnh chất lượng hình ảnh để tối ưu hóa nén
                $img->save($resizedImageFullPath, 75); // Điều chỉnh chất lượng về 75%

                // Tối ưu hóa hình ảnh
                $optimizerChain->optimize($resizedImageFullPath);

                $thumbImageName = pathinfo($imageName, PATHINFO_FILENAME) . '_Thumb.' . $extension;
                $thumbImageFullPath = storage_path('app/public/images/thumbs/' . $thumbImageName);

                if (!file_exists(storage_path('app/public/images/thumbs'))) {
                    mkdir(storage_path('app/public/images/thumbs'), 0755, true);
                }

                $img->resize(600, 600, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                })->save($thumbImageFullPath, 75); // Điều chỉnh chất lượng về 75%

                // Tối ưu hóa hình ảnh thu nhỏ
                $optimizerChain->optimize($thumbImageFullPath);

                $imageModel = new Image([
                    'image' => $imageName,
                    'name' => $request->input("names.$index"),
                    'tags' => $request->input("tags.$index"),
                    'category' => $globalCategory,
                    'isOn' => 1,
                    'isSelected' => 1,
                    'extension' => $extension
                ]);

                $imageModel->save();

                unset($img); // Giải phóng bộ nhớ
            } catch (\Exception $e) {
                Log::error('Image processing failed: ' . $e->getMessage());
                return response()->json(['error' => 'Image processing failed'], 500);
            }
        }

        return response()->json(['success' => 'Images Uploaded Successfully']);
    }

    public function deleteBase64Session(Request $request)
    {
        session()->forget('base64Images');
        return redirect()->back()->with('success', 'Base64 images deleted from session');
    }

    public function download($id)
    {
        $image = Image::findOrFail($id);
        $imagePath = storage_path('app/public/images/resized/' . $image->image);

        if (!file_exists($imagePath)) {
            return back()->with('error', 'Image not found.');
        }

        return response()->download($imagePath, $image->image, [
            'Content-Disposition' => 'attachment; filename="' . $image->image . '"'
        ]);
    }
}
?>
