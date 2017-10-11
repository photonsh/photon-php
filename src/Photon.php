<?php

namespace Photonsh\PhotonPhp;

use Photonsh\PhotonPhp\Highlighter;

class Photon
{
    private $apiKey = '';

    public function setup(array $options = array())
    {
        if (array_key_exists('apiKey', $options)) {
            $this->apiKey = $options['apiKey'];
        }
    }

    public function highlight(string $document = '', array $options = array())
    {
        if ($this->apiKey === '' && !array_key_exists('apiKey', $options)) {
            throw new \Exception('Missing API Key.');
        }

        $highlighter = new Highlighter(array_merge(['apiKey' => $this->apiKey], $options));

        return $highlighter->highlight($document);
    }
}
