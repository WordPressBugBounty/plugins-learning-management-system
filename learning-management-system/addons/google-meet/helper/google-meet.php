<?php

/**
 * Google Meet helper functions.
 *
 * @since 1.11.0
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
	 * @since 1.11.0
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
	 * @since 1.11.0
	 */
	function masteriyo_get_google_meet_credits( $key = null ) {
		return masteriyo( 'addons.google-meet.setting' )->get( $key );
	}
}

if ( ! function_exists( 'masteriyo_google_calendar_meeting_data_insertion' ) ) {
	/**
	 * Fetches and Returns Google Calendar meeting data.
	 *
	 * @since 1.11.0
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
				$meeting = array(
					'id'           => $event['id'],
					'summary'      => $event['summary'],
					'description'  => $event['description'],
					'start'        => $event['start']['dateTime'],
					'end'          => $event['end']['dateTime'],
					'htmlLink'     => $event['htmlLink'],
					'time_zone'    => $object_data['timeZone'],
					'meeting_link' => $event['hangoutLink'],
				);

				$meetings[] = $meeting;
		}

		$data['meetings'] = $meetings;

		return $data;
	}
}

if ( ! function_exists( 'create_google_meet_client' ) ) {
	/**
	 * creates the google client based on the google meet setting data,
	 *  this is the basic for validating and accessing access token.
	 *
	 * @since 1.11.0
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
