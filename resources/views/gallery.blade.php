<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Image Gallery</title>
  <script src="https://cdn.jsdelivr.net/npm/react@18/umd/react.development.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/react-dom@18/umd/react-dom.development.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/@babel/standalone/babel.min.js"></script>
  <script src="https://cdn.tailwindcss.com"></script>
  <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Roboto:300,400,500,700&display=swap" />
  <link rel="stylesheet" href="https://fonts.googleapis.com/icon?family=Material+Icons" />
</head>
<body class="bg-gray-50">
  <div id="root"></div>

  <script type="text/babel">
    const { useState, useEffect } = React;
    const { Button, TextField, Select, MenuItem, FormControl, InputLabel, Card, CardMedia, CardContent, Typography, CircularProgress, Dialog, DialogContent, IconButton, Box } = MaterialUI;

    const Gallery = () => {
      const [images, setImages] = useState([]);
      const [page, setPage] = useState(1);
      const [perPage, setPerPage] = useState(50);
      const [search, setSearch] = useState('');
      const [type, setType] = useState('');
      const [total, setTotal] = useState(0);
      const [loading, setLoading] = useState(false);
      const [selectedImageIndex, setSelectedImageIndex] = useState(null);
      const [touchStart, setTouchStart] = useState(null);
      const [touchEnd, setTouchEnd] = useState(null);

      const isImageValid = async (url) => {
        try {
          const response = await fetch(url, { method: 'HEAD', mode: 'cors' });
          return response.ok;
        } catch {
          return false;
        }
      };

      useEffect(() => {
        const fetchImages = async () => {
          setLoading(true);
          // Reset images and page when search or type changes
          if (search !== '' || type !== '') {
            setImages([]);
            setPage(1);
          }
          try {
            const response = await fetch('https://images.afterdarkhub.com/api/images?search=${search}&type=${type}&page=${page}&per_page=${perPage}');
            const data = await response.json();
            const validImages = await Promise.all(data.data.map(async (image) => {
              if (image.image && await isImageValid(image.image)) {
                return image;
              }
              return null;
            }));
            // Only set new images, avoiding append unless paginating
            setImages(validImages.filter(img => img !== null));
            setTotal(data.total || 0);
          } catch (error) {
            console.error('Error fetching data:', error);
          } finally {
            setLoading(false);
          }
        };
        fetchImages();
      }, [page, perPage]); // Removed search and type from dependency to control fetch with button

      const handleSearch = () => {
        setImages([]); // Clear previous images
        setPage(1);   // Reset to first page
        // Trigger fetch by updating a dependency (e.g., page) is handled by useEffect
      };

      const handleViewMore = () => {
        setPage(prev => prev + 1);
      };

      const handleImageClick = (index) => {
        setSelectedImageIndex(index);
      };

      const handleClose = () => {
        setSelectedImageIndex(null);
      };

      const handleNext = () => {
        if (selectedImageIndex < images.length - 1) {
          setSelectedImageIndex(prev => prev + 1);
        }
      };

      const handlePrev = () => {
        if (selectedImageIndex > 0) {
          setSelectedImageIndex(prev => prev - 1);
        }
      };

      const handleTouchStart = (e) => {
        setTouchStart(e.touches[0].clientX);
      };

      const handleTouchMove = (e) => {
        setTouchEnd(e.touches[0].clientX);
      };

      const handleTouchEnd = () => {
        if (!touchStart || !touchEnd) return;
        const distance = touchStart - touchEnd;
        const isLeftSwipe = distance > 50;
        const isRightSwipe = distance < -50;
        if (isLeftSwipe && selectedImageIndex < images.length - 1) handleNext();
        if (isRightSwipe && selectedImageIndex > 0) handlePrev();
        setTouchStart(null);
        setTouchEnd(null);
      };

      return (
        <div className="container mx-auto p-4">
          <h1 className="text-3xl font-bold text-gray-800 mb-6">Image Gallery</h1>
          <div className="mb-6 flex flex-wrap gap-4">
            <div className="flex items-center">
              <TextField
                label="Search by title"
                value={search}
                onChange={(e) => setSearch(e.target.value)}
                variant="outlined"
                size="small"
                className="mr-2"
              />
              <Button variant="contained" color="primary" onClick={handleSearch} className="ml-2">
                Search
              </Button>
            </div>
            <FormControl variant="outlined" size="small" className="mr-2">
              <InputLabel>Type</InputLabel>
              <Select
                value={type}
                onChange={(e) => {
                  setType(e.target.value);
                  setPage(1); // Reset to first page on type change
                }}
                label="Type"
              >
                <MenuItem value="">All Types</MenuItem>
                <MenuItem value="jpg">jpg</MenuItem>
                <MenuItem value="jpeg">jpeg</MenuItem>
                <MenuItem value="png">png</MenuItem>
                <MenuItem value="gif">gif</MenuItem>
              </Select>
            </FormControl>
            <TextField
              label="Per Page"
              type="number"
              value={perPage}
              onChange={(e) => {
                setPerPage(e.target.value);
                setPage(1); // Reset to first page on per page change
              }}
              variant="outlined"
              size="small"
            />
          </div>
          {loading ? (
            <div className="text-center"><CircularProgress /></div>
          ) : (
            <>
              <div className="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                {images.map((image, index) => (
                  <Card key={index} className="border border-gray-200 shadow-sm hover:shadow-md transition-shadow duration-300" onClick={() => handleImageClick(index)}>
                    {image.image && (
                      <CardMedia
                        component="img"
                        height="200"
                        image={image.image}
                        alt={image.title}
                        className="object-cover w-full h-full"
                      />
                    )}
                    <CardContent className="p-2 flex justify-between items-center bg-white">
                      <Typography variant="caption" color="text.secondary">
                        {image.title || `From ARVIND Verma's images`}
                      </Typography>
                      <div className="flex space-x-2">
                        <span className="text-red-500 cursor-pointer">❤️</span>
                        <span className="text-gray-500 cursor-pointer">↗</span>
                      </div>
                    </CardContent>
                  </Card>
                ))}
              </div>
              {images.length < total && (
                <div className="text-center mt-6">
                  <Button variant="contained" color="primary" onClick={handleViewMore} disabled={loading}>
                    View More
                  </Button>
                </div>
              )}
            </>
          )}
          <Dialog open={selectedImageIndex !== null} onClose={handleClose} maxWidth="md" fullWidth>
            <DialogContent
              onTouchStart={handleTouchStart}
              onTouchMove={handleTouchMove}
              onTouchEnd={handleTouchEnd}
              style={{ position: 'relative', background: 'black', padding: 0 }}
            >
              {selectedImageIndex !== null && images[selectedImageIndex] && (
                <>
                  <IconButton onClick={handlePrev} style={{ color: 'white', position: 'absolute', left: 10, top: '50%', transform: 'translateY(-50%)', fontSize: '2rem' }} disabled={selectedImageIndex === 0}>
                    ←
                  </IconButton>
                  <img src={images[selectedImageIndex].image} alt={images[selectedImageIndex].title} style={{ width: '100%', height: 'auto', display: 'block', maxHeight: '80vh', objectFit: 'contain' }} />
                  <IconButton onClick={handleNext} style={{ color: 'white', position: 'absolute', right: 10, top: '50%', transform: 'translateY(-50%)', fontSize: '2rem' }} disabled={selectedImageIndex === images.length - 1}>
                    →
                  </IconButton>
                </>
              )}
            </DialogContent>
          </Dialog>
        </div>
      );
    };

    ReactDOM.render(<Gallery />, document.getElementById('root'));
  </script>

  <script src="https://unpkg.com/@mui/material@5.15.14/umd/material-ui.production.min.js"></script>
</body>
</html>
