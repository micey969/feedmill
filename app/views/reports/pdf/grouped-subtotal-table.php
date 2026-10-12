<?php
$escape = static fn($value): string => htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
$alignment = static fn(string $align): string => in_array($align, ['left', 'center', 'right'], true) ? $align : 'left';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body { color: #1f2937; font-family: DejaVu Sans, sans-serif; font-size: 8pt; }
    .company-name { margin: 0 0 3px; color: #111827; font-size: 12pt; font-weight: bold; text-align: center; }
    h1 { margin: 0 0 5px; color: #b91c1c; font-size: 16pt; font-weight: 900; text-align: center; text-transform: uppercase; }
    .date-range { margin: 0 0 20px; color: #4b5563; text-align: center; }
    table { width: 100%; border-collapse: collapse; }
    thead { display: table-header-group; }
    th { padding: 7px 5px; border-bottom: 1px solid #9ca3af; background: #f3f4f6; color: #374151; font-size: 7pt; text-align: left; }
    td { padding: 6px 5px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
    tr { page-break-inside: avoid; }
    .group-heading td { padding-top: 9px; background: #f3f4f6; color: #b91c1c; font-weight: bold; }
    .subtotal td { border-top: 1px solid #fca5a5; background: #fef2f2; font-weight: bold; }
    .grand-total td { border-top: 2px solid #6b7280; background: #f9fafb; font-weight: bold; }
    .empty { padding: 18px 5px; color: #6b7280; text-align: center; }
    .meta { margin-top: 18px; padding-top: 7px; border-top: 1px solid #e5e7eb; color: #6b7280; font-size: 7pt; }
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
      <?php if (!$groups): ?>
        <tr><td class="empty" colspan="<?php echo count($columns); ?>"><?php echo $escape($emptyMessage); ?></td></tr>
      <?php else: ?>
        <?php foreach ($groups as $group): ?>
          <tr class="group-heading"><td colspan="<?php echo count($columns); ?>"><?php echo $escape($group['label']); ?></td></tr>
          <?php foreach ($group['rows'] as $row): ?>
            <tr>
              <?php foreach ($row as $index => $value): ?>
                <td style="text-align: <?php echo $alignment($columns[$index]['align'] ?? 'left'); ?>"><?php echo $escape($value); ?></td>
              <?php endforeach; ?>
            </tr>
          <?php endforeach; ?>
          <tr class="subtotal">
            <?php foreach ($group['subtotal'] as $index => $value): ?>
              <td style="text-align: <?php echo $alignment($columns[$index]['align'] ?? 'left'); ?>"><?php echo $escape($value); ?></td>
            <?php endforeach; ?>
          </tr>
        <?php endforeach; ?>
      <?php endif; ?>
      <tr class="grand-total">
        <?php foreach ($grandTotal as $index => $value): ?>
          <td style="text-align: <?php echo $alignment($columns[$index]['align'] ?? 'left'); ?>"><?php echo $escape($value); ?></td>
        <?php endforeach; ?>
      </tr>
    </tbody>
  </table>
  <div class="meta">Generated <?php echo $escape($generatedAt); ?></div>
</body>
</html>