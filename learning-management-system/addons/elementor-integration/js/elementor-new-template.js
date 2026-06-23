(function ($, localized) {
	var SINGLE_COURSE_TYPE = 'masteriyo-single-course-page';
	var COURSE_ARCHIVE_TYPE = 'masteriyo-course-archive-page';
	var MASTERIYO_TYPES = [ SINGLE_COURSE_TYPE, COURSE_ARCHIVE_TYPE ];

	var TYPE_SELECT = '#elementor-new-template__form__template-type';
	var FORM = '#elementor-new-template__form';
	var CHECKBOX_ROW_ID = 'masteriyo-set-as-active-template-row';
	var CHECKBOX_ID = 'masteriyo-set-as-active-template';
	var SESSION_KEY = 'masteriyo_set_active_template_type';

	function getSelectedType() {
		return $( TYPE_SELECT ).val();
	}

	function injectCheckbox( type ) {
		$( '#' + CHECKBOX_ROW_ID ).remove();

		if ( -1 === MASTERIYO_TYPES.indexOf( type ) ) {
			return;
		}

		var label =
			type === SINGLE_COURSE_TYPE
				? localized.i18n.set_as_single_course_template
				: localized.i18n.set_as_course_archive_template;

		var $row = $(
			'<div id="' + CHECKBOX_ROW_ID + '" style="margin-top:12px;">' +
				'<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">' +
					'<input type="checkbox" id="' + CHECKBOX_ID + '" />' +
					'<span>' + label + '</span>' +
				'</label>' +
			'</div>'
		);

		// Insert after the last visible form field (before the submit button).
		$( TYPE_SELECT ).closest( '.elementor-form-field' ).after( $row );
	}

	function onTypeChange() {
		injectCheckbox( getSelectedType() );
	}

	function onFormSubmit() {
		if ( $( '#' + CHECKBOX_ID ).is( ':checked' ) ) {
			sessionStorage.setItem( SESSION_KEY, getSelectedType() );
		}
	}

	function bindEvents() {
		// Elementor renders the modal content before binding events on the select,
		// so delegation on document catches the change regardless of render timing.
		$( document ).on( 'change', TYPE_SELECT, onTypeChange );
		$( document ).on( 'submit', FORM, onFormSubmit );

		// When the modal opens, run once so an already-selected Masteriyo type shows the checkbox.
		$( document ).on( 'click', '#elementor-template-library-add-new, a.page-title-action[href*="elementor_library"]', function () {
			// The modal is shown asynchronously; wait a tick for the DOM to be ready.
			setTimeout( function () {
				injectCheckbox( getSelectedType() );
			}, 100 );
		} );
	}

	$( bindEvents );
})( jQuery, _MASTERIYO_ELEMENTOR_NEW_TEMPLATE_ );
