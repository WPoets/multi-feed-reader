<?php
namespace MultiFeedReader\Models;

abstract class Base
{
	/**
	 * Property dictionary for all tables
	 */
	private static $properties = array();
	
	/**
	 * Define a property with name and type.
	 * 
	 * Currently only supports basics.
	 * @todo enable additional options like NOT NULL, DEFAULT etc.
	 * 
	 * @param string $name Name of the property / column
	 * @param string $type mySQL column type 
	 */
	public static function property( $name, $type ) {
		$class = get_called_class();
		
		if ( ! isset( self::$properties[ $class ] ) ) {
			self::$properties[ $class ] = array();
		}
		
		self::$properties[ $class ][] = array(
			'name' => $name,
			'type' => $type
		);
	}
	
	private static function properties() {
		$class = get_called_class();
		
		if ( ! isset( self::$properties[ $class ] ) ) {
			self::$properties[ $class ] = array();
		}
		
		return self::$properties[ $class ];
	}
	
	/**
	 * Create database table based on defined properties.
	 * 
	 * Automatically includes an id column as auto incrementing primary key.
	 * @todo allow model changes
	 */
	public static function build() {
		global $wpdb;
		
		$property_sql = array();
		$properties = self::properties();
		foreach ( $properties as $property ) {
			$property_sql[] = "`{$property['name']}` {$property['type']}";
		}
		
		$sql = 'CREATE TABLE IF NOT EXISTS '
		     . self::table_name()
		     . ' ('
		     . '`id` INT NOT NULL AUTO_INCREMENT PRIMARY KEY,'
		     . implode( ',', $property_sql )
		     . ' );'
		;
		
		$wpdb->query( $sql );
	}
	
	/**
	 * Retrieves the database table name.
	 * 
	 * The name is derived from the namespace an class name. Additionally, it
	 * is prefixed with the global WordPress database table prefix.
	 * @todo cache
	 * 
	 * @return string database table name
	 */
	public static function table_name() {
		global $wpdb;
		
		// get name of implementing class
		$table_name = get_called_class();
		// replace backslashes from namespace by underscores
		$table_name = str_replace( '\\', '_', $table_name );
		// remove Models subnamespace from name
		$table_name = str_replace( 'Models_', '', $table_name );
		// all lowercase
		$table_name = strtolower( $table_name );
		// prefix with $wpdb prefix
		return $wpdb->prefix . $table_name;
	}	
}