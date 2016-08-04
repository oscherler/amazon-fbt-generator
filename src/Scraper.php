<?php

namespace FBTGenerator;

use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

use Doctrine\Common\Cache\Cache;

class Scraper
{
	protected $cache;
	protected $client;

	public function __construct( Cache $cache )
	{
		$this->cache = $cache;
		$this->client = new Client();
	}

	public function scrapeProduct( $asin )
	{
		if( $this->cache->contains( $asin ) )
		{
			$cached = $this->cache->fetch( $asin );
			$success = $cached['success'];
			$crawler = new Crawler( $cached['body'] );
		}
		else
		{
			$crawler = $this->client->request( 'GET', 'https://www.amazon.com/dp/' . $asin );
			$status = $this->client->getResponse()->getStatus();
			$success = $status >= 200 && $status < 300;

			$this->cache->save( $asin, array(
				'success' => $success,
				'body' => $crawler->html()
			) );
		}

		if( ! $success )
			return null;

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
				list( $_ignored, $currency, $price ) = $matches;
			}

			$tiny_urls = $crawler
				->filter('#altImages .a-button-text img')
				->extract('src');

			$image_urls = array_map(
				function( $url ) { return str_replace( '_SS40_', '_AC_UL115_', $url ); },
				$tiny_urls
			);
		}
		catch( Exception $e )
		{
			# all properties are previously initialised
		}

		$product = array(
			'title' => $title,
			'image' => $image_urls[0],
			'price' => $price,
			'currency' => $currency
		);
	
		return $product;
	}
}
