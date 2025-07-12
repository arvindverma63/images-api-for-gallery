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
            align-items: center;
            justify-content: center;
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
        <form method="GET" action="{{ route('gallery.index') }}" class="flex flex-col sm:flex-row items-center mb-4 gap-2">
            <div class="flex w-full sm:w-auto items-center">
                <input type="text" id="search-input" name="search" value="{{ old('search', $search ?? '') }}"
                    class="border border-gray-300 p-2 rounded-l-md w-full sm:w-64 focus:outline-none focus:ring-2 focus:ring-blue-500"
                    placeholder="Search by title or description">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded-r-md hover:bg-blue-600">Search</button>
            </div>
            <div class="flex w-full sm:w-auto items-center">
                <select id="type-select" name="type" class="border border-gray-300 p-2 rounded-l-md focus:outline-none focus:ring-2 focus:ring-blue-500">
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

        @if ($images->isEmpty() && !$fullscreenImage)
            <p id="no-images" class="text-center text-gray-600 col-span-2">No images found.</p>
        @endif

        @if ($fullscreenImage)
            <div class="fullscreen-dialog active">
                <div class="dialog-content">
                    <a href="{{ route('gallery.index', ['search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                        class="close-button material-icons">close</a>
                    @php
                        $currentIndex = $images->items()->search(fn($item) => $item->id == $fullscreenImage->id);
                        $prevImage = $currentIndex > 0 ? $images->items()[$currentIndex - 1] : null;
                        $nextImage = $currentIndex < count($images->items()) - 1 ? $images->items()[$currentIndex + 1] : null;
                    @endphp
                    @if ($prevImage)
                        <a href="{{ route('gallery.index', ['fullscreen' => $prevImage->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                            class="nav-button left material-icons">chevron_left</a>
                    @endif
                    <img src="{{ $fullscreenImage->proxy_url }}" alt="{{ $fullscreenImage->title ?? 'Image' }}"
                        class="fullscreen-img" onerror="this.closest('.fullscreen-dialog').style.display='none';">
                    @if ($nextImage)
                        <a href="{{ route('gallery.index', ['fullscreen' => $nextImage->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                            class="nav-button right material-icons">chevron_right</a>
                    @endif
                </div>
            </div>
        @else
            <div id="gallery-grid" class="gallery-grid">
                @foreach ($images as $index => $image)
                    <div class="card" data-image-id="{{ $image->id }}">
                        <a href="{{ route('gallery.index', ['fullscreen' => $image->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}">
                            <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}"
                                class="card-img" onerror="this.closest('.card').classList.add('hidden')">
                        </a>
                    </div>
                @endforeach
            </div>

            @if ($images->hasMorePages())
                <div id="view-more" class="text-center mt-6">
                    <a href="{{ route('gallery.index', ['page' => $page + 1, 'search' => $search, 'type' => $type, 'per_page' => $perPage]) }}"
                        class="bg-blue-500 text-white p-2 rounded hover:bg-blue-600">View More</a>
                </div>
            @endif
        @endif
    </div>
</body>
</html>
