<?php
// guidance-detail.php: Fetch and display guidance details by ID.
// Expected DB schema (example):
// Table: guidance_topics
//   id INT PRIMARY KEY
//   title VARCHAR(255)
//   description TEXT (short)
//   content LONGTEXT (HTML or markdown rendered server-side)
//   images JSON (array of relative URLs)
//   youtube_links JSON (array of YouTube URLs)
//   howto JSON (array of step strings)

require_once __DIR__ . '/../php/config.php';

function fetchGuideById(mysqli $conn, $id) {
  $stmt = $conn->prepare('SELECT id, title, description, content, images, youtube_links, howto FROM guidance_topics WHERE id = ? LIMIT 1');
  if (!$stmt) {
    throw new Exception('Prepare failed: ' . $conn->error);
  }
  $stmt->bind_param('i', $id);
  $stmt->execute();
  $result = $stmt->get_result();
  $row = $result->fetch_assoc();
  $stmt->close();
  return $row ?: null;
}

function e($s) { return htmlspecialchars($s ?? '', ENT_QUOTES, 'UTF-8'); }

function embedYouTube($url) {
  // Accept typical forms and extract video id
  $id = '';
  if (preg_match('~(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|v/))([A-Za-z0-9_-]{11})~', $url, $m)) {
    $id = $m[1];
  }
  if ($id === '') return '';
  return '<div class="video-embed"><iframe src="https://www.youtube.com/embed/' . e($id) . '" title="YouTube video" frameborder="0" allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share" allowfullscreen></iframe></div>';
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;
$guide = null;
$error = '';

if ($id <= 0) {
  $error = 'Invalid guide ID.';
} else {
  try {
    $guide = fetchGuideById($conn, $id);
    if (!$guide) {
      $error = 'Guide not found.';
    }
  } catch (Throwable $t) {
    $error = 'Error fetching guide: ' . $t->getMessage();
  }
}

$images = [];
$videos = [];
$howto = [];

if ($guide) {
  $images = json_decode($guide['images'] ?? '[]', true) ?: [];
  $videos = json_decode($guide['youtube_links'] ?? '[]', true) ?: [];
  $howto = json_decode($guide['howto'] ?? '[]', true) ?: [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo e($guide['title'] ?? 'Guidance Detail'); ?> - AgriHub</title>
  <link rel="stylesheet" href="../Css/header.css">
  <link rel="stylesheet" href="../Css/footer.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
  <link rel="stylesheet" href="../Css/guidance-detail.css">
</head>
<body>
  <div id="header-placeholder"></div>

  <main>
    <div class="container">
      <?php if ($error): ?>
        <div class="error-box"><?php echo e($error); ?></div>
      <?php else: ?>
        <article class="guide-detail">
          <header class="guide-header">
            <h1><?php echo e($guide['title']); ?></h1>
            <?php if (!empty($guide['description'])): ?>
            <p class="muted"><?php echo e($guide['description']); ?></p>
            <?php endif; ?>
          </header>

          <?php if (!empty($images)): ?>
            <section class="image-gallery">
              <?php foreach ($images as $src): ?>
                <figure class="image-item">
                  <img src="<?php echo e($src); ?>" alt="Image for <?php echo e($guide['title']); ?>">
                </figure>
              <?php endforeach; ?>
            </section>
          <?php endif; ?>

          <section class="guide-content">
            <?php // Render content as-is. If markdown is used, convert before storing. ?>
            <?php echo $guide['content']; ?>
          </section>

          <?php if (!empty($howto)): ?>
            <section class="howto-section">
              <h2>How-To Steps</h2>
              <ol class="howto-list">
                <?php foreach ($howto as $step): ?>
                  <li><?php echo e($step); ?></li>
                <?php endforeach; ?>
              </ol>
            </section>
          <?php endif; ?>

          <?php if (!empty($videos)): ?>
            <section class="videos">
              <h2>Videos</h2>
              <div class="video-grid">
                <?php foreach ($videos as $url): ?>
                  <?php echo embedYouTube($url); ?>
                <?php endforeach; ?>
              </div>
            </section>
          <?php endif; ?>
        </article>
      <?php endif; ?>
    </div>
  </main>

  <div id="footer-placeholder"></div>

  <script type="module" src="../Js/site.js"></script>
</body>
</html>
