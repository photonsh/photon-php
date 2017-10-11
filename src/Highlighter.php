<?php

namespace Photonsh\PhotonPhp;

use Photonsh\PhotonPhp\Utils;

use \GuzzleHttp\Client;
use \GuzzleHttp\Psr7\Request;
use \GuzzleHttp\Exception\ClientException;

class Highlighter
{
    private $client;

    private $apiKey;

    private $dom;

    public function __construct(array $options)
    {
        $this->client = new Client();

        $this->apiKey = $options['apiKey'];

        $this->dom = new \DOMDocument();
    }

    private function validSnippet(\DOMNode $node)
    {
        if (
            $node->nodeName === 'pre'
            && $node->hasChildNodes()
            && $node->childNodes->length === 1
            && ($node->childNodes->item(0)->nodeName === 'code' || $node->childNodes->item(0)->nodeName === 'samp')
            && $node->childNodes->item(0)->hasAttributes()
            && $node->childNodes->item(0)->hasAttribute('class')
            && preg_match('/\blang(?:uage)?-([\w-]+)\b/i', $node->childNodes->item(0)->getAttribute('class'))
            && $node->childNodes->item(0)->hasChildNodes()
            && $node->childNodes->item(0)->childNodes->length === 1
            && $node->childNodes->item(0)->childNodes->item(0)->nodeValue !== ''
        ) {
            return true;
        }

        return false;
    }

    private function sendSnippet(string $snippet)
    {
        $compressedSnippetBuffer = Utils::compressGzip($snippet);

        $headers = [
            'Authorization' => "Token $this->apiKey",
            'Content-Type' => 'text/html',
            'Content-Encoding' => 'gzip',
            'Accept-Encoding' => 'gzip',
            'Library' => 'php'
        ];

        $request = new Request('POST', 'https://api.photon.sh/snippets', $headers, $compressedSnippetBuffer);

        try {
            $response = $this->client->send($request);
        } catch (ClientException $e) {
            throw new \Photonsh\PhotonPhp\Exception\ClientException($e->getResponse()->getBody()->getContents());
        }

        return $response->getBody();
    }

    private function walkNode(\DOMNode $node)
    {
        if ($this->validSnippet($node)) {
            $highlightedSnippet = (string)$this->sendSnippet(Utils::parseNodesAsHtml($node->childNodes));

            $fragment = $this->dom->createDocumentFragment();
            $fragment->appendXML($highlightedSnippet);


            if ($fragment->childNodes->item(0)->nodeName === 'div') {
                $node->parentNode->replaceChild($fragment, $node);
            } else {
                $node->replaceChild($fragment, $node->childNodes->item(0));
            }
        } elseif ($node->nodeName !== 'pre' && $node->hasChildNodes()) {
            foreach ($node->childNodes as $deepNode) {
                $this->walkNode($deepNode);
            }
        }
    }

    public function highlight(string $document)
    {
        $nodes = Utils::parseHtmlAsNodes($this->dom, $document);

        foreach ($nodes as $node) {
            $this->walkNode($node);
        }

        return Utils::parseNodesAsHtml($nodes);
    }
}
