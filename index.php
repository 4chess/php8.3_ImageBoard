<?php

declare(strict_types=1);

class FileUploadConfig {
    public function __construct(
        public string $uploadDir = "uploads/",
        public int $maxFileSize = 30000000, // in bytes
        public array $allowedTypes = ["image/jpeg", "image/png", "image/gif"]
    ) {}
}

class FileUploadHandler {
    public function __construct(private FileUploadConfig $config) {}

    public function handleUpload(): void {
        if ($_SERVER["REQUEST_METHOD"] !== "POST" || empty($_POST['title'])) {
            return;
        }

        $image = $_FILES["image"] ?? null;
        $title = trim($_POST['title']);

        if ($this->isValidUpload($image) && $this->isValidTitle($title)) {
            $this->processFile($image, $title);
        } else {
            // More specific error message based on the validation that failed
            if (!$this->isValidUpload($image)) {
                echo "<p>Error: Invalid file upload.</p>";
            }
            if (!$this->isValidTitle($title)) {
                echo "<p>Error: Title is too long. Max 20 characters allowed.</p>";
            }
        }
    }

    private function isValidUpload(?array $file): bool {
        return isset($file) && $file["error"] === UPLOAD_ERR_OK;
    }

    private function isValidTitle(string $title): bool {
        return strlen($title) <= 20;
    }


    private function processFile(array $file, string $title): void {
        ['name' => $fileName, 'type' => $fileType, 'size' => $fileSize, 'tmp_name' => $tmpName] = $file;

        if (!in_array($fileType, $this->config->allowedTypes)) {
            echo "<p>Error: Only jpg, png, and gif files are allowed.</p>";
            return;
        }

        if ($fileSize > $this->config->maxFileSize) {
            echo "<p>Error: File size must not exceed {$this->config->maxFileSize} bytes.</p>";
            return;
        }

        $safeTitle = preg_replace('/[^A-Za-z0-9\-]/', '', $title);
        $fileExtension = pathinfo($fileName, PATHINFO_EXTENSION);
        $filePath = $this->config->uploadDir . $safeTitle . '.' . $fileExtension;

        if (!move_uploaded_file($tmpName, $filePath)) {
            echo "<p>Error: Failed to move the file to the upload directory.</p>";
        }
    }
}

class GalleryDisplay {
    public function __construct(private string $uploadDir) {}

    public function display(): void {
        $files = array_diff(scandir($this->uploadDir, SCANDIR_SORT_DESCENDING), ['.', '..']);

        foreach ($files as $file) {
            $title = pathinfo($file, PATHINFO_FILENAME);
            $filePath = $this->uploadDir . $file;
            
            echo "<div class='thumbnail'>";
            echo "<a href='$filePath' target='_blank'>";
            echo "<img src='$filePath' alt='$title' style='width: 100px; height: 100px; object-fit: cover;'>";
            echo "</a>";
            echo "<p>$title</p>";
            echo "</div>";
        }
    }
}

$config = new FileUploadConfig();
$uploader = new FileUploadHandler($config);
$uploader->handleUpload();

$gallery = new GalleryDisplay($config->uploadDir);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Image Gallery</title>
    <style>
    .gallery {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
    }
    .thumbnail {
        border: 1px solid #ddd;
        padding: 5px;
        text-align: center;
        width: 200px; /* Set a fixed width */
        height: 250px; /* Set a fixed height */
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .thumbnail img {
        width: 100%;
        height: 150px; /* Adjust as needed */
        object-fit: cover;
    }
    .thumbnail p {
        margin-top: 5px;
        word-wrap: break-word; /* Break long words */
        overflow-wrap: break-word; /* Ensures the content breaks */
        height: 50px; /* Set a fixed height for the title area */
        overflow: hidden; /* Hide overflowed content */
    }
    </style>
</head>
<body>
    <form action="" method="post" enctype="multipart/form-data">
        <input type="text" name="title" placeholder="Enter image title" maxlength="20" required>
        <input type="file" name="image" required>
        <input type="submit" value="Upload Image">
    </form>

    <div class='gallery'>
        <?php $gallery->display(); ?>
    </div>
</body>
</html>
