<?php

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

include '../HTML/guidance-detail.html';
