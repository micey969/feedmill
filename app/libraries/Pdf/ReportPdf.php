<?php

namespace App\Libraries\Pdf;

use Dompdf\Dompdf;
use Dompdf\Options;
use RuntimeException;

final class ReportPdf
{
    public static function download(string $templateName, array $data, string $filename, string $orientation = 'portrait'): void
    {
        $pdf = self::render($templateName, $data, $orientation);
        $safeFilename = preg_replace('/[^A-Za-z0-9._-]/', '-', basename($filename));
        header('Content-Type: application/pdf');
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Cache-Control: private, max-age=0, must-revalidate');
        echo $pdf;
        exit;
    }

    public static function render(string $templateName, array $data, string $orientation = 'portrait'): string
    {
        if (!in_array($orientation, ['portrait', 'landscape'], true)) {
            throw new RuntimeException('Unsupported PDF orientation.');
        }

        $templates = ['standard-table.php', 'grouped-subtotal-table.php'];
        if (!in_array($templateName, $templates, true)) {
            throw new RuntimeException('Unknown PDF template.');
        }

        $autoloadPath = APP_PATH . '/../vendor/autoload.php';
        if (!is_file($autoloadPath)) {
            http_response_code(503);
            exit('PDF export is not configured. Run composer install in the project directory.');
        }
        require_once $autoloadPath;

        if (!class_exists(Dompdf::class)) {
            http_response_code(503);
            exit('The Dompdf package is not available. Run composer install in the project directory.');
        }

        $templatePath = APP_PATH . '/views/reports/pdf/' . $templateName;
        extract($data, EXTR_SKIP);
        ob_start();
        require $templatePath;
        $html = ob_get_clean();

        $options = new Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(
            '<style>@page { size: A4 ' . $orientation . '; margin: 15mm 12mm 20mm 12mm; }</style>' . $html,
            'UTF-8'
        );
        $dompdf->setPaper('A4', $orientation);
        $dompdf->render();

        $canvas = $dompdf->getCanvas();
        $canvas->page_text(
            $canvas->get_width() - 95,
            $canvas->get_height() - 24,
            'Page {PAGE_NUM} of {PAGE_COUNT}',
            'DejaVu Sans',
            8,
            [0.35, 0.35, 0.35]
        );
        return $dompdf->output();
    }
}