<?php

require __DIR__ . '/vendor/autoload.php';

use Symfony\Component\Translation\Translator;
use Symfony\Component\Translation\MessageSelector;
use Symfony\Component\Translation\Loader\YamlFileLoader;
use Symfony\Bridge\Twig\Extension\TranslationExtension;

$loader = new Twig_Loader_Filesystem( __DIR__ . '/views' );
$twig = new Twig_Environment( $loader );

$translator = new Translator( 'en', new MessageSelector() );
$yaml_loader = new YamlFileLoader();

$translator->addLoader( 'yaml', $yaml_loader );
$translator->addResource( 'yaml', __DIR__ . '/translations/messages.en.yml', 'en' );

$twig->addExtension( new TranslationExtension( $translator ) );

$items = array(
	array(
		'title' => 'American DJ Haze Generator Heaterless Fog Machine',
		'image' => 'https://images-na.ssl-images-amazon.com/images/I/41Mp8HpjHvL._AC_UL115_.jpg',
		'price' => '429.99'
	),
	array(
		'title' => 'American DJ Haze/G',
		'image' => 'https://images-na.ssl-images-amazon.com/images/I/31EgPO9ISzL._AC_UL115_.jpg',
		'price' => '43.99'
	),
);

$total = array_reduce( $items, function( $total, $item ) { return $total + $item['price']; }, 0 );

echo $twig->render(
	'main.html.twig',
	array(
		'items' => $items,
		'total' => $total
	)
);
