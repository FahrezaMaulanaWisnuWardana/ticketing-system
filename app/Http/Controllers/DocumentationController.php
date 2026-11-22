<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;

/**
 * Renders the single-page user guide inside the authenticated app layout.
 * The guide itself lives in docs/ as a standalone HTML file; this controller
 * reuses its CSS and body, inlines its screenshots and scopes every CSS rule
 * to .doc-page so the app chrome keeps its own styling.
 */
final class DocumentationController
{
    public function __invoke(): View
    {
        $path = base_path('docs/PANDUAN-PENGGUNAAN.html');
        abort_unless(is_file($path), 404);

        $html = (string) file_get_contents($path);
        $html = str_replace('http://localhost:8000', url('/'), $html);

        foreach (glob(base_path('docs/screenshots/*.png')) ?: [] as $image) {
            $html = str_replace(
                'screenshots/'.basename($image),
                'data:image/png;base64,'.base64_encode((string) file_get_contents($image)),
                $html,
            );
        }

        preg_match('#<style>(.*?)</style>#s', $html, $styleMatch);
        preg_match('#<body[^>]*>(.*)</body>#s', $html, $bodyMatch);

        return view('docs.panduan', [
            'css' => $this->scopeCss($styleMatch[1] ?? '', '.doc-page'),
            'content' => $bodyMatch[1] ?? '',
        ]);
    }

    /** Prefix every selector with $scope, leaving keyframes and at-rules intact. */
    private function scopeCss(string $css, string $scope): string
    {
        $out = '';
        $length = strlen($css);
        $i = 0;

        while ($i < $length) {
            $open = strpos($css, '{', $i);
            if ($open === false) {
                $out .= substr($css, $i);
                break;
            }

            $head = trim(substr($css, $i, $open - $i));
            $depth = 1;
            $j = $open + 1;
            while ($j < $length && $depth > 0) {
                $char = $css[$j];
                if ($char === '{') {
                    $depth++;
                } elseif ($char === '}') {
                    $depth--;
                }
                $j++;
            }

            $body = substr($css, $open + 1, $j - $open - 2);

            if (str_starts_with($head, '@media') || str_starts_with($head, '@supports')) {
                $out .= $head.'{'.$this->scopeCss($body, $scope).'}';
            } elseif (str_starts_with($head, '@keyframes')) {
                $out .= $head.'{'.$body.'}';
            } elseif ($head !== '') {
                $selectors = array_map('trim', explode(',', $head));
                $scoped = array_map(static fn (string $selector): string => $selector === ':root'
                    ? $scope
                    : $scope.' '.$selector, $selectors);
                $out .= implode(', ', $scoped).'{'.$body.'}';
            }

            $i = $j;
        }

        return $out;
    }
}
