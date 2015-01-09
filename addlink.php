<?php
require './auth.php';
require_once './helper.php';

require './vendor/autoload.php';

$tags = array();

if (PHP_SAPI == 'cli') {
    switch ($argc) {
    case 3:
        $tags = explode(',', $argv[2]);
    case 2:
        $url = $argv[1];
        break;
    default:
        die("Fehlerhafter Aufruf\n");
    }
} else {
    if (!(isset($_GET['url']) && $url = stripslashes($_GET['url']))) {
        die("<pre>\nCLI only\n");
    }
    if (!empty($_GET['tags'])) {
        $tags = explode(',', $_GET['tags']);
    }
}

error_reporting(E_ALL);
$filename = 'feed.xml';

$dom = new DOMDocument();
$dom->formatOutput = true;
$dom->load($filename);

$xpath = new DOMXPath($dom);
$xpath->registerNamespace('foo', 'http://www.w3.org/2005/Atom');
$entries = $xpath->query('//foo:entry/foo:id[. = "'.$url.'"]/..');
if ($entries->length != 0) {
    die("URL already added.");
}


$dom->getElementsByTagname('updated')->item(0)->nodeValue = date(DateTime::RFC3339);

$first_entry = $dom->getElementsByTagname('entry')->item(0);

$new_entry = new AtomEntryNode($dom);
$new_entry->setFromURL($url);


$dom->documentElement->insertBefore($new_entry->get(), $first_entry);
$dom->save($filename);

const SILENT=1;
include './create.index.php';

#header('Location: ./');

################


class AtomEntryNode
{
    protected $dom;
    protected $content = null;
    protected $inner;

    public function __construct(DOMDocument $dom) {
        $this->dom = $dom;
        $this->inner = $dom->createElement('entry');
        $this->inner->setAttribute("xmlns", "http://www.w3.org/2005/Atom");

        $this->inner->appendChild($dom->createElement('published',  date(DateTime::RFC3339)));
        $this->inner->appendChild($dom->createElement('updated',  date(DateTime::RFC3339)));
    }

    public function get() {
        return $this->inner;
    }

    public function setTags(array $tags)
    {
         foreach ($tags as $tag) {
             $tag = strtolower(trim($tag));

             $cat = $dom->createElement('category');
             $cat->appendChild($dom->createTextnode($tag));

              $entry->appendChild($cat);
         }
    }

    public function setFromURL($url)
    {
        global $http_response_header;

        $url = str_replace(" ", "+", $url); 
        $info = Embed\Embed::create($url);
        $ct = $info->request->getMimeType();
  
        switch ($ct) {          
        case 'image/png':
        case 'image/gif':
        case 'image/jpeg':
            $this->setFromImage($url, $data);
            break; 
        default:
            $this->setFromEmbed($url,  $info);
            break;
        }
    }

    public function setFromImage($url, $data)
    {
        $info = getimagesize('data://text/plain;base64,'.base64_encode($data));
        switch ($info[2]) {
            case IMAGETYPE_GIF:  $type = 'GIF';  break;
            case IMAGETYPE_JPEG: $type = 'JPEG'; break;
            case IMAGETYPE_PNG:  $type = 'PNG';  break;
            default:             $type = 'Unknown'; break;
        }

        $title = sprintf('%s - %s image (%ux%u)', basename($url), $type, $info[0], $info[1]);
        $this->setData($url, $title);
        
        $img = $this->dom->createElement('img');
        $img->setAttribute('src', $url);
        $this->appendContent($img);

    }
   
    public function setFromEmbed($url, $info)
    {
        $title = $info->title;
        if ($info->providerName) {
            $title .= ' ('.$info->providerName.')';
        }
        $this->setData($url, $title);
        if ($d = $info->description) {
            $div = $this->dom->createElement('div');
            $t = $this->dom->createTextNode($d);
            $div->appendChild($t);
               
            $this->appendContent($div);
        }
    }

    public function setData($url, $title)
    {
        $this->inner->appendChild($this->dom->createElement('id', htmlspecialchars($url)));
        $this->inner->appendChild($this->dom->createElement('title', htmlspecialchars($title)));

        $link = $this->dom->createElement('link');
        $link->setAttribute('href', $url);
        $link->setAttribute('rel', 'alternate');
        $link->setAttribute('type', 'text/html');
        $this->inner->appendChild($link);
    }

    public function appendContent(DOMNode $node)
    {
        if ($this->content == null) {
            $this->content = $this->dom->createElement('content');
            $this->content->setAttribute('type', 'xhtml');
            $this->inner->appendChild($this->content);
        }

        $node->setAttribute('xmlns', 'http://www.w3.org/1999/xhtml');
        $this->content->appendChild($node);
    }
}

