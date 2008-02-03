<?php
require './auth.php';
require './helper.php';

if ($argc == 2) {
    $url = $argv[1];
} elseif (PHP_SAPI == 'cli') {
    die("Fehlerhafter Aufruf\n");
} else {
    if (!(isset($_GET['url']) && $url = stripslashes($_GET['url']))) {
        die("<pre>\nCLI only\n");
    }
}
error_reporting(E_ALL);
$filename = 'feed.xml';

$dom = DOMDocument::load($filename);

$dom->getElementsByTagname('updated')->item(0)->nodeValue = date(DateTime::RFC3339);

$first_entry = $dom->getElementsByTagname('entry')->item(0);

$new_entry = new AtomEntryNode($dom);
$new_entry->setFromURL($url);

$dom->documentElement->insertBefore($new_entry->get(), $first_entry);
$dom->save($filename);

@unlink('index.html');

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

    private function getContentType($header)
    {
        foreach ($header as $h) {
            if (strpos($h, 'Content-Type: ') === 0) {
                $h = substr($h, 14);
                list($h) = explode(';', $h);
                return $h;
            }
        }

        return 'application/octet-stream';
    }

    public function setFromURL($url)
    {
        global $http_response_header;

        if (strpos($url, 'youtube.com')) {
            $this->setFromYoutube($url);
            return;
        }

        $url = str_replace(" ", "+", $url); 
        $data = @file_get_contents($url);

        if (!$data) throw new Exception("No data");
     
        $ct = $this->getContentType($http_response_header);
  
        switch ($ct) {          
        case 'image/png':
        case 'image/gif':
        case 'image/jpeg':
            $this->setFromImage($url, $data);
            break; 
        case 'text/html':
        case 'text/xhtml':
            $this->setFromHTML($url, $data);
            break;
        default:
            $this->setData($url,  sprintf('%s (%s)', basename($url), $ct));
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
   
    public function setFromHTML($url, $data)
    {
        $page = @DOMDocument::loadHTMLFile('data://text/plain;base64,'.base64_encode($data));
        $title = $page->getElementsByTagname('title')->item(0)->nodeValue;

        $this->setData($url, $title);

        switch(parse_url($url, PHP_URL_HOST)) {
        case 'www.sueddeutsche.de':
        case 'sueddeutsche.de':
            $xpath = new DOMXPath($page);
            $q = $xpath->query('//h3[@class="artikelTeaser"]');
            if ($q->length) {
               $p = $this->dom->createElement('p');
               $p->appendChild($this->dom->importNode($q->item(0)->firstChild, true));
               
               $this->appendContent($p);
            }
            break;

        case 'www.golem.de':
        case 'golem.de':
            $xpath = new DOMXPath($page);
            $q = $xpath->query('//p[@class="teaser"]');
            if ($q->length) {
               $c = $this->dom->importNode($q->item(0), true);
               $c->removeAttribute('class');
               $this->appendContent($c);
            }

            break;

        case 'www.faz.net':
        case 'faz.net':
            $xpath = new DOMXPath($page);
            $q = $xpath->query('//table[@id="content-tab"]//span[@class="dunkelgrau fs-12 lh-16"]/span[@class="dunkelgrau fs-12 lh-16"]');
            if ($q->length) {
               $c = $this->dom->importNode($q->item(0), true);
               $c->removeAttribute('class');
               $this->appendContent($c);
            }

            break;
        case 'www.heise.de':
        case 'heise.de':
            $xpath = new DOMXPath($page);

            // Heise Newsticker
            $q = $xpath->query('//div[@class="meldung_wrapper"]/p');
            if ($q->length) {
               $this->appendContent($this->dom->importNode($q->item(0), true));
               break;
            }

            // Telepolis
            $q = $xpath->query('//table[@class="table"]//td[@class="content"]//h2');
            if ($q->length) {
               $p = $this->dom->createElement('p');
               $p->appendChild($this->dom->importNode($q->item(0)->firstChild, true));
               break;
            }

            break;
        case 'www.spiegel.de':
        case 'spiegel.de':
            $xpath = new DOMXPath($page);
            $q = $xpath->query('//p[@class="spIntrotext"]');
            if ($q->length) {
               $this->appendContent($this->dom->importNode($q->item(0), true)); 
            }
            break;
        }
    }

    public function setFromYoutube($url)
    {
        parse_str(substr($url, strpos($url, '?') + 1), $output);
        $vid = $output['v'];

        $search_url = sprintf("http://www.youtube.com/api2_rest?method=youtube.videos.get_details&dev_id=%s&video_id=%s",
                          YOUTUBE_DEVID, $vid);

        $yt = DOMDocument::load($search_url);
        $this->setData($url, 'Youtube: '.$yt->getElementsByTagName('title')->item(0)->nodeValue);

        $object = $this->dom->createElement('object');
        $object->setAttribute('width', 425);
        $object->setAttribute('height', 350);
        $this->appendContent($object);

        $param = $this->dom->createElement('param');
        $param->setAttribute('name', 'movie');
        $param->setAttribute('value', 'http://www.youtube.com/v/'.$vid);
        $object->appendChild($param);

        $embed = $this->dom->createElement('embed');
        $embed->setAttribute('width', 425);
        $embed->setAttribute('height', 350);
        $embed->setAttribute('src', 'http://www.youtube.com/v/'.$vid);
        $embed->setAttribute('type', 'application/x-shockwave-flash');
        $object->appendChild($embed);

        $this->appendContent($this->dom->createElement('p', $yt->getElementsByTagName('description')->item(0)->nodeValue));
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

