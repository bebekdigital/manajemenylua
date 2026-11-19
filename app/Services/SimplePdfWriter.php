<?php

namespace App\Services;

/**
 * Lightweight zero-dependency PDF 1.4 generator supporting A4 layout,
 * Base-14 Times fonts (Times-Roman, Times-Bold, Times-Italic),
 * vector shapes, accurate text wrapping, and deduplicated JPEG image XObjects.
 */
class SimplePdfWriter
{
    private const PT_PER_MM = 72.0 / 25.4;

    private const PAGE_WIDTH_MM = 210.0;

    private const PAGE_HEIGHT_MM = 297.0;

    /** @var array<int, string> */
    private array $pages = [];

    private int $currentPage = -1;

    /** @var array<string, array{index: int, name: string, w: int, h: int, data: string}> */
    private array $images = [];

    private string $currentFont = 'F1';

    private float $currentFontSize = 10.0;

    private string $fillColorCmd = '0 0 0 rg';

    private string $drawColorCmd = '0 0 0 RG';

    private float $lineWidthPt = 0.5;

    /**
     * Standard Adobe AFM character widths (1/1000 em) for Times-Roman (ASCII 32-126).
     *
     * @var array<int, int>
     */
    private const WIDTHS_TIMES = [
        32 => 250, 33 => 333, 34 => 408, 35 => 500, 36 => 500, 37 => 833, 38 => 778, 39 => 333,
        40 => 333, 41 => 333, 42 => 500, 43 => 564, 44 => 250, 45 => 333, 46 => 250, 47 => 278,
        48 => 500, 49 => 500, 50 => 500, 51 => 500, 52 => 500, 53 => 500, 54 => 500, 55 => 500,
        56 => 500, 57 => 500, 58 => 278, 59 => 278, 60 => 564, 61 => 564, 62 => 564, 63 => 444,
        64 => 921, 65 => 722, 66 => 667, 67 => 667, 68 => 722, 69 => 611, 70 => 556, 71 => 722,
        72 => 722, 73 => 333, 74 => 389, 75 => 722, 76 => 611, 77 => 889, 78 => 722, 79 => 722,
        80 => 556, 81 => 722, 82 => 667, 83 => 556, 84 => 611, 85 => 722, 86 => 722, 87 => 944,
        88 => 722, 89 => 722, 90 => 611, 91 => 333, 92 => 278, 93 => 333, 94 => 469, 95 => 500,
        96 => 333, 97 => 444, 98 => 500, 99 => 444, 100 => 500, 101 => 444, 102 => 333, 103 => 500,
        104 => 500, 105 => 278, 106 => 278, 107 => 500, 108 => 278, 109 => 778, 110 => 500, 111 => 500,
        112 => 500, 113 => 500, 114 => 333, 115 => 389, 116 => 278, 117 => 500, 118 => 500, 119 => 722,
        120 => 500, 121 => 500, 122 => 444, 123 => 480, 124 => 200, 125 => 480, 126 => 541,
    ];

    /**
     * Standard Adobe AFM character widths (1/1000 em) for Times-Bold (ASCII 32-126).
     *
     * @var array<int, int>
     */
    private const WIDTHS_TIMES_BOLD = [
        32 => 250, 33 => 333, 34 => 555, 35 => 500, 36 => 500, 37 => 1000, 38 => 833, 39 => 333,
        40 => 333, 41 => 333, 42 => 500, 43 => 570, 44 => 250, 45 => 333, 46 => 250, 47 => 278,
        48 => 500, 49 => 500, 50 => 500, 51 => 500, 52 => 500, 53 => 500, 54 => 500, 55 => 500,
        56 => 500, 57 => 500, 58 => 333, 59 => 333, 60 => 570, 61 => 570, 62 => 570, 63 => 500,
        64 => 930, 65 => 722, 66 => 667, 67 => 722, 68 => 722, 69 => 667, 70 => 611, 71 => 778,
        72 => 778, 73 => 389, 74 => 500, 75 => 778, 76 => 667, 77 => 944, 78 => 722, 79 => 778,
        80 => 611, 81 => 778, 82 => 722, 83 => 556, 84 => 667, 85 => 722, 86 => 722, 87 => 1000,
        88 => 722, 89 => 722, 90 => 667, 91 => 333, 92 => 278, 93 => 333, 94 => 581, 95 => 500,
        96 => 333, 97 => 500, 98 => 556, 99 => 444, 100 => 556, 101 => 444, 102 => 333, 103 => 500,
        104 => 556, 105 => 278, 106 => 333, 107 => 556, 108 => 278, 109 => 833, 110 => 556, 111 => 500,
        112 => 556, 113 => 556, 114 => 444, 115 => 389, 116 => 333, 117 => 556, 118 => 500, 119 => 722,
        120 => 500, 121 => 500, 122 => 444, 123 => 394, 124 => 220, 125 => 394, 126 => 520,
    ];

