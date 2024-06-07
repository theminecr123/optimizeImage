<?php
namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use App\Models\Image;

class ImageController extends Controller
{
    public function index()
    {
        // Lấy danh sách hình ảnh từ cơ sở dữ liệu
        $images = Image::paginate(5); // Paginate with 5 items per page
        return view('index', compact('images'));
    }

    public function store(Request $request)
{
    ini_set('memory_limit', '1G');
    $optimizerChain = OptimizerChainFactory::create();
    $manager = new ImageManager(new Driver());

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
        'name.required' => 'Tên hình bắt buộc phải nhập',
        'heights.required' => 'Chiều cao bắt buộc phải nhập',
        'widths.required' => 'Chiều rộng phẩm bắt buộc phải nhập',
        'tags.required' => 'Tags sản phẩm bắt buộc phải nhập'

    ]);

    $images = $request->file('images');
    $imageData = [];
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

        // Compress and save the resized image
        if ($width && $height) {
            $img = $manager->read($image->getRealPath())->resize($width, $height);
        } else {
            $img = $manager->read($image->getRealPath());
        }

        $img->save($resizedImageFullPath);
        $optimizerChain->optimize($resizedImageFullPath);

        // Create thumbnail with the same format and resize to 600x600
        $thumbImageName = pathinfo($imageName, PATHINFO_FILENAME) . '_Thumb.' . $extension;
        $thumbImageFullPath = storage_path('app/public/images/thumbs/' . $thumbImageName);

        if (!file_exists(storage_path('app/public/images/thumbs'))) {
            mkdir(storage_path('app/public/images/thumbs'), 0755, true);
        }

        $img->resize(600, 600)->save($thumbImageFullPath);
        $optimizerChain->optimize($thumbImageFullPath);

        // Save image data to the database
        $imageModel = new Image([
            'image' => $imageName, // Only the filename
            'name' => $request->input("names.$index"),
            'tags' => $request->input("tags.$index"),
            'category' => $globalCategory,
            'isOn' => 1,
            'isSelected' => 1,
            'extension' => $extension // Save the extension
        ]);

        $imageModel->save();

        // Giải phóng bộ nhớ sau mỗi lần xử lý
        unset($img);
    }

    return redirect()->route('image.index')->with('success', 'Images Uploaded Successfully');
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
            return back()->with('error', 'Image not found');
        }

        return response()->download($imagePath);
    }
}
