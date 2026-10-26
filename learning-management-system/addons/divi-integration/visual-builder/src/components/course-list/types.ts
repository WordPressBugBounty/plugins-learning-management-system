// Divi dependencies.
import { ModuleEditProps } from '@divi/module-library';
import { type InternalAttrs, type Element } from '@divi/types';

export interface CourseListModuleAttrs extends InternalAttrs {
	module?: {
		meta?: Element.Meta.Attributes;
		advanced?: {
			html?: Element.Advanced.Html.Attributes;
			htmlAttributes?: Element.Advanced.IdClasses.Attributes;
		};
		decoration?: Element.Decoration.PickedAttributes<
			| 'background'
			| 'border'
			| 'boxShadow'
			| 'disabledOn'
			| 'sizing'
			| 'spacing'
		>;
	};

	// Settings-only content attributes — no on-canvas DOM element of their own,
	// they only steer the frontend query/output (see Helper::render_course_list()
	// and Helper::render_toggle_hide_css()). One shape covers all of them:
	// scalar fields (postsPerPage, order, orderBy, columns, the 16 show_*
	// toggles) store a string in .value, the two category checkbox fields
	// store a string[] instead — both fit StaticContentAttr<T>.
	postsPerPage?: StaticContentAttr<string>;
	order?: StaticContentAttr<string>;
	orderBy?: StaticContentAttr<string>;
	columns?: StaticContentAttr<string>;
	show_pagination?: StaticContentAttr<string>;
	include_categories?: StaticContentAttr<string[]>;
	exclude_categories?: StaticContentAttr<string[]>;
	show_thumbnail?: StaticContentAttr<string>;
	show_difficulty_badge?: StaticContentAttr<string>;
	show_categories?: StaticContentAttr<string>;
	show_course_title?: StaticContentAttr<string>;
	show_author?: StaticContentAttr<string>;
	show_author_avatar?: StaticContentAttr<string>;
	show_author_name?: StaticContentAttr<string>;
	show_rating?: StaticContentAttr<string>;
	show_description?: StaticContentAttr<string>;
	show_metadata?: StaticContentAttr<string>;
	show_course_duration?: StaticContentAttr<string>;
	show_students_count?: StaticContentAttr<string>;
	show_lessons_count?: StaticContentAttr<string>;
	show_footer?: StaticContentAttr<string>;
	show_price?: StaticContentAttr<string>;
	show_enroll_button?: StaticContentAttr<string>;
}

interface StaticContentAttr<T> {
	innerContent?: {
		desktop?: {
			value?: T;
		};
	};
}

export type CourseListModuleEditProps = ModuleEditProps<CourseListModuleAttrs>;
