<?php

// $basePath = "https://holedigging.club/archive/";
$basePath = "http://localhost/holedigging.club/archive/";
$defaultImagesPerPage = 20;
$validDirectories = array("artbook", "colours", "edits", "pics", "fanart", "misc", "screencaps");
$directory = $_POST['directory'] ?? $_GET['directory'] ?? "pics";

if (!in_array($directory, $validDirectories)) {
  $directory = $validDirectories[0];
}

$pdo = new PDO("sqlite:archive.db") or die("cannot open the database");

// Get number of images in specified directory
$stmt = $pdo->prepare("SELECT COUNT(*) AS totalImages FROM archive WHERE image_path LIKE '%images/' || ? || '/%'");
$stmt->execute([$directory]);
$result = $stmt->fetch();

$totalImages = $result ? $result[0] : 0;

$imagesPerPage = $_POST["imagesPerPage"] ?? $_GET["imagesPerPage"] ?? $defaultImagesPerPage;

if (is_numeric($imagesPerPage)) {
  $imagesPerPage = floor($imagesPerPage);

  if ($imagesPerPage < 1) {
    $imagesPerPage = 1;
  }
  if ($imagesPerPage > $totalImages) {
    $imagesPerPage = $totalImages;
  }
} else {
  $imagesPerPage = $defaultImagesPerPage;
}

$maxPage = 1;

if ($totalImages > 0) {
  $maxPage = ceil($totalImages / $imagesPerPage);
}

$page = $_POST['page'] ?? $_GET['page'] ?? 1;

if (is_numeric($page)) {
  $page = floor($page);

  // Keep us between page 1 and page max
  $page = $page < 1 ? 1 : $page;
  $page = $page > $maxPage ? $maxPage : $page;
} else {
  $page = 1;
}

$offset = ($page - 1) * $imagesPerPage;

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>gallery</title>

  <link rel="stylesheet" href="../style.css">
  <link rel="stylesheet" href="archive-style.css">
</head>
<body>
  <div class="gallery">
    <?php

    // Output images
    $sqlGetImages = "SELECT image_path, thumbnail_path FROM archive WHERE image_path LIKE '%images/' || ? || '/%' ORDER BY image_path LIMIT ? OFFSET ?";
    $stmt = $pdo->prepare($sqlGetImages);
    $stmt->execute([$directory, $imagesPerPage, $offset]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($rows) {
      foreach ($rows as $row) {
        $imagePath = $row["image_path"];
        $thumbnailPath = $row["thumbnail_path"];
        echo "<a class=\"image-link\" href=\"$imagePath\" target=\"_blank\"> <img src=\"$thumbnailPath\"> </a>";
      }
    } else {
      echo "0 images found :(";
    }

    $pdo = null;

    ?>
  </div>

  <div class="gallery-controls">
    <div class="gallery-pagination">
      <?php

      // Pagination
      if ($maxPage > 1) {
        $url = "${basePath}gallery.php?directory=$directory&imagesPerPage=$imagesPerPage&page=";
        
        // Determine start and end for numbered page links
        $start = $page - 2 < 1 ? 1 : $page - 2;
        $end = $page + 2 > $maxPage ? $maxPage : $page + 2;

        // First and previous page
        if ($page != 1) {
          echo "<a href='${url}1'>&lt;&lt;</a>";
          $prev = $page - 1;
          echo "<a href='$url$prev'>&lt;</a>";
        }

        // Current and surrounding pages
        for ($i = $start; $i <= $end; $i++) {
          $pageNum = strlen((string) $i) < 2 ? "0$i" : $i;

          if ($i == $page) {
            echo "<p>$pageNum</p>";
          } else {
            echo "<a href='$url$i'>$pageNum</a>";
          }
        }

        // Next and last page
        if ($page < $maxPage) {
          $next = $page + 1;
          echo "<a href='$url$next'>&gt;</a>";
          echo "<a href='$url$maxPage'>&gt;&gt;</a>";
        }
      }

      ?>
    </div>

    <form method="POST" action="<?php echo $basePath; ?>gallery.php" class="gallery-options">
      <div>
        <label for="directory">Gallery</label>
        <select name="directory">
          <?php

          foreach ($validDirectories as $dir) {
            if ($dir == $directory) {
              echo "<option selected value=\"$dir\">$dir</option>";
            } else {
              echo "<option value=\"$dir\">$dir</option>";
            }
          }

          ?>
        </select>
      </div>

      <div>
        <label for="imagesPerPage">Images per page</label>
        <input type="text" name="imagesPerPage" value="<?php echo $imagesPerPage; ?>">
      </div>

      <div>
        <label for="page">Go to page</label>
        <input type="text" name="page" value="<?php echo $page; ?>">
      </div>

      <div>
        <input class="gallery-options-submit" type="submit" value="Go">
      </div>
    </form>
  </div>
</body>
</html>
