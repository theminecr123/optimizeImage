<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <title>Image Management</title>
    <style>
        .image-border {
            border: 2px solid black;
            border-radius: 5%;
        }
        .bottom-padding {
            padding-bottom: 50px;
        }
        .modal-img {
            max-width: 100%;
            max-height: 100%;
        }

        .image-preview {
            display: flex;
            flex-direction: column;
            margin-bottom: 15px;
            position: relative;
        }
        .image-preview img {
            width: 200px; /* Fixed width */
            height: 200px; /* Adjusted height to maintain 3:2 aspect ratio */
            object-fit: cover; /* Ensure the image covers the area */
        }

        .progress-overlay {
            position: absolute;
            top: 0;
            border-radius: 5%;
            width: 200px;
            height: 200px;
            background: rgba(0, 0, 0, 0.5);
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            color: white;
            font-weight: bold;
            text-shadow: 1px 1px 2px black;
            opacity: 0;
            transition: opacity 0.3s;
        }

        .progress {
            width: 80%;
            height: 20px;
            margin-top: 10px;
        }

        .image-preview:hover .progress-overlay {
            opacity: 1;
        }

        .delete-button {
            position: absolute;
            top: 5px;
            right: 5px;
            background: red;
            color: white;
            border: none;
            border-radius: 50%;
            width: 25px;
            height: 25px;
            text-align: center;
            cursor: pointer;
            font-size: 16px;
            line-height: 25px;
        }

        .error-message {
            color: red;
            font-size: 12px;
            margin-top: 5px;
        }

        .drop-zone {
            border: 2px dashed #007bff;
            padding: 20px;
            text-align: center;
            cursor: pointer;
        }
        .drop-zone.dragover {
            background-color: #f1f1f1;
        }
    </style>
