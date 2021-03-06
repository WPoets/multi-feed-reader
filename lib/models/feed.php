<?php
namespace MultiFeedReader\Models;

class Feed extends Base
{
	private function get_itunes_item_tag( $item, $tag ) {
		$raw = $item->get_item_tags( SIMPLEPIE_NAMESPACE_ITUNES, $tag );

		if ( $raw && isset( $raw[0]['data'] ) )
			return $raw[0]['data'];
		else
			return '';
	}

	private function get_itunes_channel_tag( $feed, $tag ) {
		$raw = $feed->get_channel_tags( SIMPLEPIE_NAMESPACE_ITUNES, $tag );

		if ( $raw && isset( $raw[0]['data'] ) ) 
			return $raw[0]['data'];
		else
			return '';
	}

	private function get_itunes_item_image( $item ) {
		$raw = $item->get_item_tags( SIMPLEPIE_NAMESPACE_ITUNES, 'image' );

		if ( $raw && isset( $raw[0]['attribs']['']['href'] ) )
			return $raw[0]['attribs']['']['href'];
		else
			return '';
	}

	public function parse() {
		require_once ABSPATH . WPINC . '/class-simplepie.php';

		$feed = new \SimplePie();
		// $feed->handle_content_type();
		$feed->set_feed_url( $this->url );
		$feed->set_cache_duration( 3600 ); // 1 hour is default
		$feed->enable_order_by_date( false ); // we will sort later manually
		$feed->set_cache_location( \MultiFeedReader\get_cache_directory() );
		$feed->init();

		$result = array();
		
		// read global feed data
		$result[ 'feed' ] = array(
			'title'    => $feed->get_title(),
			'link'     => $feed->get_link(),
			'language' => $feed->get_language(),
			'subtitle' => $this->get_itunes_channel_tag( $feed, 'subtitle' ),
			'summary'  => $this->get_itunes_channel_tag( $feed, 'summary' ),
			'image'    => $feed->get_image_url()
		);
		
		// read feed items
		$result[ 'items' ] = array();
		
		$items = $feed->get_items();
		foreach ( $items as $item ) {
			
			$description = $this->get_itunes_item_tag( $item, 'description' );
			if ( ! $description )
				$description = $item->get_description();
			
			$result[ 'items' ][] = array(
				'feed_id'     => $this->id,
				'content'     => $item->get_content(),
				'duration'    => $this->get_itunes_item_tag( $item, 'duration' ),
                'thumbnail'   => $this->get_itunes_item_image( $item ),
				'subtitle'    => $this->get_itunes_item_tag( $item, 'subtitle' ),
				'summary'     => $this->get_itunes_item_tag( $item, 'summary' ),
				'title'       => $item->get_title(),
				'link'        => $item->get_link(),
				'pubDate'     => $item->get_date(),
				'pubDateTime' => strtotime( $item->get_date() ),
				'guid'        => $item->get_id(),
				'description' => $description,
				'enclosure'   => $item->get_enclosure()->link
			);
		}

		return $result;
	}
	
	public static function find_by_feed_collection_id( $id ) {
		global $wpdb;

		$class = get_called_class();
		$models = array();

		$rows = $wpdb->get_results( 'SELECT * FROM ' . self::table_name() . ' WHERE feed_collection_id = ' . (int) $id );
		
		foreach ( $rows as $row ) {
			$model = new $class();
			$model->flag_as_not_new();
			foreach ( $row as $property => $value ) {
				$model->$property = $value;
			}
			$models[] = $model;
		}

		return $models;
	}
}

Feed::property( 'id', 'INT NOT NULL AUTO_INCREMENT PRIMARY KEY' );
Feed::property( 'feed_collection_id', 'INT' );
Feed::property( 'url', 'VARCHAR(255)' );
// Feed::belongs_to( 'MultiFeedReader\Models\FeedCollection' );