    /**
     * Add a new A4 portrait page to the document.
     */
    public function addPage(): void
    {
        $this->pages[] = '';
        $this->currentPage = count($this->pages) - 1;
        $this->appendRaw(sprintf("%.4f w\n%s\n%s\n", $this->lineWidthPt, $this->drawColorCmd, $this->fillColorCmd));
    }

    /**
     * Set text / fill RGB color (0-255).
     */
    public function setTextColor(int $r, int $g, int $b): void
    {
        $this->fillColorCmd = sprintf('%.3f %.3f %.3f rg', $r / 255, $g / 255, $b / 255);
        if ($this->currentPage >= 0) {
            $this->appendRaw($this->fillColorCmd."\n");
        }
    }

    /**
     * Set stroke RGB color (0-255).
     */
    public function setDrawColor(int $r, int $g, int $b): void
    {
        $this->drawColorCmd = sprintf('%.3f %.3f %.3f RG', $r / 255, $g / 255, $b / 255);
        if ($this->currentPage >= 0) {
            $this->appendRaw($this->drawColorCmd."\n");
        }
    }

    /**
     * Set fill RGB color (0-255) for shapes.
     */
    public function setFillColor(int $r, int $g, int $b): void
    {
        $this->setTextColor($r, $g, $b);
    }

    /**
     * Set stroke line width in millimeters.
     */
    public function setLineWidth(float $mm): void
    {
        $this->lineWidthPt = $mm * self::PT_PER_MM;
        if ($this->currentPage >= 0) {
            $this->appendRaw(sprintf("%.4f w\n", $this->lineWidthPt));
        }
    }

    /**
     * Draw a rectangle in millimeters from top-left origin.
     * Style: 'D' (stroke), 'F' (fill), 'B' (fill + stroke).
     */
    public function rect(float $xMm, float $yMm, float $wMm, float $hMm, string $style = 'D'): void
    {
        $op = match ($style) {
            'F' => 'f',
            'B', 'DF', 'FD' => 'B',
            default => 'S',
        };

        $xPt = $this->mmToPt($xMm);
        $yBottomPt = $this->yToPdfPt($yMm + $hMm);
        $wPt = $this->mmToPt($wMm);
        $hPt = $this->mmToPt($hMm);

        $this->appendRaw(sprintf("%.4f %.4f %.4f %.4f re %s\n", $xPt, $yBottomPt, $wPt, $hPt, $op));
    }

    /**
     * Draw a straight line in millimeters from top-left origin.
     */
    public function line(float $x1Mm, float $y1Mm, float $x2Mm, float $y2Mm): void
    {
        $this->appendRaw(sprintf(
            "%.4f %.4f m %.4f %.4f l S\n",
            $this->mmToPt($x1Mm),
            $this->yToPdfPt($y1Mm),
            $this->mmToPt($x2Mm),
            $this->yToPdfPt($y2Mm)
        ));
    }

    /**
     * Calculate exact string width in millimeters.
     */
    public function getStringWidth(string $text, string $fontKey = 'F1', float $sizePt = 10.0): float
    {
        $encoded = $this->toWinAnsi($text);
        $widths = ($fontKey === 'F2') ? self::WIDTHS_TIMES_BOLD : self::WIDTHS_TIMES;
        $totalThousandths = 0;

        $len = strlen($encoded);
        for ($i = 0; $i < $len; $i++) {
            $ord = ord($encoded[$i]);
            $totalThousandths += $widths[$ord] ?? 500;
        }

        $widthPt = ($totalThousandths / 1000.0) * $sizePt;

        return $widthPt / self::PT_PER_MM;
    }

