<?php

require __DIR__ . '/vendor/autoload.php';

$loader = new Twig_Loader_Filesystem( __DIR__ . '/views' );
$twig = new Twig_Environment( $loader );

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
