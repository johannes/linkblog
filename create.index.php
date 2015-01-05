<?php

require_once './helper.php';

$template = new DOMDocument;
$template->loadHTMLFile('template.html');

$feed = new DOMDocument;
$feed->load('feed.xml');

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

$container = $template->getElementById('tagcloud');
$list = $feed->getElementsByTagName('category');

$tags = array();
for($i = 0; $i < $list->length; $i++) {
    $item = $list->item($i);
    @$tags[trim($item->nodeValue)]++;
}

$max = $tags;
rsort($max);
$max = $max[0];

foreach ($tags as $tag => $count) {
    $span = $template->createElement('span');
    $span->setAttribute('style', 'font-size:'.round($count/$max*125+75).'%');
    $span->appendChild($template->createTextNode($tag));
    $container->appendChild($span);
    $container->appendChild($template->createTextNode("\n"));
}


$update = $template->getElementById('last_update');
$update->appendChild($template->createTextNode(date('Y-m-d H:i:s')));

$update->appendChild($template->createComment('Last commit: '.trim(`git rev-list HEAD | head -n1`)));

if (!defined('EDIT')) {
    $template->save('index.html');
    if (!defined('SILENT')) {
        header('Location: ./');
    }    
} else {
    return $template;
}
