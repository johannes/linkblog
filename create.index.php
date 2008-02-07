<?php

require './helper.php';

$template = DOMDocument::loadHTMLFile('template.html');
$feed = DOMDocument::load('feed.xml');

$list = $template->getElementById('linklist');

$i = 0;
foreach ($feed->getElementsByTagname('entry') as $entry) {
    $li = $template->createElement('li');
    $list->appendChild($li);

    createNode($template, $li, $entry, !defined('EDIT') && $i++ < 15);

    if (defined('EDIT')) {
        $tags = '';
        $taglist = $entry->getElementsByTagname('category');
        foreach ($taglist as $tagtag) {
            $tags .= $tagtag->firstChild->nodeValue . ', ';
        }

        $div   = $template->createElement('div');
        $form  = $template->createElement('form');
        $input = $template->createElement('input');

        $input->setAttribute('style', 'width: 400px');
        $input->setAttribute('value', $tags);

        $form->appendChild($input);
        $div->appendChild($form);
        $li->appendChild($div);
    }
}

$update = $template->getElementById('last_update');
$update->appendChild($template->createTextNode(date('Y-m-d H:i:s')));

$update->appendChild($template->createComment('Last commit: '.trim(`/home/johannes/bin/git-rev-list HEAD | head -n1`)));

if (!defined('EDIT')) {
    $template->save('index.html');
    header('Location: ./');
} else {
    return $template;
}
