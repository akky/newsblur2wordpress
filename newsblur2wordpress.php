<?php

// This is a script to convert Newsblur-exported json to Wordpress WXR

// CDATA handling in XML generation
// code from http://coffeerings.posterous.com/php-simplexml-and-cdata
class SimpleXMLExtended extends SimpleXMLElement {
  public function addCData($cdata_text) {
    $node = dom_import_simplexml($this); 
    $no   = $node->ownerDocument; 
    $node->appendChild($no->createCDATASection($cdata_text)); 
  } 
}

// solve feedburner proxy redirection to get the end URLs
// (this is only needed because my salvaging site used FeedBurner.)
// code from https://github.com/camillo/wp-untracker/blob/967196fd9b41694b6808d7dbd3688ca75c9ef0c1/feedproxyResolver.php
/**
 * Do a GET request against given url (with nobody option enabled).
 * @param string $url
 * @return dict with curl response, containing all headers
 * @throws everything, that curls throws. No exception is catched here.
 */
function doCurlRequest($url)
{
    $curlSession = curl_init($url);
    curl_setopt($curlSession, CURLOPT_NOBODY, 1);
    $curlResponse = curl_exec($curlSession);
    $ret = curl_getinfo($curlSession);
    curl_close($curlSession);
    
    return $ret;
}

// added a code to remove Analytics tracking parameters
/**
 * Do a GET request against given url and returns the redirect_url header, if exists.
 * @return redirect_url if exists, $url otherwise; leave $url unmodified if something went wrong.
 * @param string $url
 */
function resolveUrl($url)
{
    try 
    {
        $header = doCurlRequest($url);
        if (array_key_exists('redirect_url', $header) && !empty($header['redirect_url']))
        {
            $redirect_url = $header['redirect_url'];

            // utm_* paramters removed
            $parameters_removed = remove_utm_parameters($redirect_url);
            return $parameters_removed;
        } else 
        {
            return $url;
        }
    } catch (Exception $ex)
    {
        return $url;
    }
}

function remove_utm_parameters($url) {
    $results = preg_replace('/[\?\&]utm_(\w+)\=[^&]+/', '', $url);
    return $results;
}

// make slug from title
//
//  Actually, this must call WP's function sanitize_file_name()
//  for non-ASCII titles. see wp-include/formatting.php
//
//  You may need your special slug function if you want so reproduce
//  the original permalink for each articles.
function sluggify($text)
{
    return preg_replace("|[^a-z0-9]+|", "-", strtolower($text));
}

// -----------------------------------------------------
// start XML generation

$xml = <<<XML
<?xml version="1.0" encoding="UTF-8" ?>
<rss version="2.0"
    xmlns:excerpt="http://wordpress.org/export/1.2/excerpt/"
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:wfw="http://wellformedweb.org/CommentAPI/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:wp="http://wordpress.org/export/1.2/"
></rss>
XML;

$rss = new SimpleXMLExtended($xml);
$namespaces = $rss->getDocNamespaces(true);

// load the Newblur exported JSON file
$file = file_get_contents("starred_stories.json");
$json = json_decode($file, true);

// Wordpress will choke if our post names aren't unique, so we track
// them separately
$post_names = array();

$channel = $rss->addChild("channel");
$channel->addChild("generator", basename(__FILE__));
$channel->addChild("wxr_version", "1.2", $namespaces["wp"]);
// base_site_url is MUST. WordPress Importer warns if this does not exist
$channel->addChild("base_site_url", "http://akimotojp/blog", $namespaces["wp"]);

foreach ($json["stories"] as $item)
{
    $rssItem = $channel->addChild("item");

    $content = null;
    if (array_key_exists('story_content', $item))
    {
        $content = $item['story_content'];
    }
    else
    {
        $content = '';
    }

    if (isset($item["story_title"]))
    {
        $rssItem->addChild("title", $item["story_title"]);
    }

    $permaLink = resolveUrl($item['story_permalink']);
    $rssItem->addChild("link", $permaLink);

    $pubDate = $rssItem->addChild("pubDate", gmdate("D, j M Y G:i:s O", $item["story_timestamp"]));

    if (isset($item["story_authors"]))
    {
        $rssCreator = $rssItem->addChild("creator", null, $namespaces["dc"]);
        $rssCreator->addCData($item["story_authors"]);
    }

    $rssContent = $rssItem->addChild("encoded", null, $namespaces["content"]);
    $rssContent->addCData($item["story_content"]);

    $excerpt = $rssItem->addChild("encoded", null, $namespaces["excerpt"]);
    $excerpt->addCData('');

    $rssItem->addChild("comment_status", "open", $namespaces["wp"]);
    $rssItem->addChild("ping_status", "open", $namespaces["wp"]);
    
    // make a Wordpress friendly title slug for the post
    if (isset($item["story_title"]))
    {
        $slug = sluggify($item["story_title"]);
    }
    else
    {
        /* if no title, generate the slug from the timestamp */
        $slug = date("Y-m-d-G-i-s", $item["story_timestamp"]);
    }
    
    /* make sure that our slug  is unique -- add a counter to the end
       if it is not, and track those counter values in $post_names[] */
    if (isset($post_names[$slug]))
    {
        $post_names[$slug]++;
        $slug .= "-" . $post_names[$slug];
    }
    else
    {
        $post_names[$slug] = 0;
    }
    $rssItem->addChild("post_name", $slug, $namespaces["wp"]);

    /* more Wordpress metadata -- all of which could be tweaked */
    $rssItem->addChild("status", "publish", $namespaces["wp"]); // TODO make configurable
    $rssItem->addchild("post_parent", 0, $namespaces["wp"]);    // TODO make configurable
    $rssItem->addChild("menu_order", 0, $namespaces["wp"]);     // TODO make configurable
    $rssItem->addChild("post_type", "post", $namespaces["wp"]); // TODO make configurable
    $rssItem->addChild("post_password", "", $namespaces["wp"]); // TODO make configurable
    $rssItem->addChild("is_sticky", 0, $namespaces["wp"]);      // TODO make configurable

    foreach($item["story_tags"] as $story_tag)
    {
        $rssCategory = $rssItem->addChild("category", null);
        $rssCategory->addCData($story_tag);
        $rssCategory->addAttribute("domain", "post_tag");
        $rssCategory->addAttribute("nicename", sluggify($story_tag));
    }

    // set default category - should be configurable
    $rssCategory = $rssItem->addChild("category", null);
    $rssCategory->addCData('Japan');
    $rssCategory->addAttribute("domain", "category");
    $rssCategory->addAttribute("nicename", sluggify('Japan'));
}

// indent
$dom = new DOMDocument('1.0', 'utf-8');
$dom->preserveWhiteSpace = false;
$dom->formatOutput = true;
$dom->loadXML($rss->asXML());
$formattedXml = $dom->saveXML();

file_put_contents("newsblur-exported.xml", $formattedXml);
