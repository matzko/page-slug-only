<?php
/*
Plugin Name: Page Slug URLs
Plugin URI:
Description: A WordPress plugin that allows you to use just page slugs for their URLs, rather than their entire permalink include parent page slugs.
Author: Austin Matzko
Author URI: http://austinmatzko.com
Version: 1.0

Copyright 2011  Austin Matzko  ( email : austin at pressedcode dot com )
*/

class Page_Slug_URLs
{
	public function __construct()
	{
		add_action( 'init', array( $this, 'event_init' ) );
		add_filter( 'page_link', array( $this, 'filter_page_link' ), 99, 2 );
		add_filter( 'page_rewrite_rules', array( $this, 'filter_page_rewrite_rules' ) );
		add_filter( 'posts_where', array( $this, 'filter_posts_where' ), 99, 2 );
	}

	public function event_init()
	{
		global $wp;
		$wp->add_query_var( 'specific_pagename' );
	}

	public function filter_page_link( $link = '', $post_id = null )
	{
		if ( ! is_admin() ) {
			$page = get_post( $post_id );
			if ( ! empty( $page->post_name ) ) {
				$link = home_url( '/' . $page->post_name . '/' );
			}
		}
		return $link;
	}

	public function filter_page_rewrite_rules( $rules = array() )
	{
		foreach( (array) $rules as $key => $val ) {
			$rules[$key] = str_replace( 'pagename=$matches[1]', 'pagename=$matches[1]&specific_pagename=$matches[1]', $val );
		}
		return $rules;
	}

	public function filter_posts_where( $w = '', &$query )
	{
		global $wpdb;
		if ( isset( $query->query_vars['specific_pagename'] ) ) {
			$query->is_page = true;
			$query->is_single = false;
			$pagename = sanitize_title_for_query( wp_basename( $query->query_vars['specific_pagename'] ) );
			$w = str_replace( " AND ({$wpdb->posts}.ID = '0')", '', $w );
			$w = str_replace( array( 
				"post_type = 'page'",
				"post_type = 'post'",
			), "post_type = 'page' AND {$wpdb->posts}.post_name = '{$pagename}' ", $w );
		}
		return $w;
	}
}

function load_page_slug_only()
{
	global $page_slug_only;
	if ( empty( $page_slug_only ) ) 
		$page_slug_only = new Page_Slug_URLs;
}

function activate_page_slug_only()
{
	global $page_slug_only;
	if ( empty( $page_slug_only ) ) 
		$page_slug_only = new Page_Slug_URLs;
	$page_slug_only->event_init();
	flush_rewrite_rules();
}

function deactivate_page_slug_only()
{
	global $page_slug_only;
	if ( empty( $page_slug_only ) ) 
		$page_slug_only = new Page_Slug_URLs;

	remove_filter( 'page_rewrite_rules', array( $page_slug_only, 'filter_page_rewrite_rules' ) );
	flush_rewrite_rules();
}

register_activation_hook( __FILE__, 'activate_page_slug_only' );
register_deactivation_hook( __FILE__, 'deactivate_page_slug_only' );

add_action( 'plugins_loaded', 'load_page_slug_only' );
