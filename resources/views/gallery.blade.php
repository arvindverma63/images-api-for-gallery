<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        body {
            font-family: 'Roboto', sans-serif;
            margin: 0;
            background: #f9fafb;
            overscroll-behavior-y: none;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 0;
        }

        @media (min-width: 768px) {
            .gallery-grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .gallery-grid {
                grid-template-columns: repeat(4, 1fr);
            }
        }

        .card-img {
            width: 100%;
            height: 200px;
            object-fit: cover;
            display: block;
            border: none;
        }

        .fullscreen-dialog {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.95);
            z-index: 1000;
            display: none;
            overscroll-behavior: none;
        }

        .fullscreen-dialog.active {
            display: flex;
        }

        .dialog-content {
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            touch-action: none;
        }

        .fullscreen-img {
            width: 100%;
            max-height: 100vh;
            object-fit: contain;
            user-select: none;
            -webkit-user-drag: none;
        }

        .nav-button {
            color: #fff;
            font-size: 2.5rem;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            text-decoration: none;
            padding: 0.5rem;
            z-index: 1001;
            opacity: 0.7;
        }

        .nav-button:hover {
            opacity: 1;
        }

        .nav-button.left {
            left: 0.5rem;
        }

        .nav-button.right {
            right: 0.5rem;
        }

        .close-button {
            color: #fff;
            font-size: 2rem;
            position: absolute;
            top: 0.5rem;
            right: 0.5rem;
            text-decoration: none;
            z-index: 1001;
            opacity: 0.7;
        }

        .close-button:hover {
            opacity: 1;
        }

        .spinner {
            border: 4px solid rgba(255, 255, 255, 0.3);
            border-top: 4px solid #3b82f6;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 1s linear infinite;
            margin: 20px auto;
            display: none;
        }

        @keyframes spin {
            0% {
                transform: rotate(0deg);
            }

            100% {
                transform: rotate(360deg);
            }
        }

        @media (max-width: 639px) {
            .container {
                padding: 0;
            }

            .card-img {
                height: 180px;
            }

            .nav-button {
                font-size: 2rem;
            }

            .close-button {
                font-size: 1.5rem;
            }
        }

        .card.hidden {
            display: none;
        }

        .error-message {
            color: #e3342f;
            text-align: center;
            margin-top: 1rem;
            display: none;
        }
    </style>
</head>

