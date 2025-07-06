<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" rel="stylesheet" />
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet" />
    <style>
        .card-img {
            height: 200px;
            width: 100%;
            object-fit: cover;
        }
        .fullscreen-img {
            max-height: 80vh;
            object-fit: contain;
        }
        .dialog-content {
            background: black;
            padding: 0;
            position: relative;
        }
        .nav-button {
            color: white;
            font-size: 2rem;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
        }
        .close-button {
            color: white;
            font-size: 2rem;
            position: absolute;
            top: 10px;
            right: 10px;
            cursor: pointer;
        }
    </style>
</head>
<body class="bg-gray-50">
    <div class="container mx-auto p-4">
        <h1 class="text-3xl font-bold text-gray-800 mb-6">Image Gallery</h1>
        <div class="mb-6 flex flex-wrap gap-4">
            <div class="flex items-center">
                <input type="text" value="{{ old('search', $search ?? '') }}" id="searchInput" class="border p-2 rounded mr-2" placeholder="Search by title" />
                <button id="searchButton" class="bg-blue-500 text-white p-2 rounded">Search</button>
            </div>
            <select id="typeSelect" class="border p-2 rounded mr-2">
                <option value="" {{ old('type', $type ?? '') === '' ? 'selected' : '' }}>All Types</option>
                <option value="jpg" {{ old('type', $type ?? '') === 'jpg' ? 'selected' : '' }}>jpg</option>
                <option value="jpeg" {{ old('type', $type ?? '') === 'jpeg' ? 'selected' : '' }}>jpeg</option>
                <option value="png" {{ old('type', $type ?? '') === 'png' ? 'selected' : '' }}>png</option>
                <option value="gif" {{ old('type', $type ?? '') === 'gif' ? 'selected' : '' }}>gif</option>
            </select>
            <input type="number" value="{{ old('perPage', $perPage ?? 50) }}" id="perPageInput" class="border p-2 rounded" placeholder="Per Page" />
        </div>

        @if ($loading)
            <div class="text-center"><div class="inline-block h-8 w-8 animate-spin rounded-full border-4 border-solid border-blue-500 border-t-transparent"></div></div>
        @else
            <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4" id="galleryGrid">
                @foreach ($images as $index => $image)
                    <div class="border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer" onclick="openFullscreen({{ $index }})" data-index="{{ $index }}" data-image="{{ $image['image'] }}">
                        @if ($image['image'])
                            <img src="{{ $image['image'] }}" alt="{{ $image['title'] }}" class="card-img">
                        @endif
                        <div class="p-2 flex justify-between items-center bg-white">
                            <p class="text-sm text-gray-600">{{ $image['title'] ?? 'From ARVIND Verma\'s images' }}</p>
                            <div class="flex space-x-2">
                                <span class="text-red-500 cursor-pointer">❤️</span>
                                <span class="text-gray-500 cursor-pointer">↗</span>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            @if (count($images) < $total)
                <div class="text-center mt-6">
                    <button id="viewMoreButton" class="bg-blue-500 text-white p-2 rounded">View More</button>
                </div>
            @endif
        @endif

        <!-- Fullscreen Dialog -->
        <div id="fullscreenDialog" class="fixed inset-0 bg-black bg-opacity-90 hidden z-50" onclick="closeFullscreen()">
            <div class="relative h-full flex items-center justify-center" onclick="event.stopPropagation()">
                <button class="nav-button left-10" onclick="prevImage(event)">←</button>
                <img id="fullscreenImage" src="" alt="" class="fullscreen-img" onclick="event.stopPropagation()">
                <button class="nav-button right-10" onclick="nextImage(event)">→</button>
                <span class="close-button material-icons" onclick="closeFullscreen(event)">close</span>
            </div>
        </div>
    </div>

    <script>
        let images = @json($images);
        let total = {{ $total ?? 0 }};
        let page = {{ $page ?? 1 }};
        let perPage = {{ $perPage ?? 50 }};
        let selectedIndex = null;

        // Function to check if image URL is valid
        async function isImageValid(url) {
            try {
                const response = await fetch(url, { method: 'HEAD', mode: 'cors' });
                return response.ok;
            } catch {
                return false;
            }
        }

        // Initial filter for broken links
        async function filterBrokenLinks() {
            const validImages = await Promise.all(images.map(async (image, index) => {
                if (image && image.image && await isImageValid(image.image)) {
                    return image;
                }
                return null;
            }));
            images = validImages.filter(img => img !== null);
            updateGallery();
        }

        // Call filter on page load
        filterBrokenLinks();

        document.getElementById('searchButton').addEventListener('click', () => {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeSelect').value;
            const perPage = document.getElementById('perPageInput').value;
            fetchGallery(search, type, 1, perPage); // Reset to page 1
        });

        document.getElementById('typeSelect').addEventListener('change', () => {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeSelect').value;
            const perPage = document.getElementById('perPageInput').value;
            fetchGallery(search, type, 1, perPage); // Reset to page 1
        });

        document.getElementById('perPageInput').addEventListener('change', () => {
            const search = document.getElementById('searchInput').value;
            const type = document.getElementById('typeSelect').value;
            const perPage = document.getElementById('perPageInput').value;
            fetchGallery(search, type, 1, perPage); // Reset to page 1
        });

        document.getElementById('viewMoreButton').addEventListener('click', () => {
            page++;
            fetchGallery(document.getElementById('searchInput').value, document.getElementById('typeSelect').value, page, document.getElementById('perPageInput').value);
        });

        function fetchGallery(search, type, page, perPage) {
            fetch(`https://images.afterdarkhub.com/api/images?search=${search}&type=${type}&page=${page}&per_page=${perPage}`)
                .then(response => response.json())
                .then(async data => {
                    images = data.data || [];
                    total = data.total || 0;
                    // Filter out broken links
                    const validImages = await Promise.all(images.map(async (image, index) => {
                        if (image && image.image && await isImageValid(image.image)) {
                            return image;
                        }
                        return null;
                    }));
                    images = validImages.filter(img => img !== null);
                    updateGallery();
                })
                .catch(error => console.error('Error fetching data:', error));
        }

        function updateGallery() {
            const gallery = document.getElementById('galleryGrid');
            gallery.innerHTML = '';
            images.forEach((image, index) => {
                const div = document.createElement('div');
                div.className = 'border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300 cursor-pointer';
                div.setAttribute('onclick', `openFullscreen(${index})`);
                div.setAttribute('data-index', index);
                div.setAttribute('data-image', image.image);
                div.innerHTML = `
                    <img src="${image.image}" alt="${image.title}" class="card-img">
                    <div class="p-2 flex justify-between items-center bg-white">
                        <p class="text-sm text-gray-600">${image.title || 'From ARVIND Verma\'s images'}</p>
                        <div class="flex space-x-2">
                            <span class="text-red-500 cursor-pointer">❤️</span>
                            <span class="text-gray-500 cursor-pointer">↗</span>
                        </div>
                    </div>
                `;
                gallery.appendChild(div);
            });
            document.getElementById('viewMoreButton').style.display = images.length < total ? 'inline-block' : 'none';
        }

        function openFullscreen(index) {
            selectedIndex = index;
            const image = images[selectedIndex];
            document.getElementById('fullscreenImage').src = image.image;
            document.getElementById('fullscreenImage').alt = image.title;
            document.getElementById('fullscreenDialog').classList.remove('hidden');
        }

        function closeFullscreen(event) {
            event.stopPropagation();
            document.getElementById('fullscreenDialog').classList.add('hidden');
            selectedIndex = null;
        }

        function prevImage(event) {
            event.stopPropagation();
            if (selectedIndex > 0) {
                selectedIndex--;
                openFullscreen(selectedIndex);
            }
        }

        function nextImage(event) {
            event.stopPropagation();
            if (selectedIndex < images.length - 1) {
                selectedIndex++;
                openFullscreen(selectedIndex);
            }
        }

        // Touch events for swipe
        let touchStartX = null;
        let touchEndX = null;

        document.getElementById('fullscreenDialog').addEventListener('touchstart', (e) => {
            touchStartX = e.changedTouches[0].screenX;
        });

        document.getElementById('fullscreenDialog').addEventListener('touchmove', (e) => {
            touchEndX = e.changedTouches[0].screenX;
        });

        document.getElementById('fullscreenDialog').addEventListener('touchend', () => {
            if (touchStartX && touchEndX) {
                const distance = touchStartX - touchEndX;
                const isLeftSwipe = distance > 50;
                const isRightSwipe = distance < -50;
                if (isLeftSwipe && selectedIndex < images.length - 1) nextImage(event);
                if (isRightSwipe && selectedIndex > 0) prevImage(event);
                touchStartX = null;
                touchEndX = null;
            }
        });
    </script>
</body>
</html>
