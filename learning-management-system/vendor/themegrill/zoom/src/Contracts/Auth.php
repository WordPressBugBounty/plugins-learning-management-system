<?php
/**
 * Auth contracts.
 *
 * @since 2.5.19
 *
 * @package Masteriyo\packages\Zoom
 */


namespace ThemeGrill\Zoom\Contracts;

defined( 'ABSPATH' ) || exit;


interface Auth {

	public function set_credentials();

	public function get_token();

	public function authorize();
}
