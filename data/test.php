<?php
error_reporting(0);
$dom = new DOMDocument();
$contents = file_get_contents("/var/www/html/common_search/data/content.html");

$dom->Load($contents);

$doc = new DOMDocument();
libxml_use_internal_errors(true); 
$doc->loadHTML($contents);
libxml_clear_errors();

/*
print_r($dom);
print_r($doc);
$d = $dom->getElementById('aspect_discovery_SimpleSearch_div_search-results');
print_r($d->tagName);
*/
$xpath = new DOMXpath($doc);
$el = $xpath->query("//html/body/div[@id='ds-main']/div[@id='ds-content-wrapper']/div[@id='ds-content']/div[@id='ds-body']/div[@id='aspect_discovery_SimpleSearch_div_search']/div[@id='aspect_discovery_SimpleSearch_div_search-results']/ul/ul/li");

/*
foreach($el as $e){
	$i++;
	echo $e->nodeValue;
}
echo $i;
*/

$res = domNodeList_to_string($el);
echo html_entity_decode(html_entity_decode($res));

//aspect_discovery_SimpleSearch_div_search

####
function domNodeList_to_string($DomNodeList) {
    $output = '';
    $doc = new DOMDocument;
    while ( $node = $DomNodeList->item($i) ) {
        // import node
        $domNode = $doc->importNode($node, true);
        // append node
        $doc->appendChild($domNode);
        $i++;
    }
    $output = $doc->saveXML();
    $output = print_r($output, 1);
    // I added this because xml output and ajax do not like each others
    $output = htmlspecialchars($output);
    return $output;
}
