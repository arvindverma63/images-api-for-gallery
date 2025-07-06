```blade
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <style>
        body {
            background: #f9fafb;
            font-family: 'Roboto', sans-serif;
            margin: 0;
        }

        .container {
            max-width: 1280px;
            margin: 0 auto;
            padding: 1rem;
        }

        .text-3xl {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 1.5rem;
        }

        .flex {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .items-center {
            align-items: center;
        }

        .border {
            border: 1px solid #e5e7eb;
        }

        .p-2 {
            padding: 0.5rem;
        }

        .rounded {
            border-radius: 0.375rem;
        }

        .mr-2 {
            margin-right: 0.5rem;
        }

        .bg-blue-500 {
            background: #3b82f6;
        }

        .text-white {
            color: #fff;
        }

        .grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 1rem;
        }

        @media (min-width: 640px) {
            .grid {
                grid-template-columns: repeat(2, 1fr);
            }
        }

        @media (min-width: 768px) {
            .grid {
                grid-template-columns: repeat(3, 1fr);
            }
        }

        @media (min-width: 1024px) {
            .grid {
                grid-template-columns: repeat(5, 1fr);
            }
        }

        .card {
            width: 100%;
            box-shadow: 0 1px 2px rgba(0, 0, 0, 0.05);
        }

        .card:hover {
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
        }

        .card-img {
            width: 100%;
            height: auto;
            max-height: 300px;
            object-fit: cover;
            display: block;
        }

        .text-sm {
            font-size: 0.875rem;
            color: #4b5563;
        }

        .justify-between {
            justify-content: space-between;
        }

        .bg-white {
            background: #fff;
        }

        .space-x-2>*+* {
            margin-left: 0.5rem;
        }

        .text-red-500 {
            color: #ef4444;
        }

        .text-gray-500 {
            color: #6b7280;
        }

        .mt-6 {
            margin-top: 1.5rem;
        }

        .text-center {
            text-align: center;
        }

        .fullscreen-dialog {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: rgba(0, 0, 0, 0.9);
            z-index: 50;
            display: none;
        }

        .fullscreen-dialog:target {
            display: block;
        }

        .dialog-content {
            background: #000;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .fullscreen-img {
            width: 100%;
            max-height: calc(100vh - 2rem);
            object-fit: contain;
        }

        .nav-button {
            color: #fff;
            font-size: 2rem;
            position: absolute;
            top: 50%;
            transform: translateY(-50%);
            text-decoration: none;
            padding: 0.5rem;
        }

        .nav-button.left {
            left: 10px;
        }

        .nav-button.right {
            right: 10px;
        }

        .close-button {
            color: #fff;
            font-size: 2rem;
            position: absolute;
            top: 10px;
            right: 10px;
            text-decoration: none;
        }

        @media (max-width: 639px) {
            .container {
                padding: 0.5rem;
            }

            .card {
                margin: 0;
            }

            .card-img {
                max-height: 400px;
                /* Larger images on mobile */
            }

            .text-3xl {
                font-size: 1.5rem;
                /* Smaller heading on mobile */
            }

            .flex {
                flex-direction: column;
                gap: 0.5rem;
            }

            .mr-2 {
                margin-right: 0;
                margin-bottom: 0.5rem;
            }

            input,
            select,
            button {
                width: 100%;
                box-sizing: border-box;
            }

            .fullscreen-img {
                max-height: calc(100vh - 1rem);
            }

            .nav-button {
                font-size: 1.5rem;
                padding: 0.25rem;
            }

            .close-button {
                font-size: 1.5rem;
                top: 5px;
                right: 5px;
            }
        }
    </style>
</head>

<body>
    <div class="container">
        <h1 class="text-3xl">Image Gallery</h1>
        <div class="flex">
            <form action="{{ route('gallery.index') }}" method="GET" class="flex items-center mr-2">
                <input type="text" name="search" value="{{ old('search', $search ?? '') }}"
                    class="border p-2 rounded" placeholder="Search by title or description">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded">Search</button>
            </form>
            <form action="{{ route('gallery.index') }}" method="GET" class="flex items-center mr-2">
                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                <select name="type" class="border p-2 rounded">
                    <option value="" {{ old('type', $type ?? '') === '' ? 'selected' : '' }}>All Types</option>
                    <option value="jpg" {{ old('type', $type ?? '') === 'jpg' ? 'selected' : '' }}>jpg</option>
                    <option value="jpeg" {{ old('type', $type ?? '') === 'jpeg' ? 'selected' : '' }}>jpeg</option>
                    <option value="png" {{ old('type', $type ?? '') === 'png' ? 'selected' : '' }}>png</option>
                    <option value="gif" {{ old('type', $type ?? '') === 'gif' ? 'selected' : '' }}>gif</option>
                </select>
                <button type="submit" class="bg-blue-500 text-white p-2 rounded">Filter</button>
            </form>
            <form action="{{ route('gallery.index') }}" method="GET" class="flex items-center">
                <input type="hidden" name="search" value="{{ $search ?? '' }}">
                <input type="hidden" name="type" value="{{ $type ?? '' }}">
                <input type="number" name="per_page" value="{{ old('per_page', $perPage ?? 10) }}"
                    class="border p-2 rounded" placeholder="Per Page" min="1" max="100">
                <button type="submit" class="bg-blue-500 text-white p-2 rounded">Apply</button>
            </form>
        </div>

        <div class="grid">
            @forelse ($images as $index => $image)
                <div class="card border">
                    <a href="#fullscreen-{{ $index }}">
                        <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}" class="card-img">
                    </a>
                    <div class="p-2 flex justify-between items-center bg-white">
                        <p class="text-sm">{{ $image->title ?? 'Untitled Image' }}</p>
                        <div class="flex space-x-2">
                            <span class="text-red-500">❤️</span>
                            <span class="text-gray-500">↗</span>
                        </div>
                    </div>
                </div>
                <!-- Fullscreen Modal -->
                <div id="fullscreen-{{ $index }}" class="fullscreen-dialog">
                    <div class="dialog-content">
                        <a href="#" class="close-button material-icons">close</a>
                        @if ($index > 0)
                            <a href="#fullscreen-{{ $index - 1 }}"
                                class="nav-button left material-icons">chevron_left</a>
                        @endif
                        <img src="{{ $image->proxy_url }}" alt="{{ $image->title ?? 'Image' }}"
                            class="fullscreen-img">
                        @if ($index < $images->count() - 1)
                            <a href="#fullscreen-{{ $index + 1 }}"
                                class="nav-button right material-icons">chevron_right</a>
                        @endif
                    </div>
                </div>
            @empty
                <p class="text-center text-sm">No images found.</p>
            @endforelse
        </div>

        @if ($images->hasMorePages())
            <div class="text-center mt-6">
                <form action="{{ route('gallery.index') }}" method="GET">
                    <input type="hidden" name="search" value="{{ $search ?? '' }}">
                    <input type="hidden" name="type" value="{{ $type ?? '' }}">
                    <input type="hidden" name="per_page" value="{{ $perPage ?? 10 }}">
                    <input type="hidden" name="page" value="{{ $page + 1 }}">
                    <input type="hidden" name="load_more" value="1">
                    <button type="submit" class="bg-blue-500 text-white p-2 rounded">View More</button>
                </form>
            </div>
        @endif
    </div>
</body>

</html>
```
