<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>

<body class="bg-gray-100 font-sans">
    <div class="container mx-auto px-4 sm:px-6 lg:px-8 py-4">
        <h1 class="text-2xl sm:text-3xl font-bold text-gray-800 mb-4">Image Gallery</h1>
        <form method="GET" action="{{ route('gallery.index') }}"
            class="flex flex-col sm:flex-row items-center mb-4 gap-2">
            <div class="flex w-full sm:w-auto items-center">
                <input type="text" id="search-input" name="search" value="{{ old('search', $search ?? '') }}"
                    class="border border-gray-300 p-2 rounded-l-md w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Search by title or description">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Search</button>
            </div>
            <div class="flex w-full sm:w-auto items-center">
                <select id="type-select" name="type"
                    class="border border-gray-300 p-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
                    <option value="" {{ old('type', $type ?? '') === '' ? 'selected' : '' }}>All Types</option>
                    <option value="jpg" {{ old('type', $type ?? '') === 'jpg' ? 'selected' : '' }}>jpg</option>
                    <option value="jpeg" {{ old('type', $type ?? '') === 'jpeg' ? 'selected' : '' }}>jpeg</option>
                    <option value="png" {{ old('type', $type ?? '') === 'png' ? 'selected' : '' }}>png</option>
                    <option value="gif" {{ old('type', $type ?? '') === 'gif' ? 'selected' : '' }}>gif</option>
                </select>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Filter</button>
            </div>
            <div class="flex w-full sm:w-auto items-center">
                <input type="number" id="per-page-input" name="per_page" value="{{ old('per_page', $perPage ?? 10) }}"
                    class="border border-gray-300 p-2 rounded-l-md w-24 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Per Page" min="1" max="100">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Apply</button>
            </div>
        </form>

        <p id="error-message" class="text-red-600 text-center mt-4 hidden">Failed to load images. Please try again.</p>

        <div id="gallery-grid" class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-0">
            @forelse ($images as $index => $image)
                <div class="group" data-image-id="{{ $image->id }}" data-image-url="{{ $image->proxy_url }}"
                    data-index="{{ $index }}">
                    <a href="javascript:void(0)" class="image-link block"
                        data-fullscreen-id="fullscreen-{{ $index }}">
                        <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}"
                            class="w-full h-48 object-cover block border-none"
                            onerror="this.closest('.group').classList.add('hidden')">
                    </a>
                    <div id="fullscreen-{{ $index }}"
                        class="fixed inset-0 bg-black bg-opacity-95 z-50 items-center justify-center hidden">
                        <div class="relative w-full h-full flex items-center justify-center">
                            <a href="javascript:void(0)"
                                class="close-button absolute top-4 right-4 text-white text-2xl opacity-70 hover:opacity-100"
                                onclick="closeFullscreen()">X</a>
                            @if ($index > 0)
                                <a href="javascript:void(0)"
                                    class="nav-button absolute left-4 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100"
                                    data-fullscreen-id="fullscreen-{{ $index - 1 }}"></a>
                            @endif
                            <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}"
                                class="max-w-full max-h-screen object-contain select-none"
                                onerror="this.closest('.fixed').classList.add('hidden')">
                            @if ($index < $images->count() - 1)
                                <a href="javascript:void(0)"
                                    class="nav-button absolute right-4 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100"
                                    data-fullscreen-id="fullscreen-{{ $index + 1 }}">
                                    << /a>
                            @endif
                        </div>
                    </div>
                </div>
            @empty
                <p id="no-images" class="text-center text-gray-600 col-span-2">No images found.</p>
            @endforelse
        </div>

        @if ($images->hasMorePages())
            <div id="view-more" class="text-center mt-6">
                <button id="view-more-button" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
                    data-page="{{ $page + 1 }}" data-search="{{ $search ?? '' }}" data-type="{{ $type ?? '' }}"
                    data-per-page="{{ $perPage ?? 10 }}">View More</button>
            </div>
        @endif
    </div>

    <script>
        // Track loaded image IDs to prevent duplicates
        const loadedImageIds = new Set(
            Array.from(document.querySelectorAll('.group')).map(card => card.getAttribute('data-image-id')).filter(id =>
                id)
        );

        // Show/hide error message
        function toggleErrorMessage(show, message = 'Failed to load images. Please try again.') {
            const errorMessage = document.getElementById('error-message');
            errorMessage.textContent = message;
            errorMessage.classList.toggle('hidden', !show);
        }

        // Open fullscreen dialog
        function openFullscreen(id) {
            document.querySelectorAll('.fixed').forEach(dialog => {
                dialog.classList.add('hidden');
            });
            const dialog = document.getElementById(id);
            if (dialog) {
                dialog.classList.remove('hidden');
            }
        }

        // Close fullscreen dialog
        function closeFullscreen() {
            document.querySelectorAll('.fixed').forEach(dialog => {
                dialog.classList.add('hidden');
            });
        }

        // Fetch images via AJAX
        async function fetchImages(params, append = false) {
            const galleryGrid = document.getElementById('gallery-grid');
            if (!galleryGrid) {
                console.error('Gallery grid element not found');
                toggleErrorMessage(true, 'Gallery grid element not found.');
                return;
            }

            toggleErrorMessage(false);
            const url = new URL('{{ route('gallery.index') }}');
            url.search = new URLSearchParams(params).toString();
            console.log('Fetching images with params:', params, 'URL:', url.toString());

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

                const data = await response.json();
                console.log('Received data:', data);

                const viewMore = document.getElementById('view-more') || document.createElement('div');
                if (!viewMore.id) {
                    viewMore.id = 'view-more';
                    viewMore.className = 'text-center mt-6';
                    document.querySelector('.container').appendChild(viewMore);
                }

                if (!append) {
                    galleryGrid.innerHTML = '';
                    loadedImageIds.clear();
                }

                let imagesAdded = 0;
                if (data.images && data.images.length > 0) {
                    const existingIndices = Array.from(document.querySelectorAll('.fixed')).map(dialog =>
                        parseInt(dialog.getAttribute('data-index')) || 0
                    );
                    const maxIndex = existingIndices.length > 0 ? Math.max(...existingIndices) : -1;

                    data.images.forEach((image, index) => {
                        if (loadedImageIds.has(image.id)) {
                            console.log('Skipping duplicate image ID:', image.id);
                            return;
                        }

                        const globalIndex = append ? maxIndex + 1 + index : index;
                        loadedImageIds.add(image.id);
                        imagesAdded++;

                        const card = document.createElement('div');
                        card.className = 'group';
                        card.setAttribute('data-image-id', image.id);
                        card.setAttribute('data-image-url', image.proxy_url);
                        card.setAttribute('data-index', globalIndex);
                        card.innerHTML = `
                            <a href="javascript:void(0)" class="image-link block" data-fullscreen-id="fullscreen-${globalIndex}">
                                <img src="${image.proxy_url}" alt="${image.title || 'Image'}" class="w-full h-48 object-cover block border-none" onerror="this.closest('.group').classList.add('hidden')">
                            </a>
                            <div id="fullscreen-${globalIndex}" class="fixed inset-0 bg-black bg-opacity-95 z-50 items-center justify-center hidden" data-index="${globalIndex}">
                                <div class="relative w-full h-full flex items-center justify-center">
                                    <a href="javascript:void(0)" class="close-button absolute top-4 right-4 text-white text-2xl opacity-70 hover:opacity-100" onclick="closeFullscreen()">X</a>
                                    ${globalIndex > 0 ? `<a href="javascript:void(0)" class="nav-button absolute left-4 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100" data-fullscreen-id="fullscreen-${globalIndex - 1}"><</a>` : ''}
                                    <img src="${image.proxy_url}" alt="${image.title || 'Image'}" class="max-w-full max-h-screen object-contain select-none" onerror="this.closest('.fixed').classList.add('hidden')">
                                    ${globalIndex < data.total - 1 ? `<a href="javascript:void(0)" class="nav-button absolute right-4 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100" data-fullscreen-id="fullscreen-${globalIndex + 1}">></a>` : ''}
                                </div>
                            </div>
                        `;
                        galleryGrid.appendChild(card);
                    });

                    if (data.hasMorePages) {
                        viewMore.innerHTML = `
                            <button id="view-more-button" class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600"
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

                if (append) {
                    window.scrollTo({
                        top: scrollY,
                        behavior: 'instant'
                    });
                }

                attachImageListeners();
            } catch (error) {
                console.error('Error fetching images:', error);
                toggleErrorMessage(true);
                if (!append) {
                    galleryGrid.innerHTML =
                        '<p id="no-images" class="text-center text-gray-600 col-span-2">Error loading images.</p>';
                }
                viewMore.innerHTML = '';
            }
        }

        // Attach event listeners for image clicks and navigation
        function attachImageListeners() {
            document.querySelectorAll('.image-link').forEach(link => {
                link.removeEventListener('click', handleImageClick);
                link.addEventListener('click', handleImageClick);
            });
            document.querySelectorAll('.nav-button').forEach(button => {
                button.removeEventListener('click', handleNavClick);
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

        // Event listeners for view more
        document.addEventListener('click', e => {
            if (e.target.id === 'view-more-button') {
                const button = e.target;
                const params = {
                    search: button.dataset.search,
                    type: button.dataset.type,
                    per_page: button.dataset.perPage,
                    page: button.dataset.page,
                    ajax: 1
                };
                console.log('View More clicked with params:', params);
                fetchImages(params, true);
            }
        });

        // Initial listeners
        attachImageListeners();
    </script>
</body>

</html>
