<?php

namespace Photonsh\PhotonPhp;

class Utils
{
    public static function compressGzip(string $text)
    {
        return gzencode($text);
    }

    public static function parseHtmlAsNodes(\DOMDocument $dom, string $html)
    {
        libxml_use_internal_errors(true);
        $dom->loadHTML($html, LIBXML_HTML_NODEFDTD);
        libxml_clear_errors();

        return $dom->getElementsByTagName('body')->item(0)->childNodes;
    }

    public static function parseNodesAsHtml(\DOMNodeList $nodes)
    {
        $html = [];

        foreach ($nodes as $node) {
            $html[] = $node->ownerDocument->saveHTML($node);
        }

        return implode($html);
    }
}
