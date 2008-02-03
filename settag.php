<?php
require('auth.php');

if (!isset($_GET['url']) || !isset($_GET['tag']) || !is_array($_GET['tag'])) {
    die("Param missing");
}

$url = stripslashes($_GET['url']);

$filename = 'feed.xml';

$dom = DOMDocument::load($filename);
$xpath = new DOMXPath($dom);
$xpath->registerNamespace('foo', 'http://www.w3.org/2005/Atom');
$entries = $xpath->query('//foo:entry/foo:id[. = "'.$url.'"]/..');
if ($entries->length == 0) {
    die("URL not found");
}

$entry = $entries->item(0);

foreach ($_GET['tag'] as $tag) {
    $tag = strtolower($tag);

    $cat = $dom->createElement('category');
    $cat->appendChild($dom->createTextnode($tag));

    $entry->appendChild($cat);
}

$dom->save($filename);