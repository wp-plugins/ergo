<?php

/*
Plugin Name: Ergo
Plugin URI: http://wordpress.org/extend/plugins/ergo/
Description: Allows users to mark their comments as "objections", and from there it builds a system that organizes and rationalizes discussions.
Version: 1.0
Author: Felipe Schenone
Author URI: http://felipeschenone.com/
License: GPLv2
*/

/*
Copyright 2012 Felipe Schenone (email: schenonef@gmail.com)

This program is free software; you can redistribute it and/or modify
it under the terms of the GNU General License, version 2, as 
published by the Free Software Foundation.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General License for more details.

You should have received a copy of the GNU General License
along with this program; if not, write to the Free Software
Foundation, Inc., 51 Franklin St, Fifth Floor, Boston, MA 02110-1301 USA
*/

define( 'ERGO_DIRECTORY', plugin_dir_url( __FILE__ ) );

wp_register_style( 'jquery-ui', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.1/themes/smoothness/jquery-ui.css' );
wp_register_style( 'ergo', ERGO_DIRECTORY . 'ergo.css' );
wp_enqueue_style( 'jquery-ui' );
wp_enqueue_style( 'ergo' );

wp_register_script( 'ergo', ERGO_DIRECTORY . 'ergo.js' );
wp_enqueue_script( 'jquery-ui-tabs' );
wp_enqueue_script( 'jquery-ui-dialog' );
wp_enqueue_script( 'ergo' );

add_action( 'publish_post', 'Ergo::on_publish_post' );
add_action( 'comment_post', 'Ergo::on_comment_post' );
add_action( 'trashed_comment', 'Ergo::on_trashed_comment' );
add_action( 'deleted_comment', 'Ergo::on_deleted_comment' );
add_action( 'comment_form_after_fields', 'Ergo::checkbox' );
add_action( 'comment_form_logged_in_after', 'Ergo::checkbox' );
add_action( 'wp_ajax_ergo_dialog', 'Ergo::dialog' );
add_action( 'wp_ajax_nopriv_ergo_dialog', 'Ergo::dialog' );
add_action( 'post_comment_status_meta_box-options', 'Ergo::allow_objections_checkbox' );

class Ergo {	
	
	/* Get methods */
	
	function get_option( $key ) {
		$option = get_option( 'ergo' );
		$option = $option[ $key ];
		return $option;
	}
	
	function get_meta() {
		global $comment;
		if ( $comment )
			return self::get_comment_meta( $comment->comment_ID );
		else
			return self::get_post_meta( get_the_id() );
	}
	
	function get_post_meta( $post_id, $key = false ) {
		$meta = get_post_meta( $post_id, 'ergo', true );
		if ( $key )
			return $meta[ $key ];
		else
			return $meta;
	}
	
	function get_comment_meta( $comment_id, $key = false ) {
		$meta = get_comment_meta( $comment_id, 'ergo', true );
		if ( $key )
			return $meta[ $key ];
		else
			return $meta;
	}
	
	function get_post_unanswered_objections( $post_id ) {
		return self::get_post_meta( $post_id, 'unanswered_objections' );
	}
	
	function get_comment_unanswered_objections( $comment_id ) {
		return self::get_comment_meta( $comment_id, 'unanswered_objections' );
	}
	
	/* Set methods */
	
	function set_option( $key, $value ) {
		$option = get_option( 'ergo' );
		$option[ $key ] = $value;
		update_option( 'ergo', $option );
	}
	
	function set_post_meta( $post_id, $key, $value ) {
		$meta = get_post_meta( $post_id, 'ergo', true );
		$meta[ $key ] = $value;
		update_post_meta( $post_id, 'ergo', $meta );
	}
	
	function set_comment_meta( $comment_id, $key, $value ) {
		$meta = get_comment_meta( $comment_id, 'ergo', true );
		$meta[ $key ] = $value;
		update_comment_meta( $comment_id, 'ergo', $meta );
	}
	
	/* Is methods */
	
	function comment_is_objection( $comment_id ) {
		return self::get_comment_meta( $comment_id, 'is_objection' );
	}
	
	/* Update methods */
	
	function update_post( $post_id ) {
		$comments = get_comments( array( 'post_id' => $post_id, 'parent' => 0 ) );
		$unanswered_objections = 0;
		foreach ( $comments as $comment ) {
			$comment_meta = self::get_comment_meta( $comment->comment_ID );
			if ( $comment_meta['is_objection' ] and ! $comment_meta['unanswered_objections'] )
				$unanswered_objections++;
		}
		self::set_post_meta( $post_id, 'unanswered_objections', $unanswered_objections );
	}
	
	function update_comment( $comment_id ) {
		$comments = get_comments( array( 'parent' => $comment_id ) );
		$unanswered_objections = 0;
		foreach ( $comments as $comment ) {
			$comment_meta = self::get_comment_meta( $comment->comment_ID );
			if ( $comment_meta['is_objection' ] and ! $comment_meta['unanswered_objections'] )
				$unanswered_objections++;
		}
		self::set_comment_meta( $comment_id, 'unanswered_objections', $unanswered_objections );
	}
	
	function update_ancestors( $comment_id ) {
		$comment = get_comment( $comment_id );
		if ( $comment->comment_parent ) {
			self::update_comment( $comment->comment_parent );
			self::update_ancestors( $comment->comment_parent );
		} else {
			self::update_post( $comment->comment_post_ID );
		}
	}
	
	function update_post_unanswered_objections( $post_id ) {
		$comments = get_comments( array( 'post_id' => $post_id, 'parent' => 0 ) );
		$unanswered_objections = 0;
		foreach ( $comments as $comment ) {
			$comment_is_objection = self::comment_is_objection( $comment->comment_ID );
			if ( $comment_is_objection ) {
				$comment_unanswered_objections = self::update_comment_unanswered_objections( $comment->comment_ID );
				if ( ! $comment_unanswered_objections )
					$unanswered_objections++;
			}
		}
		self::set_post_meta( $post_id, 'unanswered_objections', $unanswered_objections );
		return $unanswered_objections;
	}
	
	function update_comment_unanswered_objections( $comment_id ) {
		$comments = get_comments( array( 'parent' => $comment_id ) );
		$unanswered_objections = 0;
		foreach ( $comments as $comment ) {
			$comment_is_objection = self::comment_is_objection( $comment->comment_ID );
			if ( $comment_is_objection ) {
				$comment_unanswered_objections = self::update_comment_unanswered_objections( $comment->comment_ID );
				if ( ! $comment_unanswered_objections )
					$unanswered_objections++;
			}
		}
		self::set_comment_meta( $comment_id, 'unanswered_objections', $unanswered_objections );
		return $unanswered_objections;
	}
	
	/* Action methods */
	
	function on_publish_post( $post_id ) {
		if ( $_POST['ergo_allow_objections'] )
			$allows_objections = true;
		else
			$allows_objections = false;
		self::set_post_meta( $post_id, 'allows_objections', $allows_objections );
		self::set_post_meta( $post_id, 'unanswered_objections', 0 );
	}
	
	function on_comment_post( $comment_id ) {
		if ( $_POST['ergo_is_objection'] )
			$is_objection = true;
		else
			$is_objection = false;
		self::set_comment_meta( $comment_id, 'is_objection', $is_objection );
		self::set_comment_meta( $comment_id, 'unanswered_objections', 0 );
		self::update_ancestors( $comment_id );
	}
	
	function on_trashed_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;
		self::update_post_unanswered_objections( $post_id );
	}
	
	function on_deleted_comment( $comment_id ) {
		$comment = get_comment( $comment_id );
		$post_id = $comment->comment_post_ID;
		self::update_post_unanswered_objections( $post_id );
	}
	
	/* Echo methods */
	
	function allow_objections_checkbox() {
		echo '<br /><label><input type="checkbox" name="ergo_allow_objections" checked="checked" /> Allow objections (Ergo plugin).</label>';
	}
	
	function checkbox() {
		$post_id = get_the_id();
		$post_allows_objections = self::get_post_meta( $post_id, 'allows_objections' );
		if ( $post_allows_objections )
			echo '<label><input type="checkbox" class="ergo-checkbox" name="ergo_is_objection" /> This comment is an objection. <a class="ergo-link" onclick="ergo.dialog( this )">What?</a></label>';
	}
	
	function widget() {
		$meta = self::get_meta();
		if ( $meta ) {
			extract( $meta );
			if ( $is_objection or $allows_objections ) {
				if ( $unanswered_objections )
					$color = 'red';
				else
					$color = 'green';
				echo '<span class="ergo-widget ergo-' . $color . '" onclick="ergo.dialog( this )">' . $unanswered_objections . ' unanswered objection' . ( $unanswered_objections == 1 ? '' : 's' ) . '</span>';
			}
		}
	}
	
	function dialog() {
		echo '
		<div class="ergo-dialog">
			<p>When you post a comment, you can mark it as being an objection. If you do, then the post or comment to which you are replying will be marked as having one unanswered objection.</p>
			<p>If your comment receives an objection, then the previous post or comment will return to having no unanswered objections. If that comment receives an objection, then your comment will return to having no unanswered objections, and the original post or comment will return to having one unanswered objection. And so on.</p>
			<p>This simple system leads to a rational and organized discussion, in which a post or comment holds exactly when it has no unanswered objections.</p>
		</div>';
		exit; //Because this method is called via ajax
	}
}