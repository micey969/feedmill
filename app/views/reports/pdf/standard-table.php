<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$alignment = static fn(string $align): string => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 9pt; }
    .company-name { margin: 0 0 3px; color: #111827; font-size: 12pt; font-weight: bold; text-align: center; }
    h1 { margin: 0 0 5px; color: #b91c1c; font-size: 17pt; font-weight: 900; text-align: center; text-transform: uppercase; }
    .date-range { margin: 0 0 22px; color: #4b5563; text-align: center; }
    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    tfoot { display: table-row-group; }
    th { padding: 8px 7px; border-bottom: 1px solid #9ca3af; background: #f3f4f6; color: #374151; font-size: 8pt; text-align: left; }
    td { padding: 7px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    tr { page-break-inside: avoid; }
    .empty { padding: 18px 7px; color: #6b7280; text-align: center; }
    tfoot td { border-top: 2px solid #6b7280; border-bottom: 0; background: #f9fafb; font-weight: bold; }
    .meta { margin-top: 18px; padding-top: 7px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 8pt; }
  </style>
</head>
<body>
  <h2 class="company-name">East Caribbean Feeds Limited</h2>
  <h1><?php echo $escape($reportTitle); ?></h1>
  <p class="date-range">From <?php echo $escape($dateRange['start']); ?> to <?php echo $escape($dateRange['end']); ?></p>
  <table>
    <thead>
      <tr>
        <?php foreach ($columns as $column): ?>
          <th style="text-align: <?php echo $alignment($column['align'] ?? 'left'); ?>"><?php echo $escape($column['label']); ?></th>
        <?php endforeach; ?>
      </tr>
    </thead>
    <tbody>
      <?php if (!$rows): ?>
        <tr><td class="empty" colspan="<?php echo count($columns); ?>"><?php echo $escape($emptyMessage); ?></td></tr>
      <?php else: ?>
        <?php foreach ($rows as $row): ?>
          <tr>
            <?php foreach ($row as $index => $value): ?>
              <td style="text-align: <?php echo $alignment($columns[$index]['align'] ?? 'left'); ?>"><?php echo $escape($value); ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
    </tbody>
    <?php if ($totals): ?>
      <tfoot>
        <tr>
          <?php foreach ($totals as $index => $value): ?>
            <td style="text-align: <?php echo $alignment($columns[$index]['align'] ?? 'left'); ?>"><?php echo $escape($value); ?></td>
          <?php endforeach; ?>
        </tr>
      </tfoot>
    <?php endif; ?>
  </table>
  <div class="meta">Generated <?php echo $escape($generatedAt); ?></div>
</body>
</html>