    /**
     * Draw a single-line text at (xMm, baselineYmm) or aligned inside box width wMm.
     */
    public function textAligned(
        float $xMm,
        float $yMm,
        float $wMm,
        string $text,
        string $fontKey = 'F1',
        float $sizePt = 10.0,
        string $align = 'L',
        bool $underline = false
    ): void {
        $textWidthMm = $this->getStringWidth($text, $fontKey, $sizePt);
        $drawX = $xMm;

        if ($align === 'C') {
            $drawX = $xMm + max(0.0, ($wMm - $textWidthMm) / 2.0);
        } elseif ($align === 'R') {
            $drawX = $xMm + max(0.0, $wMm - $textWidthMm);
        }

        $this->currentFont = $fontKey;
        $this->currentFontSize = $sizePt;

        $escaped = $this->escapePdfString($this->toWinAnsi($text));
        $xPt = $this->mmToPt($drawX);
        $yPt = $this->yToPdfPt($yMm);

        $this->appendRaw(sprintf(
            "BT /%s %.2f Tf %.4f %.4f Td (%s) Tj ET\n",
            $fontKey,
            $sizePt,
            $xPt,
            $yPt,
            $escaped
        ));

        if ($underline && trim($text) !== '') {
            $lineY = $yMm + ($sizePt * 0.14 / self::PT_PER_MM) + 0.4;
            $savedWidth = $this->lineWidthPt;
            $this->setLineWidth(max(0.2, $sizePt * 0.025));
            $this->line($drawX, $lineY, $drawX + $textWidthMm, $lineY);
            $this->lineWidthPt = $savedWidth;
            $this->appendRaw(sprintf("%.4f w\n", $this->lineWidthPt));
        }
    }

    /**
     * Word-wrap text into lines that fit inside maxWidthMm.
     *
     * @return array<int, string>
     */
    public function wrapText(string $text, float $maxWidthMm, string $fontKey = 'F1', float $sizePt = 10.0): array
    {
        $paragraphs = preg_split('/\r\n|\r|\n/', $text) ?: [$text];
        $lines = [];

        foreach ($paragraphs as $para) {
            $para = trim($para);
            if ($para === '') {
                $lines[] = '';

                continue;
            }

            $words = preg_split('/\s+/', $para) ?: [$para];
            $current = '';

            foreach ($words as $word) {
                $candidate = $current === '' ? $word : $current.' '.$word;
                if ($this->getStringWidth($candidate, $fontKey, $sizePt) <= $maxWidthMm) {
                    $current = $candidate;
                } else {
                    if ($current !== '') {
                        $lines[] = $current;
                    }
                    // Handle single word wider than maxWidthMm
                    if ($this->getStringWidth($word, $fontKey, $sizePt) > $maxWidthMm) {
                        $chunk = '';
                        $chars = preg_split('//u', $word, -1, PREG_SPLIT_NO_EMPTY) ?: [$word];
                        foreach ($chars as $ch) {
                            if ($this->getStringWidth($chunk.$ch, $fontKey, $sizePt) <= $maxWidthMm) {
                                $chunk .= $ch;
                            } else {
                                if ($chunk !== '') {
                                    $lines[] = $chunk;
                                }
                                $chunk = $ch;
                            }
                        }
                        $current = $chunk;
                    } else {
                        $current = $word;
                    }
                }
            }

            if ($current !== '') {
                $lines[] = $current;
            }
        }

        return $lines ?: [''];
    }

    /**
     * Draw wrapped multi-line text and return total height consumed in mm.
     */
    public function multiLineText(
        float $xMm,
        float $startYmm,
        float $wMm,
        float $lineHeightMm,
        string $text,
        string $fontKey = 'F1',
        float $sizePt = 10.0,
        string $align = 'L'
    ): float {
        $lines = $this->wrapText($text, $wMm, $fontKey, $sizePt);
        $y = $startYmm;

        foreach ($lines as $line) {
            $this->textAligned($xMm, $y, $wMm, $line, $fontKey, $sizePt, $align);
            $y += $lineHeightMm;
        }

        return count($lines) * $lineHeightMm;
    }

