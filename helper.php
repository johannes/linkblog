<?php
function createNode(DOMDocument $targetdoc, DOMNode $targetnode, DOMNode $entry, $with_content = true)
{
    $a = $targetdoc->createElement('a');
    $targetnode->appendChild($a);
    $a->setAttribute('href', $entry->getElementsByTagName('id')->item(0)->nodeValue);

    $a->appendChild($targetdoc->createElement('b',
        date('Y-m-d H:i:s', strtotime($entry->getElementsByTagName('updated')->item(0)->nodeValue)).': '));

    $a->appendChild($targetdoc->createTextNode($entry->getElementsByTagName('title')->item(0)->nodeValue));

    if ($with_content && ($contents = $entry->getElementsByTagName('content')) && $contents->length) {
        $div = $targetdoc->createElement('div');
        $targetnode->appendChild($div);
        $div->appendChild($targetdoc->importNode($contents->item(0), true));
    }
}
