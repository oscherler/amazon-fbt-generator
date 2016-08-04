<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

use Doctrine\Common\Cache\FilesystemCache;

use FBTGenerator\Scraper;

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

$product_cache = new FilesystemCache( $product_cache_path );

$scraper = new Scraper( $product_cache );

array_shift( $argv );

foreach( $argv as $asin )
{
	if( $product = $scraper->scrapeProduct( $asin ) )
		$items[] = $product;
}

$total = array_reduce(
	$items,
	function( $total, $item ) { return $total + $item['price']; },
	0
);

$currency = count( $items ) > 0 ? $items[0]['currency'] : null;

echo $twig->render(
	'main.html.twig',
	array(
		'items' => $items,
		'total' => $total,
		'currency' => $currency
	)
);
