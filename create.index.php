<?php
require './helper.php';

$template = DOMDocument::loadHTMLFile('template.html');
$feed = DOMDocument::load('feed.xml');

$list = $template->getElementById('linklist');

$i = 0;
foreach ($feed->getElementsByTagname('entry') as $entry) {
    $li = $template->createElement('li');
    $list->appendChild($li);

    createNode($template, $li, $entry, $i++ < 15);
}

$update = $template->getElementById('last_update');
$update->appendChild($template->createTextNode(date('Y-m-d H:i:s')));

$update->appendChild($template->createComment('Last commit: '.trim(`/home/johannes/bin/git-rev-list HEAD | head -n1`)));

$template->save('index.html');
header('Location: ./');
?>
