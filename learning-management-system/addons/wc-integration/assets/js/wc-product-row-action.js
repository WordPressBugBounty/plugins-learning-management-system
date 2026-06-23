/**
 * Masteriyo WooCommerce product list row action — "Create Course" handler.
 *
 * The modal HTML is server-rendered by render_product_row_action_modal() and
 * appended to admin_footer. This script handles show/hide, dynamic data
 * population, and the REST API call to create the course.
 *
 * @since x.x.x
 *
 * @param {Object} data - Localized strings from _MASTERIYO_WC_ROW_ACTION_.
 */
( function ( data ) {
	'use strict';

	if ( typeof data === 'undefined' ) {
		return;
	}

	var MODAL_ID = 'masteriyo-convert-modal-overlay';

	var pending = {
		productId: null,
		link:      null,
	};

	var masteriyoRowAction = {

		/**
		 * Initialize the row action module.
		 *
		 * @since x.x.x
		 */
		init: function () {
			this.bindUIActions();
		},

		/**
		 * Show the conversion confirmation modal for a given product.
		 *
		 * @since x.x.x
		 *
		 * @param {string}      productId        WooCommerce product ID.
		 * @param {string}      productName      Product display name.
		 * @param {string}      productTypeLabel Human-readable WC product type (e.g. "Simple Product").
		 * @param {HTMLElement} link             The "Create Course" anchor that was clicked.
		 */
		showModal: function ( productId, productName, productTypeLabel, link ) {
			pending.productId = productId;
			pending.link      = link;

			document.getElementById( 'masteriyo-modal-product-name' ).textContent = productName;
			document.getElementById( 'masteriyo-modal-type-from' ).textContent    = productTypeLabel;

			var check     = document.getElementById( 'masteriyo-convert-confirm-check' );
			check.checked = false;

			document.getElementById( 'masteriyo-modal-confirm' ).disabled = true;

			document.getElementById( MODAL_ID ).classList.remove( 'masteriyo-hidden' );
		},

		/**
		 * Hide the conversion confirmation modal.
		 *
		 * @since x.x.x
		 */
		hideModal: function () {
			var overlay = document.getElementById( MODAL_ID );
			if ( overlay ) {
				overlay.classList.add( 'masteriyo-hidden' );
			}
		},

		/**
		 * Call the REST API to create a draft Masteriyo course from the WC product,
		 * open the course editor in a new tab, and replace the row action link in place.
		 *
		 * @since x.x.x
		 *
		 * @param {string}      productId WooCommerce product ID.
		 * @param {HTMLElement} link      The "Create Course" anchor element in the product row.
		 */
		runCreateCourse: function ( productId, link ) {
			var originalText         = data.createCourseText;
			link.textContent         = data.creatingText;
			link.style.pointerEvents = 'none';

			// Open the tab synchronously before the async call so browsers don't block it as a popup.
			var newTab = window.open( 'about:blank', '_blank' );

			wp.apiFetch( {
				path:   '/masteriyo/v1/courses/create-from-wc-product',
				method: 'POST',
				data:   { product_id: parseInt( productId, 10 ) },
			} )
				.then( function ( response ) {
					if ( ! response || ! response.course_id || ! response.edit_url ) {
						return;
					}

					if ( newTab ) {
						newTab.location.href = response.edit_url;
					}

					var actionsSpan = link.closest( '.row-actions' );
					if ( ! actionsSpan ) {
						return;
					}

					var createSpan = link.closest( 'span' );
					if ( ! createSpan ) {
						return;
					}

					var editSpan       = document.createElement( 'span' );
					editSpan.className = 'masteriyo-edit-course-action';

					var editLink         = document.createElement( 'a' );
					editLink.href        = response.edit_url;
					editLink.target      = '_blank';
					editLink.rel         = 'noopener noreferrer';
					editLink.textContent = data.editCourseText;
					editSpan.appendChild( editLink );

					var lastChild = createSpan.lastChild;
					if ( lastChild && lastChild.nodeType === Node.TEXT_NODE && lastChild.textContent.indexOf( '|' ) !== -1 ) {
						editSpan.appendChild( document.createTextNode( lastChild.textContent ) );
					}

					actionsSpan.replaceChild( editSpan, createSpan );
				} )
				.catch( function () {
					if ( newTab ) {
						newTab.close();
					}

					link.textContent         = originalText;
					link.style.pointerEvents = '';

					var errorSpan                     = document.createElement( 'span' );
					errorSpan.style.color             = '#cc0000';
					errorSpan.style.marginInlineStart = '4px';
					errorSpan.textContent             = data.errorText;
					link.parentNode.appendChild( errorSpan );

					setTimeout( function () {
						if ( errorSpan.parentNode ) {
							errorSpan.parentNode.removeChild( errorSpan );
						}
					}, 4000 );
				} );
		},

		/**
		 * Bind all UI event listeners for the modal and the "Create Course" row action trigger.
		 *
		 * @since x.x.x
		 */
		bindUIActions: function () {
			var self = this;

			// Checkbox enables / disables the confirm button.
			document.getElementById( 'masteriyo-convert-confirm-check' ).addEventListener( 'change', function () {
				document.getElementById( 'masteriyo-modal-confirm' ).disabled = ! this.checked;
			} );

			// Cancel button closes the modal.
			document.getElementById( 'masteriyo-modal-cancel' ).addEventListener( 'click', function () {
				self.hideModal();
			} );

			// Backdrop click closes the modal.
			document.getElementById( MODAL_ID ).addEventListener( 'click', function ( e ) {
				if ( e.target === this ) {
					self.hideModal();
				}
			} );

			// Confirm button triggers course creation.
			document.getElementById( 'masteriyo-modal-confirm' ).addEventListener( 'click', function () {
				if ( this.disabled ) {
					return;
				}
				self.hideModal();
				self.runCreateCourse( pending.productId, pending.link );
			} );

			// Delegated listener for "Create Course" row action links.
			document.body.addEventListener( 'click', function ( e ) {
				var link = e.target.closest( '.masteriyo-create-course-action' );
				if ( ! link ) {
					return;
				}

				e.preventDefault();

				var productId = link.dataset.productId;
				if ( ! productId ) {
					return;
				}

				// Bail if the product is already a Masteriyo type — PHP skips rendering the
				// link for these, but this guard handles any edge cases.
				var productType = link.dataset.productType || '';
				if ( productType.indexOf( 'mto_' ) === 0 ) {
					return;
				}

				self.showModal(
					productId,
					link.dataset.productName || '',
					link.dataset.productTypeLabel || '',
					link
				);
			} );
		},
	};

	document.addEventListener( 'DOMContentLoaded', function () {
		masteriyoRowAction.init();
	} );

} )( window._MASTERIYO_WC_ROW_ACTION_ );
