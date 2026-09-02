<?php
/**
 * Lesson model.
 *
 * @since 1.0.0
 *
 * @package Masteriyo\Models;
 */

namespace Masteriyo\Models;

use Masteriyo\Database\Model;
use Masteriyo\Enums\VideoSource;
use Masteriyo\Repository\LessonRepository;
use Waynestate\Youtube\ParseId;


defined( 'ABSPATH' ) || exit;

/**
 * Lesson model (post type).
 *
 * @since 1.0.0
 */
class Lesson extends Model {

	/**
	 * Stores data about status changes so relevant hooks can be fired.
	 *
	 * @since 1.6.9
	 *
	 * @var bool|array
	 */
	protected $status_transition = false;

	/**
	 * This is the name of this object type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $object_type = 'lesson';

	/**
	 * Post type.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $post_type = 'mto-lesson';

	/**
	 * Cache group.
	 *
	 * @since 1.0.0
	 *
	 * @var string
	 */
	protected $cache_group = 'lessons';

	/**
	 * Stores lesson data.
	 *
	 * @since 1.0.0
	 *
	 * @var array
	 */
	protected $data = array(
		'name'                    => '',
		'slug'                    => '',
		'date_created'            => null,
		'date_modified'           => null,
		'status'                  => false,
		'menu_order'              => 0,
		'description'             => '',
		'short_description'       => '',
		'post_password'           => '',
		'parent_id'               => 0,
		'course_id'               => 0,
		'author_id'               => 0,
		'reviews_allowed'         => true,
		'featured_image'          => '',
		'video_source'            => '',
		'video_source_url'        => '',
		'enable_preview'          => false,
		'enable_video_preview'    => false,
		'video_playback_time'     => 0,
		'rating_counts'           => array(),
		'average_rating'          => 0,
		'review_count'            => 0,
		'download_materials'      => array(),
		'video_meta'              => array(
			'time_stamps'               => array(),
			'enable_video_share'        => false,
			'enable_right_button_click' => false,

		),
		'starts_at'               => '',
		'ends_at'                 => '',
		'live_chat_enabled'       => true,
		'pdf'                     => array(),
		'pdf_downloadable'        => true,
		'transform_live_to_video' => false,
		'subtitle_meta'           => array(),
		'audio_source'            => '',
		'audio_source_url'        => '',
		'audio_source_files'      => array(),
		'custom_fields'           => null,
		'lesson_type'             => '',
	);

	/**
	 * Get the lesson if ID.
	 *
	 * @since 1.0.0
	 *
	 * @param LessonRepository $lesson_repository Lesson Repository,
	 */
	public function __construct( LessonRepository $lesson_repository ) {
		$this->repository = $lesson_repository;
	}

	/**
	 * Save data to the database.
	 *
	 * @since 1.6.9
	 *
	 * @return int order ID
	 */
	public function save() {
		parent::save();
		$this->status_transition();
		return $this->get_id();
	}

	/**
	 * Handle the status transition.
	 *
	 * @since 1.6.9
	 */
	protected function status_transition() {
		$status_transition = $this->status_transition;

		// Reset status transition variable.
		$this->status_transition = false;

		if ( ! $status_transition ) {
			return;
		}

		/**
		 * Fires after lesson model's status transition.
		 *
		 * @since 1.6.9
		 *
		 * @param \Masteriyo\Models\Lesson $lesson The lesson object.
		 * @param string $old_status Old status.
		 * @param string $new_status New status.
		 */
		do_action( 'masteriyo_lesson_status_changed', $this, $status_transition['from'], $status_transition['to'] );
	}

