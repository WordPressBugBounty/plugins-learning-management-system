(function ($, localized) {
	var mto = {
		currentDocType: localized.is_elementor_template
			? elementor.config.document.type
			: '',
		docTypes: {
			courseArchivePage: 'masteriyo-course-archive-page',
			singleCoursePage: 'masteriyo-single-course-page',
		},

		init: function () {
			mto.initElementorComponents();
			mto.initDocumentLoadHandler();
			mto.initNewTemplateDialog();
			mto.initViewModeControlSync();
		},

		initElementorComponents: function () {
			$e.components.register(new MasteriyoLibraryComponent());
		},

		initDocumentLoadHandler: function () {
			elementor.on('document:loaded', (isFirstLoad) => {
				mto.maybeOpenLibraryModal();
				mto.initLibraryModalOpenBtn();
				mto.maybeActivateNewTemplate();
			});
		},

		initLibraryModalOpenBtn: function () {
			if (mto.isMasteriyoDocumentType()) {
				var previewIframe = window['elementor-preview-iframe'];
				var previewWindow = previewIframe.contentWindow;

				if (
					previewWindow &&
					!previewWindow.jQuery('.masteriyo-templates-button').length
				) {
					mto.addLibraryModalOpenBtn(previewWindow);
				}
			}
		},

		addLibraryModalOpenBtn: function (previewWindow) {
			previewWindow
				.jQuery('.elementor-add-template-button')
				.after(localized.library_btn_template);

			previewWindow
				.jQuery(previewWindow.document.body)
				.on('click', '.masteriyo-templates-button', function () {
					mto.openLibraryModal();
				});
		},

		openLibraryModal: function () {
			$e.components.components['masteriyo-library'].open();
		},

		maybeOpenLibraryModal: function () {
			if (mto.isMasteriyoDocumentType() && mto.isEmptyDocument()) {
				mto.openLibraryModal();
			}
		},

		isMasteriyoDocumentType: function () {
			return Object.values(mto.docTypes).includes(mto.currentDocType);
		},

		// ── "Set as active template" checkbox in the New Template dialog ────

		initNewTemplateDialog: function () {
			// Inject/remove checkbox when the user changes the template type dropdown.
			$(document).on(
				'change',
				'#elementor-new-template__form__template-type',
				mto.onNewTemplateTypeChange
			);

			// Store the intent in sessionStorage before the dialog redirects to the new editor.
			$(document).on('submit', '#elementor-new-template__form', function () {
				if ($('#masteriyo-set-as-active-template').is(':checked')) {
					var type = $('#elementor-new-template__form__template-type').val();
					sessionStorage.setItem('masteriyo_set_active_template_type', type);
				}
			});
		},

		onNewTemplateTypeChange: function () {
			var type = $(this).val();
			var isMasteriyoType =
				type === mto.docTypes.singleCoursePage ||
				type === mto.docTypes.courseArchivePage;

			$('#masteriyo-set-as-active-template-row').remove();

			if (!isMasteriyoType) {
				return;
			}

			var label =
				type === mto.docTypes.singleCoursePage
					? localized.i18n.set_as_single_course_template
					: localized.i18n.set_as_course_archive_template;

			var $row = $(
				'<div id="masteriyo-set-as-active-template-row" style="margin-top:12px;">' +
					'<label style="display:flex;align-items:center;gap:8px;cursor:pointer;">' +
					'<input type="checkbox" id="masteriyo-set-as-active-template" />' +
					'<span>' + label + '</span>' +
					'</label>' +
					'</div>'
			);

			// Insert after the "Name your template" field (last form field before the button).
			$(this).closest('.elementor-form-field').after($row);
		},

		// Called on every document:loaded. Runs once for the newly-created template
		// if the checkbox was checked when the user clicked "Create Template".
		maybeActivateNewTemplate: function () {
			var type = sessionStorage.getItem('masteriyo_set_active_template_type');
			if (!type) {
				return;
			}

			// Only activate when the open document matches the stored type, so a
			// stale flag can't activate an unrelated template.
			if (elementor.config.document.type !== type) {
				return;
			}

			sessionStorage.removeItem('masteriyo_set_active_template_type');

			var templateId = elementor.config.document.id;
			if (!templateId) {
				return;
			}

			var settingsKey =
				type === mto.docTypes.singleCoursePage ? 'single_course' : 'course_archive';

			var payload = {};
			payload[settingsKey] = {
				display: {
					template: {
						custom_template: {
							enable: true,
							template_source: 'elementor',
							template_id: templateId,
						},
					},
				},
			};

			$.ajax({
				url: localized.rest_url + 'masteriyo/v1/settings',
				method: 'POST',
				contentType: 'application/json',
				data: JSON.stringify(payload),
				beforeSend: function (xhr) {
					xhr.setRequestHeader('X-WP-Nonce', localized.nonce);
				},
				success: function () {
					if (elementorCommon && elementorCommon.dialogsManager) {
						var dialog = elementorCommon.dialogsManager.createWidget('alert', {
							message: localized.i18n.template_activated,
						});
						if (dialog) {
							dialog.show();
						}
					}
				},
				error: function ( xhr, status, error ) {
					window.console && window.console.error( 'Masteriyo: failed to activate template', status, error );
				},
			});
		},

		isEmptyDocument: function () {
			return !elementor.config.document.elements?.length;
		},

		// ── Courses Toolbar: View Mode panel control ───────────────────────
		// The view-mode switcher exists only on the Default layout, so hide its
		// toggle whenever a Course List on the canvas uses another layout.

		pageHasNonDefaultCourseList: function () {
			var found = false;
			var walk = function (models) {
				models.forEach(function (model) {
					if (model.get('widgetType') === 'masteriyo-course-list') {
						var layout = model.get('settings').get('layout');
						if (layout && 'default' !== layout) {
							found = true;
						}
					}
					var children = model.get('elements');
					if (children) {
						walk(children.models);
					}
				});
			};
			walk(elementor.elements.models);
			return found;
		},

		syncViewModeControl: function () {
			$('#elementor-panel .elementor-control-show_view_mode').toggle(
				!mto.pageHasNonDefaultCourseList()
			);
		},

		initViewModeControlSync: function () {
			// When the Courses Toolbar panel opens.
			elementor.hooks.addAction(
				'panel/open_editor/widget/masteriyo-courses-toolbar',
				function () {
					setTimeout(mto.syncViewModeControl);
				}
			);

			// After any element-settings change (covers panel edits, undo/redo and
			// programmatic changes), re-sync if a Course List was touched.
			class MtoSyncViewModeHook extends $e.modules.hookUI.After {
				getCommand() {
					return 'document/elements/settings';
				}

				getId() {
					return 'masteriyo-sync-view-mode-control';
				}

				getConditions(args) {
					var containers = args.containers || [args.container];
					return containers.some(function (container) {
						return (
							container &&
							container.model &&
							container.model.get('widgetType') === 'masteriyo-course-list'
						);
					});
				}

				apply() {
					mto.syncViewModeControl();
				}
			}

			$e.hooks.registerUIAfter(new MtoSyncViewModeHook());
		},

		importWidgetsTemplate: function (template) {
			var targetContainer = elementor.getPreviewContainer();
			var index = undefined;

			template.forEach((model) => {
				// If is inner create section for `inner-section`.
				if (model.isInner) {
					var section = $e.run('document/elements/create', {
						container: targetContainer,
						model: {
							elType: 'section',
						},
						columns: 1,
						options: {
							at: index,
							edit: false,
						},
					});

					// `targetContainer` = first column at `section`.
					targetContainer = section.view.children.findByIndex(0).getContainer();
				}

				$e.run('document/elements/create', {
					containers: [targetContainer],
					model,
					options: { at: index, clone: true, edit: false },
				});
			});
		},
	};

	class LogoView extends Marionette.ItemView {
		getTemplate() {
			return '#tmpl-masteriyo-templates-modal__header__logo';
		}

		className() {
			return 'elementor-templates-modal__header__logo';
		}

		events() {
			return {
				click: 'onClick',
			};
		}

		templateHelpers() {
			return {
				title: this.getOption('title'),
			};
		}

		onClick() {
			var clickCallback = this.getOption('click');

			if (clickCallback) {
				clickCallback();
			}
		}
	}

	var HeaderActionsView = Marionette.ItemView.extend({
		template: '#tmpl-masteriyo-template-library-header-actions',
		id: 'masteriyo-template-library-header-actions',
	});

	// ── Layout picker (single course only) ──────────────────────────────────

	var layouts = [
		{
			key: 'default',
			label: 'Simple',
			templateKey: 'single_course_page',
			imageUrl: localized.single_course_layout_images?.default || '',
		},
		{
			key: 'layout1',
			label: 'Modern',
			templateKey: 'single_course_page_layout1',
			imageUrl: localized.single_course_layout_images?.layout1 || '',
		},
		{
			key: 'minimal',
			label: 'Minimal',
			templateKey: 'single_course_page_minimal',
			imageUrl: localized.single_course_layout_images?.minimal || '',
		},
		{
			key: 'custom',
			label: 'Custom',
			templateKey: null,
		},
	];

	var LayoutPickerView = Marionette.ItemView.extend({
		id: 'masteriyo-layout-picker',

		template: '#tmpl-masteriyo-layout-picker',

		ui: {
			card: '.masteriyo-layout-card',
			useBtn: '.masteriyo-layout-use-btn',
		},

		events: {
			'click @ui.useBtn': 'onUseClick',
			'click @ui.card': 'onCardClick',
		},

		onCardClick: function (e) {
			var $card = $(e.currentTarget);
			this.$('.masteriyo-layout-card').removeClass('is-selected');
			$card.addClass('is-selected');
		},

		onUseClick: function (e) {
			var key = $(e.currentTarget).closest('.masteriyo-layout-card').data('layout');
			var layout = layouts.find(function (l) { return l.key === key; });
			if (layout && layout.templateKey && localized.page_templates[layout.templateKey]) {
				mto.importWidgetsTemplate(localized.page_templates[layout.templateKey]);
			}
			// Custom layout: close modal and leave the canvas blank for the user to build.
			$('.elementor-templates-modal__header__close').click();
		},
	});

	// ── Archive layout picker ───────────────────────────────────────────────

	var archiveLayouts = [
		{
			key: 'default',
			label: 'Default',
			templateKey: 'course_archive_page',
		},
		{
			key: 'layout1',
			label: 'Modern',
			templateKey: 'course_archive_page_layout1',
		},
		{
			key: 'layout2',
			label: 'Overlay',
			templateKey: 'course_archive_page_layout2',
		},
		{
			key: 'custom',
			label: 'Custom',
			templateKey: null,
		},
	];

	var ArchiveLayoutPickerView = Marionette.ItemView.extend({
		id: 'masteriyo-archive-layout-picker',

		template: '#tmpl-masteriyo-archive-layout-picker',

		ui: {
			card: '.masteriyo-layout-card',
			useBtn: '.masteriyo-layout-use-btn',
		},

		events: {
			'click @ui.useBtn': 'onUseClick',
			'click @ui.card': 'onCardClick',
		},

		onRender: function () {
			// Pre-select the layout that matches the current Masteriyo courses setting.
			var active = localized.course_archive_active_layout || 'default';
			this.$('[data-layout="' + active + '"]').addClass('is-selected');
		},

		onCardClick: function (e) {
			var $card = $(e.currentTarget);
			this.$('.masteriyo-layout-card').removeClass('is-selected');
			$card.addClass('is-selected');
		},

		onUseClick: function (e) {
			var key = $(e.currentTarget).closest('.masteriyo-layout-card').data('layout');
			var layout = archiveLayouts.find(function (l) { return l.key === key; });
			if (layout && layout.templateKey && localized.page_templates[layout.templateKey]) {
				mto.importWidgetsTemplate(localized.page_templates[layout.templateKey]);
			}
			// Custom layout: close modal and leave the canvas blank.
			$('.elementor-templates-modal__header__close').click();
		},
	});

	// ── Modal layout ────────────────────────────────────────────────────────

	var ModalLayoutView = elementorModules.common.views.modal.Layout.extend({
		getModalOptions() {
			return {
				id: 'masteriyo-template-library-modal',
			};
		},

		showLogo() {
			this.getHeaderView().logoArea.show(new LogoView(this.getLogoOptions()));
		},

		getLogoOptions() {
			return {
				title: 'Choose a layout',
			};
		},

		setHeaderDefaultParts() {
			this.getHeaderView().tools.show(new HeaderActionsView());
			this.showLogo();
		},

		showPreviewView() {
			if (mto.currentDocType === mto.docTypes.singleCoursePage) {
				this.modalContent.show(new LayoutPickerView());
			} else if (mto.currentDocType === mto.docTypes.courseArchivePage) {
				this.modalContent.show(new ArchiveLayoutPickerView());
			}
		},
	});

	class MasteriyoLibraryComponent extends elementorCommon.api.modules
		.ComponentModalBase {
		__construct(args) {
			super.__construct(args);
			$e.data.deleteCache(this, 'masteriyo-library'); // Remove whole component cache data.
		}

		getNamespace() {
			return 'masteriyo-library';
		}

		open() {
			super.open();
			this.layout.setHeaderDefaultParts();
			this.layout.showPreviewView();
			return true;
		}

		close() {
			if (!super.close()) {
				return false;
			}
			this.manager.modalConfig = {};
			return true;
		}

		getModalLayout() {
			return ModalLayoutView;
		}
	}

	mto.init();
})(jQuery, _MASTERIYO_ELEMENTOR_EDITOR_);
