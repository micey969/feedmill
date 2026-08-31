<?php
  //Default page title if none is provided
  $pageTitle = $pageTitle ?? 'ECGC Feeds Production';
?>

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?php echo htmlspecialchars($pageTitle); ?></title>
  <link href="<?php echo htmlspecialchars(publicUrl('css/output.css')); ?>" rel="stylesheet">
</head>
<script src="https://unpkg.com/htmx.org@1.9.10"></script>