<?php

namespace App\Shared\Services;

final class HtmlSanitizer
{
    public function clean(string $html): string
    {
        if (class_exists(\HTMLPurifier::class)) {
            $config = \HTMLPurifier_Config::createDefault();
            $config->set('HTML.Allowed', 'p,br,strong,b,em,i,u,ul,ol,li,a[href|title|target|rel],h2,h3,h4,blockquote,code,pre,span,div,img[src|alt|width|height]');
            $config->set('Attr.AllowedFrameTargets', ['_blank']);
            $config->set('HTML.TargetBlank', true);
            $config->set('AutoFormat.RemoveEmpty', true);
            $config->set('URI.AllowedSchemes', [
                'http' => true,
                'https' => true,
                'mailto' => true,
            ]);

            return (new \HTMLPurifier($config))->purify($html);
        }

        $allowed = '<p><br><strong><b><em><i><u><ul><ol><li><a><h2><h3><h4><blockquote><code><pre><span><div><img>';
        $cleaned = strip_tags($html, $allowed);
        $cleaned = preg_replace('/\son\w+\s*=\s*("[^"]*"|\'[^\']*\'|[^\s>]+)/i', '', $cleaned) ?? $cleaned;

        return $cleaned;
    }
}
