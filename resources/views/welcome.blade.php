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
            border: 5px solid black;
            border-radius: 5%
        }
        .image-border {
            border: 2px solid black;
            border-radius: 5%
        }
        .bottom-padding {
            padding-bottom: 50px;
        }
    </style>
</head>
<body>
    <div class="container mt-5 bottom-padding">
        <div class="row">
            <div class="col-md-6 offset-md-3">
                <h1 class="text-center">Optimize Image</h1>
                
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

                <form action="{{ route('image.store') }}" method="POST" enctype="multipart/form-data" class="mt-4">
                    @csrf
                    <div class="form-group">
                        <label for="image">Select Image:</label>
                        <input type="file" name="image" id="image" accept="image/*" class="form-control-file" onchange="previewImage(event)">
                    </div>
                    <div class="form-group">
                        <img height="50%" width="50%" id="preview" src="#" alt="Image Preview">
                    </div>

                    <div class="form-group">
                        <label for="height">Height</label>
                        <input type="number" id="height" name="height" class="form-control">
                    </div>
                    <div class="form-group">
                        <label for="width">Width</label>
                        <input type="number" id="width" name="width" class="form-control">
                    </div>

                    <div class="form-group">
                        <label>Output File Type:</label>
                        <div id="fileTypeOptions">
                            <!-- Radio buttons will be inserted here by JavaScript -->
                        </div>
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-primary btn-block">Optimize Image</button>
                    </div>
                    
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
                    
                    @elseif(session('base64Image'))
                        <div class="text-center">
                            <p>Base64 Image:</p>
                            <textarea class="form-control" rows="6" id="base64Code">{{ session('base64Image') }}</textarea>
                            <button class="btn btn-primary mt-2" onclick="copyToClipboard()">Copy Text</button>
                            <br><br>
                            <img src="data:image/png;base64,{{ session('base64Image') }}" alt="Base64 Image" class="img-fluid">
                        </div>
                    @else
                        <div class="text-center">
                            <img height="20%" width="20%" src="{{ asset('storage/images/blank.jpg') }}" alt="Uploaded Image">
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

                    const fileTypeOptions = document.getElementById('fileTypeOptions');
                    fileTypeOptions.innerHTML = ''; // Clear existing options
                    const fileType = event.target.files[0].type;
                    const originalType = fileType.split('/')[1];
                    
                    fileTypeOptions.innerHTML = `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="outputFileType" id="originalOption" value="original" checked>
                            <label class="form-check-label" for="originalOption">Original (${originalType.toUpperCase()})</label>
                        </div>`;

                    if (originalType === 'jpeg' || originalType === 'jpg') {
                        fileTypeOptions.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="pngOption" value="png">
                                <label class="form-check-label" for="pngOption">PNG</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="webpOption" value="webp">
                                <label class="form-check-label" for="webpOption">WebP</label>
                            </div>`;
                    } else if (originalType === 'png') {
                        fileTypeOptions.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="jpgOption" value="jpg">
                                <label class="form-check-label" for="jpgOption">JPG</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="webpOption" value="webp">
                                <label class="form-check-label" for="webpOption">WebP</label>
                            </div>`;
                    } else if (originalType === 'webp') {
                        fileTypeOptions.innerHTML += `
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="jpgOption" value="jpg">
                                <label class="form-check-label" for="jpgOption">JPG</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="outputFileType" id="pngOption" value="png">
                                <label class="form-check-label" for="pngOption">PNG</label>
                            </div>`;
                    }
                    fileTypeOptions.innerHTML += `
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="outputFileType" id="base64Option" value="base64">
                            <label class="form-check-label" for="base64Option">Base64</label>
                        </div>`;
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
                        const height = width / aspectRatio;
                        heightInput.value = height.toFixed(0);
                    } else {
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
                        const width = height / aspectRatio;
                        widthInput.value = width.toFixed(0);
                    } else {
                        const width = height * aspectRatio;
                        widthInput.value = width.toFixed(0);
                    }
                } else {
                    widthInput.value = '';
                }
            });
        });

        function copyToClipboard() {
            const textarea = document.getElementById('base64Code');
            textarea.select();
            textarea.setSelectionRange(0, 99999); // For mobile devices

            // Copy the text inside the text field
            navigator.clipboard.writeText(textarea.value);

            // Alert the copied text
            alert("Copied the text!");
            deleteSessionData();

        }

        function deleteSessionData() {
        fetch('{{ route("image.base64session") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
            },
            credentials: 'same-origin'
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Failed to delete session data');
            }
            // Optionally, you can handle the response here if needed
        })
        .catch(error => {
            console.error('Error:', error);
        });
    }
    window.onload = function() {
            // Clear session storage
            sessionStorage.clear();
        };
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