    /**
     * Embed an image file (JPEG, PNG, WEBP, GIF) at (xMm, yMm, wMm, hMm).
     * Automatically deduplicates identical rendered images across pages/students.
     */
    public function addImage(
        string $filePath,
        float $xMm,
        float $yMm,
        float $wMm,
        float $hMm,
        bool $cropCover = false,
        bool $keepAspectContain = true
    ): bool {
        if (! extension_loaded('gd') || ! is_file($filePath)) {
            return false;
        }

        $targetRatio = $wMm / max(0.1, $hMm);
        $cacheKey = md5($filePath.'|'.($cropCover ? 'cover:'.round($targetRatio, 3) : 'orig'));

        if (! isset($this->images[$cacheKey])) {
            $raw = @file_get_contents($filePath);
            if ($raw === false) {
                return false;
            }

            $src = @imagecreatefromstring($raw);
            if (! $src) {
                return false;
            }

            $srcW = imagesx($src);
            $srcH = imagesy($src);

            if ($cropCover) {
                // Center-crop to exact target aspect ratio and resize to ~300 DPI for crisp 3x4 printing
                $srcRatio = $srcW / max(1, $srcH);
                if ($srcRatio > $targetRatio) {
                    $cropH = $srcH;
                    $cropW = (int) round($srcH * $targetRatio);
                    $cropX = (int) floor(($srcW - $cropW) / 2);
                    $cropY = 0;
                } else {
                    $cropW = $srcW;
                    $cropH = (int) round($srcW / $targetRatio);
                    $cropX = 0;
                    $cropY = (int) floor(($srcH - $cropH) / 2);
                }

                $destW = min(480, max(300, $cropW));
                $destH = (int) round($destW / $targetRatio);
                $canvas = imagecreatetruecolor($destW, $destH);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
                imagecopyresampled($canvas, $src, 0, 0, $cropX, $cropY, $destW, $destH, $cropW, $cropH);
                $outW = $destW;
                $outH = $destH;
            } else {
                // Composite onto pure white canvas (handles transparent PNG logos cleanly)
                $maxDim = 800;
                if ($srcW > $maxDim || $srcH > $maxDim) {
                    $scale = min($maxDim / $srcW, $maxDim / $srcH);
                    $outW = max(1, (int) round($srcW * $scale));
                    $outH = max(1, (int) round($srcH * $scale));
                } else {
                    $outW = $srcW;
                    $outH = $srcH;
                }

                $canvas = imagecreatetruecolor($outW, $outH);
                $white = imagecolorallocate($canvas, 255, 255, 255);
                imagefill($canvas, 0, 0, $white);
                imagecopyresampled($canvas, $src, 0, 0, 0, 0, $outW, $outH, $srcW, $srcH);
            }

            ob_start();
            imagejpeg($canvas, null, 92);
            $jpegData = ob_get_clean();

            imagedestroy($src);
            imagedestroy($canvas);

            if (! is_string($jpegData) || $jpegData === '') {
                return false;
            }

            $idx = count($this->images) + 1;
            $this->images[$cacheKey] = [
                'index' => $idx,
                'name' => 'Im'.$idx,
                'w' => $outW,
                'h' => $outH,
                'data' => $jpegData,
            ];
        }

        $img = $this->images[$cacheKey];
        $drawW = $wMm;
        $drawH = $hMm;
        $drawX = $xMm;
        $drawY = $yMm;

        if (! $cropCover && $keepAspectContain) {
            $imgRatio = $img['w'] / max(1, $img['h']);
            if ($wMm / max(0.1, $hMm) > $imgRatio) {
                $drawW = $hMm * $imgRatio;
                $drawX = $xMm + ($wMm - $drawW) / 2.0;
            } else {
                $drawH = $wMm / $imgRatio;
                $drawY = $yMm + ($hMm - $drawH) / 2.0;
            }
        }

        $xPt = $this->mmToPt($drawX);
        $yBottomPt = $this->yToPdfPt($drawY + $drawH);
        $wPt = $this->mmToPt($drawW);
        $hPt = $this->mmToPt($drawH);

        $this->appendRaw(sprintf(
            "q %.4f 0 0 %.4f %.4f %.4f cm /%s Do Q\n",
            $wPt,
            $hPt,
            $xPt,
            $yBottomPt,
            $img['name']
        ));

        return true;
    }