	/*
	|--------------------------------------------------------------------------
	| Non-CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get the product's title. For products this is the product name.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_title() {
		/**
		 * Filters lesson title.
		 *
		 * @since 1.0.0
		 *
		 * @param string $title Lesson title.
		 * @param Masteriyo\Models\Lesson $lesson Lesson object.
		 */
		return apply_filters( 'masteriyo_lesson_title', $this->get_name(), $this );
	}

	/**
	 * Product permalink.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_permalink() {
		return get_permalink( $this->get_id() );
	}

	/**
	 * Get learn page URL for the lesson.
	 *
	 * @return string
	 */
	public function get_learn_url() {
		$course = masteriyo_get_course( $this->get_course_id() );
		if ( ! $course ) {
			return $this->get_permalink();
		}

		$learn_page_url = masteriyo_get_page_permalink( 'learn' );
		$url            = trailingslashit( $learn_page_url ) . 'course/' . $course->get_slug();

		if ( '' === get_option( 'permalink_structure' ) ) {
			$url = add_query_arg(
				array(
					'course_name' => $course->get_id(),
				),
				$learn_page_url
			);
		}

		$url .= '#/course/' . $course->get_id() . '/lesson/' . $this->get_id();

		return apply_filters( 'masteriyo_lesson_learn_url', $url, $this );
	}

	/**
	 * Returns the children IDs if applicable. Overridden by child classes.
	 *
	 * @since 1.0.0
	 *
	 * @return array Array of IDs.
	 */
	public function get_children() {
		return array();
	}

	/**
	 * Get the object type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_object_type() {
		return $this->object_type;
	}

	/**
	 * Get the post type.
	 *
	 * @since 1.0.0
	 *
	 * @return string
	 */
	public function get_post_type() {
		return $this->post_type;
	}

	/**
	 * Get post preview link.
	 *
	 * @since 1.4.1
	 *
	 * @return string
	 */
	public function get_post_preview_link() {
		$preview_link = get_preview_post_link( $this->get_id() );

		/**
		 * Lesson post preview link.
		 *
		 * @since 1.4.1
		 *
		 * @param string $link Preview link.
		 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
		 */
		return apply_filters( 'masteriyo_lesson_post_preview_link', $preview_link, $this );
	}

	/**
	 * Get preview link in learn page.
	 *
	 * @since 1.4.1
	 *
	 * @return string
	 */
	public function get_preview_link() {
		$preview_link = '';
		$course       = masteriyo_get_course( $this->get_course_id() );

		if ( $course ) {
			$course_preview_link = $course->get_preview_link();
			$preview_link        = trailingslashit( $course_preview_link ) . 'lesson/' . $this->get_id();
		}

		/**
		 * Lesson preview link for learn page.
		 *
		 * @since 1.4.1
		 *
		 * @param string $url Preview link.
		 * @param \Masteriyo\Models\Lesson $lesson Lesson object.
		 */
		return apply_filters( 'masteriyo_lesson_preview_link', $preview_link, $this );
	}

	/**
	 * Get icon.
	 *
	 * @since 1.5.15
	 *
	 * @return string
	 */
	public function get_icon( $context = 'single-course.curriculum.section.content' ) {
		if ( ! empty( $this->get_video_source_url() ) ) {
			if ( 'live-stream' === $this->get_video_source() ) {
				$icon = masteriyo_get_svg( 'live-stream-lesson' );
			} else {
				$icon = masteriyo_get_svg( 'video-lesson' );
			}
		} elseif ( ! empty( $this->get_pdf() ) ) {
			$icon = masteriyo_get_svg( 'pdf-lesson' );
		} elseif ( ! empty( $this->get_audio_source_url() ) || ! empty( $this->get_audio_source_files() ) ) {
			$icon = masteriyo_get_svg( 'audio-lesson' );
		} else {
			$icon = masteriyo_get_svg( 'text-lesson' );
		}
		/**
		 * Filters lesson icon.
		 *
		 * @since 1.5.15
		 *
		 * @param string $icon.
		 * @param string $context.
		 */
		return apply_filters( 'masteriyo_lesson_icon', $icon, $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Getters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Get lesson name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_name( $context = 'view' ) {
		return $this->get_prop( 'name', $context );
	}

	/**
	 * Get lesson slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_slug( $context = 'view' ) {
		return $this->get_prop( 'slug', $context );
	}

	/**
	 * Get lesson created date.
	 *
	 * @since 1.0.0
	 * @since 1.5.32 Return DateTime|NULL
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return \Masteriyo\DateTime|null object if the date is set or null if there is no date.
	 */
	public function get_date_created( $context = 'view' ) {
		return $this->get_prop( 'date_created', $context );
	}

	/**
	 * Get lesson modified date.
	 *
	 * @since 1.0.0
	 * @since 1.5.32 Return DateTime|NULL
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return DateTime|NULL object if the date is set or null if there is no date.
	 */
	public function get_date_modified( $context = 'view' ) {
		return $this->get_prop( 'date_modified', $context );
	}

	/**
	 * Get lesson status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_status( $context = 'view' ) {
		return $this->get_prop( 'status', $context );
	}

	/**
	 * Get catalog visibility.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_catalog_visibility( $context = 'view' ) {
		return $this->get_prop( 'catalog_visibility', $context );
	}

	/**
	 * Get lesson description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_description( $context = 'view' ) {
		return $this->get_prop( 'description', $context );
	}

	/**
	 * Get lesson short description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_short_description( $context = 'view' ) {
		return $this->get_prop( 'short_description', $context );
	}

	/**
	 * Returns the lesson's password.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Lesson's password.
	 */
	public function get_post_password( $context = 'view' ) {
		return $this->get_prop( 'post_password', $context );
	}

	/**
	 * Returns whether review is allowed or not..
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return bool
	 *
	 */
	public function get_reviews_allowed( $context = 'view' ) {
		return $this->get_prop( 'reviews_allowed', $context );
	}

	/**
	 * Returns lesson parent id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int Lesson parent id.
	 */
	public function get_parent_id( $context = 'view' ) {
		return $this->get_prop( 'parent_id', $context );
	}

	/**
	 * Returns the lesson's course id.
	 *
	 * @since  1.0.0
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_course_id( $context = 'view' ) {
		return $this->get_prop( 'course_id', $context );
	}

	/**
	 * Returns the lesson's author id.
	 *
	 * @since  1.3.2
	 *
	 * @param  string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_author_id( $context = 'view' ) {
		return $this->get_prop( 'author_id', $context );
	}

	/**
	 * Returns lesson menu order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int Lesson menu order.
	 */
	public function get_menu_order( $context = 'view' ) {
		return $this->get_prop( 'menu_order', $context );
	}

	/**
	 * Returns lesson featured image.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string Lesson featured image.
	 */
	public function get_featured_image( $context = 'view' ) {
		return $this->get_prop( 'featured_image', $context );
	}

	/**
	 * Get featured image URL.
	 *
	 * @since 2.6.7
	 *
	 * @return string
	 */
	public function get_featured_image_url( $size = 'thumbnail' ) {
		if ( empty( $this->get_featured_image() ) ) {
			return masteriyo_placeholder_img_src( $size );
		}
		return wp_get_attachment_image_url( $this->get_featured_image(), $size );
	}

	/**
	 * Get video source.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_video_source( $context = 'view' ) {
		return $this->get_prop( 'video_source', $context );
	}

	/**
	 * Get video source.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_video_source_url( $context = 'view' ) {
		$source     = $this->get_video_source( 'edit' );
		$source_url = trim( $this->get_prop( 'video_source_url', $context ) );

		if ( 'edit' === $context ) {
			return $source_url;
		}

		if ( VideoSource::SELF_HOSTED === $source && is_numeric( $source_url ) ) {
			$video_url_type = masteriyo_get_setting( 'learn_page.general.lesson_video_url_type' );
			if ( 'default' === $video_url_type ) {
				$source_url = wp_get_attachment_url( $this->get_video_source_id( $context ) );
			} else {
				$source_url = masteriyo_generate_self_hosted_lesson_video_url( $this->get_id() );
			}
		}

		return $source_url;
	}

	/**
	 * Get preview enable.
	 *
	 * @since 2.6.7
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_enable_preview( $context = 'view' ) {
		return $this->get_prop( 'enable_preview', $context );
	}

	/**
	 * Get video preview enable.
	 *
	 * @since 2.7.1
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return boolean
	 */
	public function get_enable_video_preview( $context = 'view' ) {
		return $this->get_prop( 'enable_video_preview', $context );
	}

	/**
	 * Get video source id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_video_source_id( $context = 'view' ) {
		return absint( $this->get_prop( 'video_source_url', $context ) );
	}

	/**
	 * Get embed URL for the featured video. If the video source is self-hosted, it will return the absolute URL to the file.
	 *
	 * @since 2.6.7
	 *
	 * @return string
	 */
	public function get_video_source_embed_url() {
		$featured_video_source = $this->get_video_source();
		$video_id              = $this->get_video_id();
		$embed_url             = '';

		if ( VideoSource::SELF_HOSTED === $featured_video_source || VideoSource::EXTERNAL === $featured_video_source ) {
			$embed_url = $this->get_video_source_url();
		} elseif ( VideoSource::YOUTUBE === $featured_video_source ) {
			$embed_url = 'https://www.youtube.com/embed/' . $video_id;
		} elseif ( VideoSource::VIMEO === $featured_video_source ) {
			$embed_url = 'https://player.vimeo.com/video/' . $video_id;
		}

		/**
		 * Filters video embed URL of a lesson.
		 *
		 * @since 2.6.7
		 *
		 * @param string $embed_url
		 * @param \Masteriyo\Models\Lesson $lesson
		 */
		return apply_filters( 'masteriyo_lesson_video_embed_url', $embed_url, $this );
	}

	/**
	 * Get the id of featured video.
	 *
	 * @since 2.6.7
	 *
	 * @return string|number
	 */
	public function get_video_id() {
		$featured_video_source = $this->get_video_source();
		$featured_video_url    = $this->get_video_source_url();
		$video_id              = 0;

		if ( VideoSource::YOUTUBE === $featured_video_source ) {
			$video_id = ParseId::fromUrl( $featured_video_url );
		} elseif ( VideoSource::VIMEO === $featured_video_source ) {
			$video_id = masteriyo_get_vimeo_id_from_url( $featured_video_url );
		}

		/**
		 * Filters video id of a lesson.
		 *
		 * @since 2.6.7
		 *
		 * @param int $video_id
		 * @param \Masteriyo\Models\Lesson $lesson
		 */
		return apply_filters( 'masteriyo_lesson_video_id', $video_id, $this );
	}


	/**
	 * Get video playback time.
	 *
	 * @since 1.0.0
	 *
	 * @param int $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_video_playback_time( $context = 'view' ) {
		return $this->get_prop( 'video_playback_time', $context );
	}

	/**
	 * Get rating count.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array of counts
	 */
	public function get_rating_counts( $context = 'view' ) {
		return $this->get_prop( 'rating_counts', $context );
	}

	/**
	 * Get average rating.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return float
	 */
	public function get_average_rating( $context = 'view' ) {
		return $this->get_prop( 'average_rating', $context );
	}

	/**
	 * Get review count.
	 *
	 * @since 1.0.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_review_count( $context = 'view' ) {
		return $this->get_prop( 'review_count', $context );
	}

	/**
	 * Get download_materials.
	 *
	 * @since 2.0.2
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_download_materials( $context = 'view' ) {
		return $this->get_prop( 'download_materials', $context );
	}

	/**
	 * Get audio source.
	 *
	 * @since 2.17.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_audio_source( $context = 'view' ) {
		return $this->get_prop( 'audio_source', $context );
	}

	/**
	 * Get audio source url.
	 *
	 * @since 2.17.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_audio_source_url( $context = 'view' ) {
		return $this->get_prop( 'audio_source_url', $context );
	}

	/**
	 * Get audio source files.
	 *
	 * @since 2.17.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_audio_source_files( $context = 'view' ) {
		return $this->get_prop( 'audio_source_files', $context );
	}


	/**
	 * Get subtitle meta info for masteriyo player.
	 *
	 * @since 2.17.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return array
	 */
	public function get_subtitle_meta( $context = 'view' ) {
		$data = $this->get_prop( 'subtitle_meta', $context );

		$filtered_data = array_filter(
			$data,
			function ( $item ) {
				return ! empty( $item );
			}
		);
		return $filtered_data;
	}




	/**
	 * Get video meta info for masteriyo player.
	 *
	 * @since 2.13.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_video_meta( $context = 'view' ) {
		return $this->get_prop( 'video_meta', $context );
	}

	/**
	 * Get starts_at.
	 *
	 * @since 2.12.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_starts_at( $context = 'view' ) {
		return $this->get_prop( 'starts_at', $context );
	}

	/**
	 * Get ends_at.
	 *
	 * @since 2.12.0
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return int
	 */
	public function get_ends_at( $context = 'view' ) {
		return $this->get_prop( 'ends_at', $context );
	}

	/**
	 * Get Live Chat Enabled.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_live_chat_enabled( $context = 'view' ) {
		return $this->get_prop( 'live_chat_enabled', $context );
	}

		/**
	 * Get PDF.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return object
	 */
	public function get_pdf( $context = 'view' ) {
		return $this->refresh_attachment_urls( $this->get_prop( 'pdf', $context ) );
	}

	/**
	 * Re-resolve stored attachment URLs from their attachment IDs.
	 *
	 * A stored PDF URL can become stale if WordPress later re-sanitizes or
	 * renames the underlying media file (for example, stripping spaces from
	 * the filename), which results in a 404 when the player fetches it. When
	 * an attachment ID is present it is the source of truth, so the current
	 * URL is resolved from it. Items without an ID (externally-hosted or
	 * legacy data) keep their stored URL for backward compatibility.
	 *
	 * @param array $pdf PDF items, each with an `id` and `url`.
	 *
	 * @return array
	 */
	protected function refresh_attachment_urls( $pdf ) {
		if ( empty( $pdf ) || ! is_array( $pdf ) ) {
			return $pdf;
		}

		foreach ( $pdf as $index => $item ) {
			$item = (array) $item;

			if ( empty( $item['id'] ) ) {
				continue;
			}

			$url = wp_get_attachment_url( (int) $item['id'] );

			if ( $url ) {
				$item['url'] = $url;
			}

			$pdf[ $index ] = $item;
		}

		return $pdf;
	}

	/**
	 * Get transform live to normal video.
	 *
	 * @since 1.16.0 [Free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return string
	 */
	public function get_transform_live_to_video( $context = 'view' ) {
		return $this->get_prop( 'transform_live_to_video', $context );
	}

	/**
	 * Get Custom fields values.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return object
	 */
	public function get_custom_fields( $context = 'view' ) {
		return $this->get_prop( 'custom_fields', $context );
	}

	/**
	 * Get lesson type.
	 *
	 * @since 1.11.3 [Free]
	 *
	 * @param string $context What the value is for. Valid values are view and edit.
	 *
	 * @return object
	 */
	public function get_lesson_type( $context = 'view' ) {
		return $this->get_prop( 'lesson_type', $context );
	}

	/*
	|--------------------------------------------------------------------------
	| CRUD Setters
	|--------------------------------------------------------------------------
	*/

	/**
	 * Set lesson name.
	 *
	 * @since 1.0.0
	 *
	 * @param string $name lesson name.
	 */
	public function set_name( $name ) {
		$this->set_prop( 'name', $name );
	}

	/**
	 * Set lesson slug.
	 *
	 * @since 1.0.0
	 *
	 * @param string $slug lesson slug.
	 */
	public function set_slug( $slug ) {
		$this->set_prop( 'slug', $slug );
	}

	/**
	 * Set lesson created date.
	 *
	 * @since 1.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_created( $date = null ) {
		$this->set_date_prop( 'date_created', $date );
	}

	/**
	 * Set lesson modified date.
	 *
	 * @since 1.0.0
	 *
	 * @param string|integer|null $date UTC timestamp, or ISO 8601 DateTime. If the DateTime string has no timezone or offset, WordPress site timezone will be assumed. Null if their is no date.
	 */
	public function set_date_modified( $date = null ) {
		$this->set_date_prop( 'date_modified', $date );
	}

	/**
	 * Set lesson status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $new_status lesson status.
	 */
	public function set_status( $new_status ) {
		$old_status = $this->get_status();

		$this->set_prop( 'status', $new_status );

		if ( true === $this->object_read && ! empty( $old_status ) && $old_status !== $new_status ) {
			$this->status_transition = array(
				'from' => ! empty( $this->status_transition['from'] ) ? $this->status_transition['from'] : $old_status,
				'to'   => $new_status,
			);
		}
	}

	/**
	 * Set lesson description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $description Lesson description.
	 */
	public function set_description( $description ) {
		$this->set_prop( 'description', $description );
	}

	/**
	 * Set lesson short description.
	 *
	 * @since 1.0.0
	 *
	 * @param string $short_description Lesson short description.
	 */
	public function set_short_description( $short_description ) {
		$this->set_prop( 'short_description', $short_description );
	}

	/**
	 * Set the lesson's password.
	 *
	 * @since 1.0.0
	 *
	 * @param string $password Password.
	 */
	public function set_post_password( $password ) {
		$this->set_prop( 'post_password', $password );
	}

	/**
	 * Set the lesson's review status.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reviews_allowed Reviews allowed.( Value can be 'open' or 'closed')
	 */
	public function set_reviews_allowed( $reviews_allowed ) {
		$this->set_prop( 'reviews_allowed', $reviews_allowed );
	}

	/**
	 * Set the lesson parent id.
	 *
	 * @since 1.0.0
	 *
	 * @param string $parent Parent id.
	 */
	public function set_parent_id( $parent ) {
		$this->set_prop( 'parent_id', absint( $parent ) );
	}

	/**
	 * Set the lesson's course id.
	 * @since 1.0.0
	 *
	 * @param int $course_id Course id.
	 */
	public function set_course_id( $course_id ) {
		$this->set_prop( 'course_id', absint( $course_id ) );
	}


	/**
	 * Set the lesson's author id.
	 *
	 * @since 1.3.2
	 *
	 * @param int $author_id author id.
	 */
	public function set_author_id( $author_id ) {
		$this->set_prop( 'author_id', absint( $author_id ) );
	}

	/**
	 * Set the lesson menu order.
	 *
	 * @since 1.0.0
	 *
	 * @param string $menu_order Menu order id.
	 */
	public function set_menu_order( $menu_order ) {
		$this->set_prop( 'menu_order', absint( $menu_order ) );
	}

	/**
	 * Set the featured image, in other words thumbnail post id.
	 *
	 * @since 1.0.0
	 *
	 * @param int $featured_image Featured image id.
	 */
	public function set_featured_image( $featured_image ) {
		$this->set_prop( 'featured_image', absint( $featured_image ) );
	}

	/**
	 * Set video source.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_source Video source.
	 */
	public function set_video_source( $video_source ) {
		$this->set_prop( 'video_source', $video_source );
	}

	/**
	 * Set video source url.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_source_url Video source url.
	 */
	public function set_video_source_url( $video_source_url ) {
		$this->set_prop( 'video_source_url', trim( $video_source_url ) );
	}

	/**
	 * Set enable preview.
	 *
	 * @since 2.6.7
	 *
	 * @param string $enable Enable preview
	 */
	public function set_enable_preview( $enable ) {
		$this->set_prop( 'enable_preview', masteriyo_string_to_bool( $enable ) );
	}

	/**
	 * Set video preview enable.
	 *
	 * @since 2.7.1
	 *
	 * @param string $enable Enable preview
	 */
	public function set_enable_video_preview( $enable ) {
		$this->set_prop( 'enable_video_preview', masteriyo_string_to_bool( $enable ) );
	}

	/**
	 * Set video playback time.
	 *
	 * @since 1.0.0
	 *
	 * @param string $video_playback_time Video playback time.
	 */
	public function set_video_playback_time( $video_playback_time ) {
		$this->set_prop( 'video_playback_time', absint( $video_playback_time ) );
	}

	/**
	 * Set rating counts. Read only.
	 *
	 * @since 1.0.0
	 *
	 * @param array $counts Product rating counts.
	 */
	public function set_rating_counts( $counts ) {
		$counts = array_map( 'absint', (array) $counts );
		$this->set_prop( 'rating_counts', array_filter( $counts ) );
	}

	/**
	 * Set average rating. Read only.
	 *
	 * @since 1.0.0
	 *
	 * @param float $average Product average rating.
	 */
	public function set_average_rating( $average ) {
		$this->set_prop( 'average_rating', round( floatval( $average ), 2 ) );
	}

	/**
	 * Set review count. Read only.
	 *
	 * @since 1.0.0
	 *
	 * @param int $count Product review count.
	 */
	public function set_review_count( $count ) {
		$this->set_prop( 'review_count', absint( $count ) );
	}

	/**
	 * Set download_materials.
	 *
	 * @since 2.0.2
	 *
	 * @param array $download_materials Attachment IDs or URLs.
	 */
	public function set_download_materials( $download_materials ) {
		$this->set_prop( 'download_materials', $download_materials );
	}


	/**
	 * Set subtitle meta info for masteriyo player.
	 *
	 * @since 2.17.0
	 *
	 * @param array $subtitle meta The array subtitle meta info like label, type, kind, etc.
	 * @param bool $delete The delete has the condition to delete or update subtitle meta.
	*/
	public function set_subtitle_meta( $subtitle_meta ) {

		if ( is_array( $subtitle_meta ) ) {
				$this->set_prop( 'subtitle_meta', $subtitle_meta );
		}
	}







	/**
	 * Set audio source.
	 *
	 * @since 2.17.0
	 *
	 * @param string $audio_source audio source.
	 */
	public function set_audio_source( $audio_source ) {
		$this->set_prop( 'audio_source', $audio_source );
	}

	/**
	 * Set audio source url.
	 *
	 * @since 2.17.0
	 *
	 * @param string $audio_source_url Audio source url.
	 */
	public function set_audio_source_url( $audio_source_url ) {
		$this->set_prop( 'audio_source_url', masteriyo_sanitize_media_embed( $audio_source_url ) );
	}

	/**
	 * Set audio source files.
	 *
	 * @since 2.17.0
	 *
	 * @param string $audio_source_files Audio source files.
	 */
	public function set_audio_source_files( $audio_source_files ) {
		$this->set_prop( 'audio_source_files', $audio_source_files );
	}

	/**
	 * Set video meta info for masteriyo player.
	 *
	 * @since 2.13.0
	 *
	 * @param array $video_meta The array video meta info like timestamps, notes, enable_video_share, etc.
	 */
	public function set_video_meta( $video_meta ) {
		$this->set_prop( 'video_meta', $video_meta );
	}


	/**
		 * Set starts_at.
		 *
		 * @since 2.12.0
		 *
		 * @param string $starts_at timestamp.
		 */
	public function set_starts_at( $starts_at ) {
		$this->set_prop( 'starts_at', $starts_at );
	}

	/**
	 * Set ends_at.
	 *
	 * @since 2.12.0
	 *
	 * @param string $starts_at timestamp.
	 */
	public function set_ends_at( $starts_at ) {
		$this->set_prop( 'ends_at', $starts_at );
	}

	/**
	 * Set live chat enabled.
	 *
	 * @since 2.12.0
	 *
	 * @param string $live_chat_enabled Live Chat Enabled.
	 */
	public function set_live_chat_enabled( $live_chat_enabled ) {
		$this->set_prop( 'live_chat_enabled', masteriyo_string_to_bool( $live_chat_enabled ) );
	}

	/**
	 * Set PDF
	 *
	 * @since 2.12.0
	 *
	 * @param object $pdf PDF.
	 */
	public function set_pdf( $pdf ) {
		$this->set_prop( 'pdf', ( $pdf ) );
	}

	/**
	 * Get pdf downloadable.
	 *
	 * @param string $context Context.
	 * @return boolean
	 */
	public function get_pdf_downloadable( $context = 'view' ) {
		return $this->get_prop( 'pdf_downloadable', $context );
	}

	/**
	 * Set pdf downloadable.
	 *
	 * @param bool $pdf_downloadable Whether PDF is downloadable.
	 */
	public function set_pdf_downloadable( $pdf_downloadable ) {
		$this->set_prop( 'pdf_downloadable', masteriyo_string_to_bool( $pdf_downloadable ) );
	}

	/**
	 * Set transform live to normal video.
	 *
	 * @since 2.12.0
	 *
	 * @param string $transform_live_to_video Transform Live To Video.
	 */
	public function set_transform_live_to_video( $transform_live_to_video ) {
		$this->set_prop( 'transform_live_to_video', masteriyo_string_to_bool( $transform_live_to_video ) );
	}

	/**
	 * Set Custom fields values.
	 *
	 * @since 2.12.0
	 *
	 * @param object $custom_fields Custom fields values.
	 */
	public function set_custom_fields( $custom_fields ) {
		$this->set_prop( 'custom_fields', masteriyo_sanitize_custom_fields( $custom_fields ) );
	}

	/**
	 * Set lesson type
	 *
	 * @since 2.12.0
	 *
	 * @param string $type lesson type.
	 */
	public function set_lesson_type( $type ) {
		$this->set_prop( 'lesson_type', $type );
	}
}