</head>
<body>
    <div class="container mt-5 bottom-padding">
        <div class="row">
            <div class="col-md-12">
                <h1 class="text-center">Image Management</h1>
                
                @if(session('success'))
                    <div class="alert alert-success">
                        {{ session('success') }}
                    </div>
                @endif

                <button type="button" class="btn btn-primary btn-block" data-toggle="modal" data-target="#optimizeImageModal">
                    Optimize Images
                </button>

                <h2 class="mt-5">Image List</h2>
                <div class="row">
                    @foreach($images as $image)
                        @php
                            $extension = pathinfo($image->image, PATHINFO_EXTENSION);
                            $thumbPath = 'storage/images/thumbs/' . pathinfo($image->image, PATHINFO_FILENAME) . '_Thumb.' . $extension;
                            $fullPath = 'storage/images/resized/' . $image->image;
                        @endphp
                        <div class="mb-4" style="margin: 23px">
                            <div class="card" style="width: 181px;">
                                <a href="#" data-toggle="modal" data-target="#imageModal{{ $image->id }}">
                                    <img src="{{ asset($thumbPath) }}" alt="Thumbnail" class="card-img-top image-border">
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">{{ $image->name }}</h5>
                                    <p class="card-text">{{ $image->tags }}</p>
                                    <p class="card-text"><small class="text-muted">{{ $image->category }}</small></p>
                                    <a href="{{ route('image.download', $image->id) }}" class="btn btn-success btn-sm">Download</a>
                                </div>
                            </div>
                        </div>

                        <!-- Modal for full image -->
                        <div class="modal fade" id="imageModal{{ $image->id }}" tabindex="-1" role="dialog" aria-labelledby="imageModalLabel{{ $image->id }}" aria-hidden="true">
                            <div class="modal-dialog modal-lg" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="imageModalLabel{{ $image->id }}">{{ $image->name }}</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <img src="{{ asset('storage/images/resized/' . $image->image) }}" alt="Full Image" class="modal-img">
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Close</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                
                <!-- Pagination links -->
                <div class="d-flex justify-content-center">
                    {{ $images->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>

    <!-- Modal for image upload -->
    <div class="modal fade" id="optimizeImageModal" tabindex="-1" role="dialog" aria-labelledby="optimizeImageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="optimizeImageModalLabel">Optimize Image</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <form id="uploadForm" action="{{ route('image.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="images">Select Images:</label>
                            <div id="drop-zone" class="drop-zone">
                                Drag & Drop your images here or click to select
                                <input type="file" name="images[]" id="images" accept="image/*" class="form-control-file" multiple onchange="previewImages(event)" style="display:none;">
                            </div>
                            <div class="error-message" id="error-images"></div>
                        </div>
                        <div id="image-options-container" class="row"></div>

                        <div class="form-group">
                            <label for="globalCategory">Category:</label>
                            <select name="globalCategory" id="globalCategory" class="form-control">
                                <option value="Category 1">Category 1</option>
                                <option value="Category 2">Category 2</option>
                                <option value="Category 3">Category 3</option>
                            </select>
                            <div class="error-message" id="error-globalCategory"></div>
                        </div>

                        <div class="form-group">
                            <label for="globalFileType">Output File Type:</label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="globalFileType" value="jpg" checked>
                                <label class="form-check-label">JPG</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="globalFileType" value="png">
                                <label class="form-check-label">PNG</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="globalFileType" value="webp">
                                <label class="form-check-label">WebP</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="globalFileType" value="base64">
                                <label class="form-check-label">Base64</label>
                            </div>
                            <div class="error-message" id="error-globalFileType"></div>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary btn-block" id="uploadButton">Optimize Images</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        let filesArray = [];

        document.getElementById('drop-zone').addEventListener('click', function() {
            document.getElementById('images').click();
        });

        document.getElementById('drop-zone').addEventListener('dragover', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.add('dragover');
        });

        document.getElementById('drop-zone').addEventListener('dragleave', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
        });

        document.getElementById('drop-zone').addEventListener('drop', function(e) {
            e.preventDefault();
            e.stopPropagation();
            this.classList.remove('dragover');
            const files = e.dataTransfer.files;
            document.getElementById('images').files = files;
            previewImages({ target: { files: files } });
        });

        function previewImages(event) {
            const files = event.target.files;
            const container = document.getElementById('image-options-container');
            container.innerHTML = ''; // Clear existing options
            filesArray = Array.from(files); // Initialize filesArray

            for (let i = 0; i < files.length; i++) {
                const reader = new FileReader();
                reader.onload = (function(file, index) {
                    return function(e) {
                        const colDiv = document.createElement('div');
                        colDiv.className = 'col-md-4 image-preview';
                        colDiv.id = `preview-${index}`;

                        const img = document.createElement('img');
                        img.onload = function() {
                            const widthInput = document.getElementById(`width-${index}`);
                            const heightInput = document.getElementById(`height-${index}`);
                            widthInput.value = img.naturalWidth;
                            heightInput.value = img.naturalHeight;
                        }
                        img.src = e.target.result;
                        img.className = 'img-fluid image-border';
                        colDiv.appendChild(img);

                        // Add a delete button
                        const deleteButton = document.createElement('button');
                        deleteButton.className = 'delete-button';
                        deleteButton.textContent = '×';
                        deleteButton.addEventListener('click', function() {
                            // Remove the image preview
                            document.getElementById(`preview-${index}`).remove();
                            // Remove the file from filesArray
                            filesArray.splice(index, 1);
                        });
                        colDiv.appendChild(deleteButton);

                        const formDiv = document.createElement('div');
                        formDiv.className = 'form-group';

                        const nameLabel = document.createElement('label');
                        nameLabel.textContent = 'Name';
                        formDiv.appendChild(nameLabel);

                        const nameInput = document.createElement('input');
                        nameInput.type = 'text';
                        nameInput.name = `names[${index}]`;
                        nameInput.id = `name-${index}`;
                        nameInput.className = 'form-control';
                        formDiv.appendChild(nameInput);
                        formDiv.appendChild(document.createElement('div')).className = 'error-message';
                        formDiv.lastChild.id = `error-name-${index}`;

                        const widthLabel = document.createElement('label');
                        widthLabel.textContent = 'Width';
                        formDiv.appendChild(widthLabel);

                        const widthInput = document.createElement('input');
                        widthInput.type = 'number';
                        widthInput.name = `widths[${index}]`;
                        widthInput.id = `width-${index}`;
                        widthInput.className = 'form-control';
                        formDiv.appendChild(widthInput);
                        formDiv.appendChild(document.createElement('div')).className = 'error-message';
                        formDiv.lastChild.id = `error-width-${index}`;

                        const heightLabel = document.createElement('label');
                        heightLabel.textContent = 'Height';
                        formDiv.appendChild(heightLabel);

                        const heightInput = document.createElement('input');
                        heightInput.type = 'number';
                        heightInput.name = `heights[${index}]`;
                        heightInput.id = `height-${index}`;
                        heightInput.className = 'form-control';
                        formDiv.appendChild(heightInput);
                        formDiv.appendChild(document.createElement('div')).className = 'error-message';
                        formDiv.lastChild.id = `error-height-${index}`;

                        const tagsLabel = document.createElement('label');
                        tagsLabel.textContent = 'Tags';
                        formDiv.appendChild(tagsLabel);

                        const tagsInput = document.createElement('input');
                        tagsInput.type = 'text';
                        tagsInput.name = `tags[${index}]`;
                        tagsInput.id = `tags-${index}`;
                        tagsInput.className = 'form-control';
                        formDiv.appendChild(tagsInput);
                        formDiv.appendChild(document.createElement('div')).className = 'error-message';
                        formDiv.lastChild.id = `error-tags-${index}`;

                        colDiv.appendChild(formDiv);

                        // Add progress overlay for each image
                        const progressOverlay = document.createElement('div');
                        progressOverlay.className = 'progress-overlay';
                        progressOverlay.id = `progress-overlay-${index}`;
                        progressOverlay.innerHTML = `
                            <div>Compressing</div>
                            <div class="progress">
                                <div class="progress-bar" id="progress-bar-${index}" role="progressbar" style="width: 0%;" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                            <div id="progress-text-${index}">0%</div>
                        `;
                        colDiv.appendChild(progressOverlay);

                        container.appendChild(colDiv);

                        // Update width and height inputs based on aspect ratio
                        const aspectRatio = 3 / 2;

                        widthInput.addEventListener('input', () => {
                            const width = parseFloat(widthInput.value);
                            if (!isNaN(width)) {
                                const newHeight = width / aspectRatio;
                                heightInput.value = newHeight.toFixed(0);
                            } else {
                                heightInput.value = '';
                            }
                        });

                        heightInput.addEventListener('input', () => {
                            const height = parseFloat(heightInput.value);
                            if (!isNaN(height)) {
                                const newWidth = height * aspectRatio;
                                widthInput.value = newWidth.toFixed(0);
                            } else {
                                widthInput.value = '';
                            }
                        });
                    };
                })(files[i], i);
                reader.readAsDataURL(files[i]);
            }
        }

        // Handle form submission with AJAX to show upload progress and validation errors
        document.getElementById('uploadForm').addEventListener('submit', function(e) {
            e.preventDefault();
            console.log("Form submission started");

            const form = e.target;
            const formData = new FormData();

            // Add all files from filesArray to formData
            filesArray.forEach((file, index) => {
                formData.append('images[]', file);
            });

            // Append other form data
            const otherFormData = new FormData(form);
            for (let pair of otherFormData.entries()) {
                if (pair[0] !== 'images[]') {
                    formData.append(pair[0], pair[1]);
                }
            }

            const files = filesArray;

            // Perform client-side validation
            let valid = true;

            filesArray.forEach((file, index) => {
                const nameInput = document.getElementById(`name-${index}`);
                const widthInput = document.getElementById(`width-${index}`);
                const heightInput = document.getElementById(`height-${index}`);
                const tagsInput = document.getElementById(`tags-${index}`);

                if (!nameInput.value.trim()) {
                    valid = false;
                    document.getElementById(`error-name-${index}`).textContent = 'Tên hình bắt buộc phải nhập';
                } else {
                    document.getElementById(`error-name-${index}`).textContent = '';
                }

                if (!widthInput.value.trim()) {
                    valid = false;
                    document.getElementById(`error-width-${index}`).textContent = 'Chiều rộng phẩm bắt buộc phải nhập';
                } else {
                    document.getElementById(`error-width-${index}`).textContent = '';
                }

                if (!heightInput.value.trim()) {
                    valid = false;
                    document.getElementById(`error-height-${index}`).textContent = 'Chiều cao bắt buộc phải nhập';
                } else {
                    document.getElementById(`error-height-${index}`).textContent = '';
                }

                if (!tagsInput.value.trim()) {
                    valid = false;
                    document.getElementById(`error-tags-${index}`).textContent = 'Tags sản phẩm bắt buộc phải nhập';
                } else {
                    document.getElementById(`error-tags-${index}`).textContent = '';
                }
            });

            if (!valid) {
                return;
            }

            // Disable upload button
            const uploadButton = document.getElementById('uploadButton');
            uploadButton.disabled = true;
            uploadButton.textContent = 'Uploading...';

            // Loop through each file and upload individually to manage progress bars
            for (let i = 0; i < files.length; i++) {
                (function(index) {
                    const fileData = new FormData();
                    fileData.append('images[]', files[index]);

                    // Append other form data
                    for (let pair of formData.entries()) {
                        if (pair[0] !== 'images[]') {
                            fileData.append(pair[0], pair[1]);
                        }
                    }

                    const xhr = new XMLHttpRequest();
                    xhr.open('POST', form.action, true);
                    xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');

                    xhr.upload.addEventListener('progress', function(e) {
                        if (e.lengthComputable) {
                            const percentComplete = (e.loaded / e.total) * 100;
                            console.log(`File ${index}: ${percentComplete}% uploaded`);
                            // Select the correct progress bar and text for the current file
                            const progressBar = document.getElementById(`progress-bar-${index}`);
                            const progressText = document.getElementById(`progress-text-${index}`);
                            if (progressBar) {
                                progressBar.style.width = percentComplete + '%';
                                progressBar.setAttribute('aria-valuenow', percentComplete);
                            }
                            if (progressText) {
                                progressText.textContent = Math.round(percentComplete) + '%';
                            }
                        }
                    });

                    xhr.onreadystatechange = function() {
                        if (xhr.readyState === 4) {
                            if (xhr.status === 200) {
                                console.log(`Upload complete for file ${index}`);
                                if (index === files.length - 1) {
                                    location.reload(); // Reload the page after all uploads are complete
                                }
                            } else {
                                console.error(`Upload failed for file ${index}`, xhr.status, xhr.statusText);
                                uploadButton.disabled = false;
                                uploadButton.textContent = 'Optimize Images';
                            }
                        }
                    };

                    xhr.send(fileData);
                })(i);
            }
        });
    </script>

    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.5.4/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
</body>
</html>
