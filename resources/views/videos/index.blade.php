<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .video-card {
            height: 100%;
            display: flex;
            flex-direction: column;
        }

        .video-card video {
            width: 100%;
            height: 120px;
            object-fit: cover;
        }

        .card-body {
            padding: 0.75rem;
            flex: 1;
        }

        .card-title {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }

        .card-text {
            font-size: 0.8rem;
            color: #6c757d;
            overflow: hidden;
            text-overflow: ellipsis;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
        }

        .search-bar {
            max-width: 500px;
            margin: 1rem auto;
        }
    </style>
</head>

<body>
    <div class="container my-4">
        <h1 class="text-center mb-4">Video Gallery</h1>

        <!-- Search Bar -->
        <form action="{{ route('videos.index') }}" method="GET" class="search-bar">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search videos..."
                    value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <!-- Video Grid -->
        <div class="row row-cols-2 row-cols-md-6 g-3">
            @foreach ($videos as $video)
                <div class="col">
                    <div class="card video-card">
                        <video controls>
                            <source src="{{ $video->proxied_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                        <div class="card-body">
                            <h5 class="card-title">{{ $video->title }}</h5>
                            <p class="card-text">{{ $video->description }}</p>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-4">
            {{ $videos->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>

</html>
