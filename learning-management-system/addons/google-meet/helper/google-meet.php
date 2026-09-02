<?php

defined( 'ABSPATH' ) || exit;

/**
 * Google Meet helper functions.
 *
 * @since 1.11.0 [free]
 *
 * @package Masteriyo\Addons\GoogleMeet
 */

use League\OAuth2\Client\Provider\Google;
use Masteriyo\Addons\GoogleMeet\Models\GoogleMeetSetting;


if ( ! function_exists( 'masteriyo_is_google_meet_credentials_set' ) ) {
	/**
	 * Return true if the Google Meet credentials are set.
	 * Doesn't validate credentials.
	 *
	 * @since 1.11.0 [free]
	 *
	 * @return boolean
	 */
	function masteriyo_is_google_meet_credentials_set() {
		$setting = new GoogleMeetSetting();

		$client_id                   = $setting->get( 'client_id' );
		$project_id                  = $setting->get( 'project_id' );
		$auth_uri                    = $setting->get( 'auth_uri' );
		$token_uri                   = $setting->get( 'token_uri' );
		$auth_provider_x509_cert_url = $setting->get( 'auth_provider_x509_cert_url' );
		$client_secret               = $setting->get( 'client_secret' );
		$redirect_uris               = $setting->get( 'redirect_uris' );

		return ! ( empty( $project_id ) || empty( $client_id ) || empty( $client_secret ) || empty( $auth_uri ) || empty( $token_uri ) || empty( $auth_provider_x509_cert_url ) || empty( $redirect_uris ) );
	}
}

if ( ! function_exists( 'masteriyo_get_google_meet_credits' ) ) {
	/**
	 * Gets the data from the google meet integration setting.
	 *
	 * @since 1.11.0 [free]
	 */
	function masteriyo_get_google_meet_credits( $key = null ) {
		return masteriyo( 'addons.google-meet.setting' )->get( $key );
	}
}

if ( ! function_exists( 'masteriyo_get_google_meet' ) ) {
	/**
	 * Get Google Meet object.
	 *
	 * @since 2.30.0
	 *
	 * @param mixed $google_meet Google Meet object or ID.
	 *
	 * @return \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet|null
	 */
	function masteriyo_get_google_meet( $google_meet ) {
		$google_meet_obj   = masteriyo( 'google-meet' );
		$google_meet_store = masteriyo( 'google-meet.store' );

		if ( is_a( $google_meet, \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet::class ) ) {
			$id = $google_meet->get_id();
		} elseif ( is_a( $google_meet, \WP_Post::class ) ) {
			$id = $google_meet->ID;
		} else {
			$id = $google_meet;
		}

		try {
			$id = absint( $id );
			$google_meet_obj->set_id( $id );
			$google_meet_store->read( $google_meet_obj );
		} catch ( \Exception $e ) {
			$google_meet_obj = null;
		}

		/**
		 * Filters google meet object.
		 *
		 * @since 2.30.0
		 *
		 * @param \Masteriyo\Addons\GoogleMeet\Models\GoogleMeet $google_meet_obj Google Meet object.
		 * @param int|\Masteriyo\Addons\GoogleMeet\Models\GoogleMeet|WP_Post $google_meet Google Meet id or Google Meet Model or Post.
		 */
		return apply_filters( 'masteriyo_get_google_meet', $google_meet_obj, $google_meet );
	}
}

if ( ! function_exists( 'masteriyo_google_calendar_meeting_data_insertion' ) ) {
	/**
	 * Fetches and Returns Google Calendar meeting data.
	 *
	 * @since 1.11.0 [free]
	 */
	function masteriyo_google_calendar_meeting_data_insertion( $access_token, $google_provider ) {

		$request = $google_provider->getAuthenticatedRequest(
			'GET',
			'https://www.googleapis.com/calendar/v3/calendars/primary/events',
			$access_token
		);

		$response      = $google_provider->getResponse( $request );
		$response_data = (string) $response->getBody();
		$object_data   = json_decode( $response_data );
		$object_data   = json_decode( wp_json_encode( $object_data ), true );

		$meetings = array();

		foreach ( $object_data['items'] as $event ) {
				// Handle both dateTime (timed events) and date (all-day events).
				$start_time = isset( $event['start']['dateTime'] ) ? $event['start']['dateTime'] : ( isset( $event['start']['date'] ) ? $event['start']['date'] : '' );
				$end_time   = isset( $event['end']['dateTime'] ) ? $event['end']['dateTime'] : ( isset( $event['end']['date'] ) ? $event['end']['date'] : '' );

				$meeting = array(
					'id'           => $event['id'],
					'summary'      => $event['summary'],
					'description'  => isset( $event['description'] ) ? $event['description'] : '',
					'start'        => $start_time,
					'end'          => $end_time,
					'htmlLink'     => $event['htmlLink'],
					'time_zone'    => $object_data['timeZone'],
					'meeting_link' => isset( $event['hangoutLink'] ) ? $event['hangoutLink'] : '',
				);

				$meetings[] = $meeting;
		}

		$data['meetings'] = $meetings;

		return $data;
	}
}

if ( ! function_exists( 'masteriyo_google_calendar_request' ) ) {
	/**
	 * Perform a Google Calendar API request.
	 *
	 * Every Calendar call in this addon goes through here, so there is exactly one
	 * place that reaches the network — which is what makes the addon testable
	 * without a Google project behind it.
	 *
	 * @param string $method  HTTP method.
	 * @param string $url     Absolute Calendar API endpoint.
	 * @param array  $options Guzzle request options (`headers`, `query`, `json`).
	 *
	 * @return \Psr\Http\Message\ResponseInterface
	 */
	function masteriyo_google_calendar_request( $method, $url, $options = array() ) {
		/**
		 * Filters a Google Calendar API request before it is sent.
		 *
		 * Return a PSR-7 response to short-circuit the request; the network is then
		 * never touched and the returned response is handed to the caller as-is.
		 * Mirrors WordPress core's own `pre_http_request`.
		 *
		 * @param \Psr\Http\Message\ResponseInterface|null $response Short-circuit response, or null to send the request.
		 * @param string                                   $method   HTTP method.
		 * @param string                                   $url      Absolute Calendar API endpoint.
		 * @param array                                    $options  Guzzle request options.
		 */
		$pre = apply_filters( 'masteriyo_pre_google_calendar_request', null, $method, $url, $options );

		if ( null !== $pre ) {
			return $pre;
		}

		return ( new \GuzzleHttp\Client() )->request( $method, $url, $options );
	}
}

if ( ! function_exists( 'create_google_meet_client' ) ) {
	/**
	 * creates the google client based on the google meet setting data,
	 *  this is the basic for validating and accessing access token.
	 *
	 * @since 1.11.0 [free]
	 * @param $google_database_info google meet setting data.
	 */
	function create_google_meet_client( $google_database_info ) {
		$scopes   = array(
			'https://www.googleapis.com/auth/calendar.events',
			'https://www.googleapis.com/auth/calendar',
			'https://www.googleapis.com/auth/calendar.events.readonly',
			'https://www.googleapis.com/auth/calendar.readonly',
			'https://www.googleapis.com/auth/calendar.settings.readonly',
		);
		$provider = new Google(
			array(
				'clientId'     => $google_database_info['client_id'],
				'clientSecret' => $google_database_info['client_secret'],
				'redirectUri'  => home_url( '/wp-admin/admin.php?page=masteriyo' ),
				'scopes'       => $scopes,
				'accessType'   => 'offline',
				'prompt'       => 'consent',
			)
		);
		return $provider;
	}
}
