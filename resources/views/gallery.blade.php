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

        @if ($errorMessage)
            <p class="text-red-600 text-center mt-4">{{ $errorMessage }}</p>
        @endif

        @if ($images->isEmpty() && !$fullscreenImage)
            <p class="text-center text-gray-600 col-span-2">No images found.</p>
        @endif

        @if ($fullscreenImage)
            <div class="fixed inset-0 bg-black bg-opacity-95 z-50 flex items-center justify-center">
                <div class="relative w-full h-full flex items-center justify-center">
                    <a href="{{ route('gallery.index', ['search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                        class="absolute top-4 right-4 text-white text-2xl opacity-70 hover:opacity-100">X</a>
                    @if ($prevImage)
                        <a href="{{ route('gallery.index', ['fullscreen' => $prevImage->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                            class="absolute left-2 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100">&lt;</a>
                    @endif
                    <img src="{{ $fullscreenImage->proxy_url }}" alt="{{ $fullscreenImage->title ?? 'Image' }}"
                        class="max-w-full max-h-screen object-contain select-none"
                        onerror="this.closest('.fixed').classList.add('hidden')">
                    @if ($nextImage)
                        <a href="{{ route('gallery.index', ['fullscreen' => $nextImage->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}"
                            class="absolute right-2 top-1/2 -translate-y-1/2 text-white text-4xl opacity-70 hover:opacity-100">&gt;</a>
                    @endif
                </div>
            </div>
        @else
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-0">
                @foreach ($images as $image)
                    <div class="group" data-image-id="{{ $image->id }}">
                        <a href="{{ route('gallery.index', ['fullscreen' => $image->id, 'search' => $search, 'type' => $type, 'per_page' => $perPage, 'page' => $page]) }}">
                            <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}"
                                class="w-full h-48 object-cover block border-none"
                                onerror="this.closest('.group').classList.add('hidden')">
                        </a>
                    </div>
                @endforeach
            </div>

            @if ($images->hasMorePages())
                <div class="text-center mt-6">
                    <a href="{{ route('gallery.index', ['page' => $page + 1, 'search' => $search, 'type' => $type, 'per_page' => $perPage]) }}"
                        class="bg-blue-500 text-white px-4 py-2 rounded hover:bg-blue-600">View More</a>
                </div>
            @endif
        @endif
    </div>
</body>
</html>
