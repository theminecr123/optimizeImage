<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Optimize Image</title>
    <style>
        #preview {
            display: none;
            max-width: 100%;
            height: auto;
            border: 5px solid black; /* Add border to preview image */
            border-radius: 5%

        }
        .image-border {
            border: 2px solid black; /* Add border to other images */
            border-radius: 5%
        }
        .bottom-padding {
            padding-bottom: 50px; /* Add padding to the bottom of the page */
        }
        
    </style>
</head>
<body>
    <div class="container mt-5 bottom-padding">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <h1 class="text-center">Optimize Image</h1>
                
                <!-- Display success message and uploaded image -->
                @if(session('success'))
                    @php
                        $originalImage = session('image');
                        $originalImagePath = storage_path('app/public/' . $originalImage);
                        $originalImageSize = filesize($originalImagePath);
                    @endphp
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                    <div class="text-center">
                        <img height="50%" width="50%" src="{{ asset('storage/' . session('image')) }}" alt="Uploaded Image" class="img-fluid image-border">
                        <p>Original Image Size: {{ round($originalImageSize / 1024, 2) }} KB</p>

                    </div>
                @endif

                <!-- Form for image upload -->
                <form action="{{ route('image.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <div class="form-group">
                        <label for="image">Select Image:</label>
                        <input type="file" name="image" id="image" accept="image/*" class="form-control-file" onchange="previewImage(event)">
                    </div>
                    <div class="form-group">
                        <img height="50%" width="50%" id="preview" src="#" alt="Image Preview">
                    </div>

                    <!-- Height and Width input fields -->
                    <div class="form-group">
                        <label for="height">Height</label>
                        <input type="number" id="height" name="height" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="width">Width</label>
                        <input type="number" id="width" name="width" class="form-control">
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Optimize Image</button>
                    </div>
                    
                    <!-- Download and Preview Image -->
                    @if(session('resizeImage'))
                        <div class="text-center">
                            @php
                                $resizeImage = session('resizeImage');
                                $originalImage = session('image');
                            @endphp
                            @if($resizeImage)
                                @php
                                    $resizeImagePath = storage_path('app/public/images/' . $resizeImage);
                                    $resizeImageSize = filesize($resizeImagePath);
                                @endphp
                                <img width="50%" height="50%" src="{{ asset('storage/images/' . $resizeImage) }}" alt="Uploaded Image" class="img-fluid image-border">

                                <p>Resized Image Size: {{ round($resizeImageSize / 1024, 2) }} KB</p>

                            @endif
                            <br><br>
                            <a href="{{ route('image.download') }}" class="btn btn-success">Download Image</a>
                        </div>
                    @else
                        <div class="text-center">
                            <img height="20%" width="20%" src="{{ asset('storage/images/blank.jpg') }}" alt="Uploaded Image" >
                        </div>
                    @endif
                </form>
            </div>
        </div>
    </div>

    <script>
        function previewImage(event) {
            var reader = new FileReader();
            reader.onload = function() {
                var output = document.getElementById('preview');
                var image = new Image();
                image.onload = function() {
                    document.getElementById('width').value = image.width;
                    document.getElementById('height').value = image.height;
                };
                image.src = reader.result;
                output.src = reader.result;
                output.style.display = 'block';
            }
            reader.readAsDataURL(event.target.files[0]);
        }

        document.addEventListener('DOMContentLoaded', () => {
        const widthInput = document.getElementById('width');
        const heightInput = document.getElementById('height');
        const aspectRatio = 3 / 2;

        widthInput.addEventListener('input', () => {
            const width = parseFloat(widthInput.value);
            if (!isNaN(width)) {
                const height = heightInput.value ? parseFloat(heightInput.value) : null;
                if (height && width / height > aspectRatio) {
                    // Landscape orientation
                    const height = width / aspectRatio;
                    heightInput.value = height.toFixed(0);
                } else {
                    // Portrait orientation or initial value
                    const height = width * aspectRatio;
                    heightInput.value = height.toFixed(0);
                }
            } else {
                heightInput.value = '';
            }
        });

        heightInput.addEventListener('input', () => {
            const height = parseFloat(heightInput.value);
            if (!isNaN(height)) {
                const width = widthInput.value ? parseFloat(widthInput.value) : null;
                if (width && height / width > aspectRatio) {
                    // Portrait orientation
                    const width = height / aspectRatio;
                    widthInput.value = width.toFixed(0);
                } else {
                    // Landscape orientation or initial value
                    const width = height * aspectRatio;
                    widthInput.value = width.toFixed(0);
                }
            } else {
                widthInput.value = '';
            }
        });
    });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