    /**
     * Compile and return the binary PDF 1.4 output string.
     */
    public function output(): string
    {
        if (empty($this->pages)) {
            $this->addPage();
        }

        $objects = [];
        $addObj = function (string $body) use (&$objects): int {
            $objects[] = $body;

            return count($objects); // 1-based PDF object ID
        };

        // 1: Catalog (placeholder updated later), 2: Pages (placeholder updated later)
        $catalogId = $addObj('');
        $pagesId = $addObj('');

        // 3, 4, 5: Built-in Base-14 Times fonts
        $f1Id = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Roman /Encoding /WinAnsiEncoding >>');
        $f2Id = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Bold /Encoding /WinAnsiEncoding >>');
        $f3Id = $addObj('<< /Type /Font /Subtype /Type1 /BaseFont /Times-Italic /Encoding /WinAnsiEncoding >>');

        // Image XObjects
        $xObjectEntries = [];
        foreach ($this->images as $img) {
            $imgBody = sprintf(
                "<< /Type /XObject /Subtype /Image /Width %d /Height %d /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length %d >>\nstream\n%s\nendstream",
                $img['w'],
                $img['h'],
                strlen($img['data']),
                $img['data']
            );
            $imgObjId = $addObj($imgBody);
            $xObjectEntries[] = sprintf('/%s %d 0 R', $img['name'], $imgObjId);
        }

        $xObjectDict = ! empty($xObjectEntries) ? ' /XObject << '.implode(' ', $xObjectEntries).' >>' : '';
        $resourcesDict = sprintf(
            '<< /Font << /F1 %d 0 R /F2 %d 0 R /F3 %d 0 R >>%s >>',
            $f1Id,
            $f2Id,
            $f3Id,
            $xObjectDict
        );

        $pageWidthPt = $this->mmToPt(self::PAGE_WIDTH_MM);
        $pageHeightPt = $this->mmToPt(self::PAGE_HEIGHT_MM);
        $useCompression = function_exists('gzcompress');

        $pageKids = [];
        foreach ($this->pages as $stream) {
            $contentData = $useCompression ? (gzcompress($stream, 6) ?: $stream) : $stream;
            $filter = $useCompression && $contentData !== $stream ? ' /Filter /FlateDecode' : '';

            $contentObjId = $addObj(sprintf(
                "<< /Length %d%s >>\nstream\n%s\nendstream",
                strlen($contentData),
                $filter,
                $contentData
            ));

            $pageObjId = $addObj(sprintf(
                '<< /Type /Page /Parent %d 0 R /MediaBox [0 0 %.4f %.4f] /Resources %s /Contents %d 0 R >>',
                $pagesId,
                $pageWidthPt,
                $pageHeightPt,
                $resourcesDict,
                $contentObjId
            ));

            $pageKids[] = $pageObjId.' 0 R';
        }

        $objects[$catalogId - 1] = sprintf('<< /Type /Catalog /Pages %d 0 R >>', $pagesId);
        $objects[$pagesId - 1] = sprintf('<< /Type /Pages /Kids [%s] /Count %d >>', implode(' ', $pageKids), count($pageKids));

        // Assemble PDF binary with xref offsets
        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0];

        foreach ($objects as $index => $body) {
            $objNum = $index + 1;
            $offsets[$objNum] = strlen($pdf);
            $pdf .= $objNum." 0 obj\n".$body."\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $totalObjects = count($objects) + 1;
        $pdf .= "xref\n0 {$totalObjects}\n0000000000 65535 f \n";

        for ($i = 1; $i < $totalObjects; $i++) {
            $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
        }

        $pdf .= sprintf(
            "trailer\n<< /Size %d /Root %d 0 R >>\nstartxref\n%d\n%%%%EOF",
            $totalObjects,
            $catalogId,
            $xrefOffset
        );

        return $pdf;
    }

    private function mmToPt(float $mm): float
    {
        return $mm * self::PT_PER_MM;
    }

    private function yToPdfPt(float $yMm): float
    {
        return (self::PAGE_HEIGHT_MM - $yMm) * self::PT_PER_MM;
    }

    private function appendRaw(string $cmd): void
    {
        if ($this->currentPage < 0) {
            $this->addPage();
        }
        $this->pages[$this->currentPage] .= $cmd;
    }

    private function toWinAnsi(string $text): string
    {
        $normalized = str_replace(
            ["\u{2018}", "\u{2019}", "\u{201C}", "\u{201D}", "\u{2013}", "\u{2014}", "\u{2022}", "\u{00A0}"],
            ["'", "'", '"', '"', '-', '-', '-', ' '],
            $text
        );

        $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $normalized);

        return is_string($converted) ? $converted : $normalized;
    }

    private function escapePdfString(string $str): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $str);
    }
}

