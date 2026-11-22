<?php

declare(strict_types=1);

namespace App\Services;

use HTMLPurifier;
use HTMLPurifier_Config;
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\ExternalLink\ExternalLinkExtension;
use League\CommonMark\MarkdownConverter;

final class MarkdownService
{
    private MarkdownConverter $converter;

    private HTMLPurifier $purifier;

    public function __construct()
    {
        // The rich-text editor stores HTML; older tickets store Markdown. Let
        // CommonMark pass HTML through and rely on HTMLPurifier below as the
        // single, allow-list based security boundary for both formats.
        $environment = new Environment([
            'html_input' => 'allow',
            'allow_unsafe_links' => false,
            'max_nesting_level' => 20,
            'external_link' => [
                'internal_hosts' => [],
                'open_in_new_window' => false,
                'nofollow' => 'all',
                'noopener' => 'all',
                'noreferrer' => 'all',
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension);
        $environment->addExtension(new ExternalLinkExtension);
        $this->converter = new MarkdownConverter($environment);

        $config = HTMLPurifier_Config::createDefault();
        $config->set('Cache.DefinitionImpl', null);
        $config->set('HTML.Allowed', 'p,br,strong,em,del,s,u,code,pre,blockquote,ul,ol,li,h1,h2,h3,h4,h5,h6,a[href|title|rel],table,thead,tbody,tr,th,td,hr,img[src|alt|title]');
        $config->set('URI.AllowedSchemes', ['http' => true, 'https' => true, 'mailto' => true]);
        $config->set('Attr.AllowedRel', ['nofollow', 'noreferrer', 'noopener']);
        $config->set('Attr.EnableID', false);
        $config->set('HTML.TargetBlank', false);
        $this->purifier = new HTMLPurifier($config);
    }

    public function render(string $markdown): string
    {
        $html = (string) $this->converter->convert($markdown);

        return $this->purifier->purify($html);
    }
}
