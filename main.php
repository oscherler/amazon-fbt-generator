<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

use Doctrine\Common\Cache\FilesystemCache;

# avoid warnings when using Goutte
ini_set( 'date.timezone', 'Europe/Zurich' );

$languages = array( 'en', 'fr', 'de' );
$cache_path = __DIR__ . '/cache';
$product_cache_path = $cache_path . '/product';

$translator = new Translator( $languages[0], new MessageSelector() );

$yaml_loader = new YamlFileLoader();
$translator->addLoader( 'yaml', $yaml_loader );

foreach( $languages as $language )
{
	$translator->addResource( 'yaml', __DIR__ . '/translations/messages.' . $language . '.yml', $language );
}

$twig_loader = new Twig_Loader_Filesystem( __DIR__ . '/views' );
$twig = new Twig_Environment( $twig_loader );
$twig->addExtension( new TranslationExtension( $translator ) );

$client = new Client();

$product_cache = new FilesystemCache( $product_cache_path );

$items = array();
$currency = 'EUR ';

array_shift( $argv );
foreach( $argv as $arg )
{
	# poor man’s cache
	if( $product_cache->contains( $arg ) )
	{
		$cached = $product_cache->fetch( $arg );
		$success = $cached['success'];
		$crawler = new Crawler( $cached['body'] );
	}
	else
	{
		$crawler = $client->request( 'GET', 'https://www.amazon.com/dp/' . $arg );
		$status = $client->getResponse()->getStatus();
		$success = $status >= 200 && $status < 300;

		$product_cache->save( $arg, array(
			'success' => $success,
			'body' => $crawler->html()
		) );
	}
	
	if( ! $success )
		continue;
	
	try
	{
		$title = 'Unknown product';
		$price = 0;
		$image_urls = array('');

		# product title
		$title_node = $crawler->filter('#productTitle');
		$title = trim( $title_node->text() );

		# product price
		$price_node = $crawler->filter('#priceblock_ourprice');
		$price_text = trim( $price_node->text() );

		if( preg_match( '/([^0-9]*)(.*)/', $price_text, $matches ) )
		{
			list( $_ignored, $product_currency, $price ) = $matches;
		}

		$tiny_urls = $crawler
			->filter('#altImages .a-button-text img')
			->extract('src');

		$image_urls = array_map(
			function( $url ) { return str_replace( '_SS40_', '_AC_UL115_', $url ); },
			$tiny_urls
		);
		
		$currency = $product_currency;
	}
	catch( Exception $e )
	{
		# all properties are previously initialised
	}
	
	$items[] = array(
		'title' => $title,
		'image' => $image_urls[0],
		'price' => $price
	);
}

$total = array_reduce(
	$items,
	function( $total, $item ) { return $total + $item['price']; },
	0
);

echo $twig->render(
	'main.html.twig',
	array(
		'items' => $items,
		'total' => $total,
		'currency' => $currency
	)
);
