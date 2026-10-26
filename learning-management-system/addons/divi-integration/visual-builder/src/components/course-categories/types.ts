// Divi dependencies.
import { ModuleEditProps } from '@divi/module-library';
import { type InternalAttrs, type Element } from '@divi/types';

export interface CourseCategoriesModuleAttrs extends InternalAttrs {
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

	// Settings-only content attributes — no on-canvas DOM element of their
	// own, they only steer the frontend query/output (see
	// Helper::render_course_categories_list()). All of them store a plain
	// string in .value, so one shape covers all of them.
	perPage?: StaticContentAttr<string>;
	columns?: StaticContentAttr<string>;
	include_sub_categories?: StaticContentAttr<string>;
	orderBy?: StaticContentAttr<string>;
	order?: StaticContentAttr<string>;
	show_thumbnail?: StaticContentAttr<string>;
	show_details?: StaticContentAttr<string>;
	show_title?: StaticContentAttr<string>;
	show_courses_count?: StaticContentAttr<string>;
}

interface StaticContentAttr<T> {
	innerContent?: {
		desktop?: {
			value?: T;
		};
	};
}

export type CourseCategoriesModuleEditProps =
	ModuleEditProps<CourseCategoriesModuleAttrs>;
