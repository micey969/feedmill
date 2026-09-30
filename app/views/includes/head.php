<?php
  //Default page title if none is provided
  $pageTitle = $pageTitle ?? 'ECGC Feeds Production';
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="<?php echo htmlspecialchars(publicUrl('assets/css/output.css')); ?>" rel="stylesheet">
  <script>window.feedmillPublicUrl = <?php echo json_encode(rtrim(publicUrl(), '/'), JSON_UNESCAPED_SLASHES); ?>;</script>
  <script src="<?php echo htmlspecialchars(publicUrl('assets/js/timeout.js')); ?>"></script>
  <script src="<?php echo htmlspecialchars(publicUrl('assets/js/modals.js')); ?>"></script>
  <script src="https://unpkg.com/htmx.org@1.9.10"></script>
  <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.20/build/pdfmake.min.js"></script>
  <script src="https://cdn.jsdelivr.net/npm/pdfmake@0.2.20/build/vfs_fonts.js"></script>
</head>
