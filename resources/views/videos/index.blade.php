<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Video Gallery</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background-color: #000;
            color: #fff;
        }
        .video-card {
            border: none;
            background: none;
            padding: 0;
            cursor: pointer;
        }
        .video-card video {
            width: 100%;
            aspect-ratio: 1 / 1; /* Square like Instagram */
            object-fit: cover;
        }
        .search-bar {
            max-width: 500px;
            margin: 1rem auto;
        }
        .search-bar input {
            background-color: #222;
            color: #fff;
            border: 1px solid #444;
        }
        .search-bar button {
            background-color: #007bff;
            border: none;
        }
        .modal-content {
            background: none;
            border: none;
        }
        .modal-dialog {
            max-width: 100%;
            margin: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }
        .modal-video {
            max-width: 90vw;
            max-height: 90vh;
            width: auto;
            height: auto;
            object-fit: contain;
        }
        .modal-footer {
            border: none;
            justify-content: space-between;
            padding: 0.5rem;
        }
        .modal-footer button {
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: none;
        }
        .pagination {
            justify-content: center;
        }
        .pagination .page-link {
            background-color: #222;
            border: 1px solid #444;
            color: #fff;
        }
        .pagination .page-link:hover {
            background-color: #444;
        }
        .swipe-area {
            position: absolute;
            top: 0;
            bottom: 0;
            width: 50%;
            z-index: 10;
        }
        .swipe-left {
            left: 0;
        }
        .swipe-right {
            right: 0;
        }
        .close-button {
            position: absolute;
            top: 10px;
            right: 10px;
            background: rgba(0, 0, 0, 0.7);
            color: #fff;
            border: none;
            font-size: 1.5rem;
            line-height: 1;
            padding: 0.5rem 0.75rem;
            z-index: 11;
        }
    </style>
</head>
<body>
    <div class="container my-3">
        <!-- Search Bar -->
        <form action="{{ route('videos.index') }}" method="GET" class="search-bar">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Search videos..." value="{{ request('search') }}">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <!-- Video Grid -->
        <div class="row row-cols-2 row-cols-md-6 g-1">
            @foreach ($videos as $video)
                <div class="col">
                    <div class="video-card" data-bs-toggle="modal" data-bs-target="#videoModal" data-video-src="{{ $video->proxied_url }}" data-video-id="{{ $video->id }}">
                        <video autoplay muted loop playsinline>
                            <source src="{{ $video->proxied_url }}" type="video/mp4">
                            Your browser does not support the video tag.
                        </video>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="mt-3">
            {{ $videos->links('pagination::bootstrap-5') }}
        </div>
    </div>

    <!-- Full-Screen Modal -->
    <div class="modal fade" id="videoModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-body p-0 position-relative">
                    <button type="button" class="close-button" data-bs-dismiss="modal" aria-label="Close">&times;</button>
                    <div class="swipe-area swipe-left" onclick="navigateVideo(-1)"></div>
                    <div class="swipe-area swipe-right" onclick="navigateVideo(1)"></div>
                    <video controls class="modal-video" id="modalVideo">
                        <source id="modalVideoSource" src="" type="video/mp4">
                        Your browser does not support the video tag.
                    </video>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" onclick="navigateVideo(-1)">Previous</button>
                    <button type="button" class="btn btn-secondary" onclick="navigateVideo(1)">Next</button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const videoCards = document.querySelectorAll('.video-card');
            const modalVideo = document.querySelector('.modal-video');
            const modalVideoSource = document.getElementById('modalVideoSource');
            const videoModal = document.getElementById('videoModal');
            let currentVideoIndex = 0;
            const videoData = [
                @foreach ($videos as $video)
                    { id: {{ $video->id }}, src: "{{ $video->proxied_url }}" },
                @endforeach
            ];

            videoCards.forEach((card, index) => {
                card.addEventListener('click', () => {
                    currentVideoIndex = index;
                    updateModalVideo();
                });
            });

            window.navigateVideo = (direction) => {
                currentVideoIndex = (currentVideoIndex + direction + videoData.length) % videoData.length;
                updateModalVideo();
            };

            function updateModalVideo() {
                const video = videoData[currentVideoIndex];
                modalVideoSource.setAttribute('src', video.src);
                modalVideo.load();
                modalVideo.play();
            }

            videoModal.addEventListener('hidden.bs.modal', () => {
                modalVideo.pause();
                modalVideoSource.setAttribute('src', '');
            });

            // Swipe detection
            let touchStartX = 0;
            videoModal.addEventListener('touchstart', (e) => {
                touchStartX = e.changedTouches[0].screenX;
            });
            videoModal.addEventListener('touchend', (e) => {
                const touchEndX = e.changedTouches[0].screenX;
                if (touchStartX - touchEndX > 50) {
                    navigateVideo(1); // Swipe left -> next
                } else if (touchEndX - touchStartX > 50) {
                    navigateVideo(-1); // Swipe right -> previous
                }
            });
        });
    </script>
</body>
</html>
