<?php
class Releases3DSBridge extends BridgeAbstract {

	public function loadMetadatas() {

		$this->maintainer = "ORelio";
		$this->name = "3DS Scene Releases";
		$this->uri = "http://www.3dsdb.com/";
		$this->description = "Returns the newest scene releases.";
		$this->update = "2015-09-17";

	}

    public function collectData(array $param) {

        function ExtractFromDelimiters($string, $start, $end) {
            if (strpos($string, $start) !== false) {
                $section_retrieved = substr($string, strpos($string, $start) + strlen($start));
                $section_retrieved = substr($section_retrieved, 0, strpos($section_retrieved, $end));
                return $section_retrieved;
            } return false;
        }

        function TypeToString($type) {
            switch ($type) {
                case 1: return '3DS Game';
                case 4: return 'eShop';
                default: return '??? ('.$type.')';
            }
        }

        function CardToString($card) {
            switch ($card) {
                case 1: return 'Regular (CARD1)';
                case 2: return 'NAND (CARD2)';
                default: return '??? ('.$card.')';
            }
        }

        $dataUrl = 'http://3dsdb.com/xml.php';
        $xml = file_get_contents($dataUrl) or $this->returnError('Could not request 3dsdb: '.$dataUrl, 500);
        $limit = 0;

        foreach (array_reverse(explode('<release>', $xml)) as $element) {
            if ($limit < 5) {
                if (strpos($element, '</release>') !== false) {

                    $id = ExtractFromDelimiters($element, '<id>', '</id>');
                    $name = ExtractFromDelimiters($element, '<name>', '</name>');
                    $publisher = ExtractFromDelimiters($element, '<publisher>', '</publisher>');
                    $region = ExtractFromDelimiters($element, '<region>', '</region>');
                    $group = ExtractFromDelimiters($element, '<group>', '</group>');
                    $imagesize = ExtractFromDelimiters($element, '<imagesize>', '</imagesize>');
                    $serial = ExtractFromDelimiters($element, '<serial>', '</serial>');
                    $titleid = ExtractFromDelimiters($element, '<titleid>', '</titleid>');
                    $imgcrc = ExtractFromDelimiters($element, '<imgcrc>', '</imgcrc>');
                    $filename = ExtractFromDelimiters($element, '<filename>', '</filename>');
                    $releasename = ExtractFromDelimiters($element, '<releasename>', '</releasename>');
                    $trimmedsize = ExtractFromDelimiters($element, '<trimmedsize>', '</trimmedsize>');
                    $firmware = ExtractFromDelimiters($element, '<firmware>', '</firmware>');
                    $type = ExtractFromDelimiters($element, '<type>', '</type>');
                    $card = ExtractFromDelimiters($element, '<card>', '</card>');

                    if (!empty($releasename)) {

                        //Retrieve cover art and short desc from IGN?
                        $ignResult = false; $ignDescription = ''; $ignLink = ''; $ignDate = time(); $ignCoverArt = '';
                        $ignSearchUrl = 'http://www.ign.com/search?q='.urlencode($name);
                        if ($ignResult = file_get_html($ignSearchUrl)) {
                            $ignCoverArt = $ignResult->find('div.search-item-media', 0)->find('img', 0)->src;
                            $ignDesc = $ignResult->find('div.search-item-description', 0)->plaintext;
                            $ignLink = $ignResult->find('div.search-item-sub-title', 0)->find('a', 1)->href;
                            $ignDate = strtotime(trim($ignResult->find('span.publish-date', 0)->plaintext));
                            $ignDescription = '<div><img src="'.$ignCoverArt.'" /></div><div>'.$ignDesc.' <a href="'.$ignLink.'">More at IGN</a></div>';
                        }

                        //Main section : Release description from 3DS database
                        $releaseDescription = '<h3>Release Details</h3>'
                            .'<b>Release ID: </b>'.$id.'<br />'
                            .'<b>Game Name: </b>'.$name.'<br />'
                            .'<b>Publisher: </b>'.$publisher.'<br />'
                            .'<b>Region: </b>'.$region.'<br />'
                            .'<b>Group: </b>'.$group.'<br />'
                            .'<b>Image size: </b>'.(intval($imagesize)/8).'MB<br />'
                            .'<b>Serial: </b>'.$serial.'<br />'
                            .'<b>Title ID: </b>'.$titleid.'<br />'
                            .'<b>Image CRC: </b>'.$imgcrc.'<br />'
                            .'<b>File Name: </b>'.$filename.'<br />'
                            .'<b>Release Name: </b>'.$releasename.'<br />'
                            .'<b>Trimmed size: </b>'.intval(intval($trimmedsize)/1048576).'MB<br />'
                            .'<b>Firmware: </b>'.$firmware.'<br />'
                            .'<b>Type: </b>'.TypeToString($type).'<br />'
                            .'<b>Card: </b>'.CardToString($card).'<br />';

                        //Build search links section to facilitate release search using search engines
                        $releaseNameEncoded = urlencode(str_replace(' ', '+', $releasename));
                        $searchLinkGoogle = 'https://google.com/?q='.$releaseNameEncoded;
                        $searchLinkDuckDuckGo = 'https://duckduckgo.com/?q='.$releaseNameEncoded;
                        $searchLinkQwant = 'https://lite.qwant.com/?q='.$releaseNameEncoded.'&t=web';
                        $releaseSearchLinks = '<h3>Search this release</h3><ul>'
                            .'<li><a href="'.$searchLinkGoogle.'">Search using Google</a></li>'
                            .'<li><a href="'.$searchLinkDuckDuckGo.'">Search using DuckDuckGo</a></li>'
                            .'<li><a href="'.$searchLinkQwant.'">Search using Qwant</a></li>'
                            .'</ul>';

                        //Build and add final item with the above three sections
                        $item = new \Item();
                        $item->title = $name;
                        $item->author = $publisher;
                        $item->timestamp = $ignDate;
                        $item->thumbnailUri = $ignCoverArt;
                        $item->uri = empty($ignLink) ? $searchLinkDuckDuckGo : $ignLink;
                        $item->content = $ignDescription.$releaseDescription.$releaseSearchLinks;
                        $this->items[] = $item;
                        $limit++;
                    }
                }
            }
        }
    }

    public function getName() {
        return '3DS Scene Releases';
    }

    public function getURI() {
        return 'http://www.3dsdb.com/';
    }

    public function getCacheDuration() {
        return 10800; //3 hours
    }
}