<body>
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4">Image Gallery</h1>
        <div class="flex flex-col sm:flex-row items-center mb-4 gap-2">
            <div class="flex w-full sm:w-auto items-center">
                <input type="text" id="search-input" value="{{ old('search', $search ?? '') }}"
                    class="border border-gray-300 p-2 rounded-l-md w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Search by title or description">
                <button id="search-button"
                    class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Search</button>
            </div>
            <div class="flex w-full sm:w-auto items-center">
                <select id="type-select"
                    class="border border-gray-300 p-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" {{ old('type', $type ?? '') === '' ? 'selected' : '' }}>All Types</option>
                    <option value="jpg" {{ old('type', $type ?? '') === 'jpg' ? 'selected' : '' }}>jpg</option>
                    <option value="jpeg" {{ old('type', $type ?? '') === 'jpeg' ? 'selected' : '' }}>jpeg</option>
                    <option value="png" {{ old('type', $type ?? '') === 'png' ? 'selected' : '' }}>png</option>
                    <option value="gif" {{ old('type', $type ?? '') === 'gif' ? 'selected' : '' }}>gif</option>
                </select>
                <button id="filter-button"
                    class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Filter</button>
            </div>
            <div class="flex w-full sm:w-auto items-center">
                <input type="number" id="per-page-input" value="{{ old('per_page', $perPage ?? 10) }}"
                    class="border border-gray-300 p-2 rounded-l-md w-24 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Per Page" min="1" max="100">
                <button id="per-page-button"
                    class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Apply</button>
            </div>
        </div>

        <div id="spinner" class="spinner"></div>
        <div id="error-message" class="error-message">Failed to load more images. Please try again.</div>
        <div id="gallery-grid" class="gallery-grid">
            @forelse ($images as $index => $image)
                <div class="card" data-image-url="{{ $image->proxy_url }}">
                    <a href="javascript:void(0)" class="image-link" data-fullscreen-id="fullscreen-{{ $index }}">
                        <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}" class="card-img"
                            onerror="this.closest('.card').classList.add('hidden')">
                    </a>
                </div>
                <div id="fullscreen-{{ $index }}" class="fullscreen-dialog" data-index="{{ $index }}">
                    <div class="dialog-content">
                        <a href="javascript:void(0)" class="close-button material-icons"
                            onclick="closeFullscreen();">close</a>
                        @if ($index > 0)
                            <a href="javascript:void(0)" class="nav-button left material-icons"
                                data-fullscreen-id="fullscreen-{{ $index - 1 }}">chevron_left</a>
                        @endif
                        <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}" class="fullscreen-img"
                            onerror="this.closest('.fullscreen-dialog').style.display='none';">
                        @if ($index < $images->count() - 1)
                            <a href="javascript:void(0)" class="nav-button right material-icons"
                                data-fullscreen-id="fullscreen-{{ $index + 1 }}">chevron_right</a>
                        @endif
                    </div>
                </div>
            @empty
                <p id="no-images" class="text-center text-gray-600 col-span-2">No images found.</p>
            @endforelse
        </div>

        @if ($images->hasMorePages())
            <div id="view-more" class="text-center mt-6">
                <button id="view-more-button" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600"
                    data-page="{{ $page + 1 }}" data-search="{{ $search ?? '' }}" data-type="{{ $type ?? '' }}"
                    data-per-page="{{ $perPage ?? 10 }}">View More</button>
            </div>
        @endif
    </div>

    <script>
        // Track loaded image URLs to prevent duplicates
        const loadedImageUrls = new Set(
            Array.from(document.querySelectorAll('.card')).map(card => card.getAttribute('data-image-url')).filter(
                url => url)
        );

        // Show/hide spinner
        function toggleSpinner(show) {
            const spinner = document.getElementById('spinner');
            spinner.style.display = show ? 'block' : 'none';
        }

        // Show/hide error message
        function toggleErrorMessage(show, message = 'Failed to load more images. Please try again.') {
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = message;
            errorMessage.style.display = show ? 'block' : 'none';
        }

        // Open fullscreen dialog
        function openFullscreen(id) {
            document.querySelectorAll('.fullscreen-dialog').forEach(dialog => {
                dialog.classList.remove('active');
            });
            const dialog = document.getElementById(id);
            if (dialog) {
                dialog.classList.add('active');
            }
        }

        // Close fullscreen dialog
        function closeFullscreen() {
            document.querySelectorAll('.fullscreen-dialog').forEach(dialog => {
                dialog.classList.remove('active');
            });
        }

        // Helper function to fetch images via AJAX
        async function fetchImages(params, append = false) {
            const galleryGrid = document.getElementById('gallery-grid');
            if (!galleryGrid) {
                console.error('Gallery grid element not found');
                toggleErrorMessage(true, 'Gallery grid element not found.');
                return;
            }

            toggleSpinner(true);
            toggleErrorMessage(false);
            const url = new URL('{{ route('gallery.index') }}');
            url.search = new URLSearchParams(params).toString();
            console.log('Fetching images with params:', params, 'URL:', url.toString());

            // Store current scroll position
            const scrollY = window.scrollY;

            try {
                const response = await fetch(url, {
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });

                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }

                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    const text = await response.text();
                    console.error('Non-JSON response received:', text);
                    throw new Error('Expected JSON response, received HTML or other content');
                }

                const data = await response.json();
                console.log('Received data:', data);

                const viewMore = document.getElementById('view-more') || document.createElement('div');
                if (!viewMore.id) {
                    viewMore.id = 'view-more';
                    viewMore.className = 'text-center mt-6';
                    document.querySelector('.container').appendChild(viewMore);
                }

                // Clear grid if not appending
                if (!append) {
                    galleryGrid.innerHTML = '';
                    loadedImageUrls.clear();
                }

                // Render images, avoiding duplicates
                let imagesAdded = 0;
                if (data.images && data.images.length > 0) {
                    // Track the highest index already in the DOM
                    const existingIndices = Array.from(document.querySelectorAll('.fullscreen-dialog')).map(dialog =>
                        parseInt(dialog.getAttribute('data-index')) || 0
                    );
                    const maxIndex = existingIndices.length > 0 ? Math.max(...existingIndices) : -1;

                    data.images.forEach((image, index) => {
                        // Skip if image URL is already loaded
                        if (loadedImageUrls.has(image.proxy_url)) {
                            console.log('Skipping duplicate image:', image.proxy_url);
                            return;
                        }

                        // Calculate global index for new images
                        const globalIndex = append ? maxIndex + 1 + index : index;
                        loadedImageUrls.add(image.proxy_url);
                        imagesAdded++;

                        const card = document.createElement('div');
                        card.className = 'card';
                        card.setAttribute('data-image-url', image.proxy_url);
                        card.innerHTML = `
                            <a href="javascript:void(0)" class="image-link" data-fullscreen-id="fullscreen-${globalIndex}">
                                <img src="${image.proxy_url}" alt="${image.title || 'Image'}" class="card-img" onerror="this.closest('.card').classList.add('hidden')">
                            </a>
                            <div id="fullscreen-${globalIndex}" class="fullscreen-dialog" data-index="${globalIndex}">
                                <div class="dialog-content">
                                    <a href="javascript:void(0)" class="close-button material-icons" onclick="closeFullscreen();">close</a>
                                    ${globalIndex > 0 ? `<a href="javascript:void(0)" class="nav-button left material-icons" data-fullscreen-id="fullscreen-${globalIndex - 1}">chevron_left</a>` : ''}
                                    <img src="${image.proxy_url}" alt="${image.title || 'Image'}" class="fullscreen-img" onerror="this.closest('.fullscreen-dialog').style.display='none';">
                                    ${globalIndex < data.total - 1 ? `<a href="javascript:void(0)" class="nav-button right material-icons" data-fullscreen-id="fullscreen-${globalIndex + 1}">chevron_right</a>` : ''}
                                </div>
                            </div>
                        `;
                        galleryGrid.appendChild(card);
                    });

                    // Update View More button
                    if (data.hasMorePages) {
                        viewMore.innerHTML = `
                            <button id="view-more-button" class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600"
                                data-page="${data.page + 1}"
                                data-search="${params.search || ''}"
                                data-type="${params.type || ''}"
                                data-per-page="${params.per_page || 10}">View More</button>
                        `;
                    } else {
                        viewMore.innerHTML = '';
                    }
                } else {
                    console.log('No images received in response');
                    if (append) {
                        toggleErrorMessage(true, 'No more images to load.');
                        viewMore.innerHTML = '';
                    } else {
                        galleryGrid.innerHTML =
                            '<p id="no-images" class="text-center text-gray-600 col-span-2">No images found.</p>';
                        viewMore.innerHTML = '';
                    }
                }

                console.log(`Added ${imagesAdded} new images`);

                // Restore scroll position
                if (append) {
                    window.scrollTo({
                        top: scrollY,
                        behavior: 'instant'
                    });
                }

                // Re-attach event listeners
                attachImageListeners();
                attachSwipeListeners();
            } catch (error) {
                console.error('Error fetching images:', error);
                toggleErrorMessage(true, 'Error loading images. Please try again.');
                if (!append) {
                    galleryGrid.innerHTML =
                        '<p id="no-images" class="text-center text-gray-600 col-span-2">Error loading images. Please try again.</p>';
                }
                viewMore.innerHTML = '';
            } finally {
                toggleSpinner(false);
            }
        }

        // Attach event listeners for image clicks and navigation
        function attachImageListeners() {
            document.querySelectorAll('.image-link').forEach(link => {
                link.removeEventListener('click', handleImageClick); // Prevent duplicate listeners
                link.addEventListener('click', handleImageClick);
            });
            document.querySelectorAll('.nav-button').forEach(button => {
                button.removeEventListener('click', handleNavClick); // Prevent duplicate listeners
                button.addEventListener('click', handleNavClick);
            });
        }

        function handleImageClick() {
            const fullscreenId = this.getAttribute('data-fullscreen-id');
            openFullscreen(fullscreenId);
        }

        function handleNavClick() {
            const fullscreenId = this.getAttribute('data-fullscreen-id');
            openFullscreen(fullscreenId);
        }

        // Swipe gesture handling
        function attachSwipeListeners() {
            document.querySelectorAll('.fullscreen-dialog').forEach(dialog => {
                const content = dialog.querySelector('.dialog-content');
                let startX, startY, isSwiping = false;

                content.addEventListener('touchstart', e => {
                    startX = e.touches[0].clientX;
                    startY = e.touches[0].clientY;
                    isSwiping = true;
                });

                content.addEventListener('touchmove', e => {
                    if (!isSwiping) return;
                    const currentX = e.touches[0].clientX;
                    const currentY = e.touches[0].clientY;
                    const diffX = startX - currentX;
                    const diffY = startY - currentY;

                    if (Math.abs(diffY) > Math.abs(diffX)) {
                        isSwiping = false;
                        return;
                    }

                    e.preventDefault();
                });

                content.addEventListener('touchend', e => {
                    if (!isSwiping) return;
                    isSwiping = false;
                    const currentX = e.changedTouches[0].clientX;
                    const diffX = startX - currentX;

                    if (Math.abs(diffX) < 50) return;

                    const index = parseInt(dialog.getAttribute('data-index'));
                    if (diffX > 0 && document.querySelector(`#fullscreen-${index + 1}`)) {
                        openFullscreen(`fullscreen-${index + 1}`);
                    } else if (diffX < 0 && index > 0) {
                        openFullscreen(`fullscreen-${index - 1}`);
                    }
                });
            });

            document.querySelectorAll('.fullscreen-dialog').forEach(dialog => {
                dialog.addEventListener('touchmove', e => {
                    if (dialog.classList.contains('active')) {
                        e.preventDefault();
                    }
                });
            });
        }

        // Event listeners for search, filter, and per-page
        document.getElementById('search-button').addEventListener('click', () => {
            const params = {
                search: document.getElementById('search-input').value,
                type: document.getElementById('type-select').value,
                per_page: document.getElementById('per-page-input').value,
                page: 1
            };
            fetchImages(params);
        });

        document.getElementById('filter-button').addEventListener('click', () => {
            const params = {
                search: document.getElementById('search-input').value,
                type: document.getElementById('type-select').value,
                per_page: document.getElementById('per-page-input').value,
                page: 1
            };
            fetchImages(params);
        });

        document.getElementById('per-page-button').addEventListener('click', () => {
            const params = {
                search: document.getElementById('search-input').value,
                type: document.getElementById('type-select').value,
                per_page: document.getElementById('per-page-input').value,
                page: 1
            };
            fetchImages(params);
        });

        // View More button listener (using event delegation)
        document.addEventListener('click', e => {
            if (e.target.id === 'view-more-button') {
                const button = e.target;
                const params = {
                    search: button.dataset.search,
                    type: button.dataset.type,
                    per_page: button.dataset.perPage,
                    page: button.dataset.page,
                    load_more: 1
                };
                console.log('View More clicked with params:', params);
                fetchImages(params, true);
            }
        });

        // Initial listeners
        attachImageListeners();
        attachSwipeListeners();
    </script>
</body>

</html>
