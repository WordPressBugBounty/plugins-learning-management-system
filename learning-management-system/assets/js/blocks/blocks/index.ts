import { registerBlockType } from '@wordpress/blocks';
import { createElement } from '@wordpress/element';
import { applyFilters } from '@wordpress/hooks';
import { blockIcons } from './../components/icon';

import courseAuthor from './course-author';
import courseCategories from './course-categories';
import courseCategory from './course-category';
import courseComingSoon from './course-coming-soon';
import courseContent from './course-contents';
import courseCurriculum from './course-curriculum';
import courseEnrollButton from './course-enroll-button';
import courseFeatureImage from './course-feature-image';
import courseHighlights from './course-highlight';
import courseOverview from './course-overview';
import coursePrice from './course-price';
import courseReviews from './course-reviews';
import courseStats from './course-stats';
import courseTitle from './course-title';
import courseUserProgress from './course-user-progress';
import courses from './courses';
import groupPriceButton from './group-price-button';
import singleCourse from './single-course';

// @ts-ignore
import courseAuthorPreview from '../../../img/blocks-preview/course-author.jpg';
// @ts-ignore
import courseCategoriesPreview from '../../../img/blocks-preview/course-categories.jpg';
// @ts-ignore
import courseCategoryPreview from '../../../img/blocks-preview/course-category.jpg';
// @ts-ignore
import courseComingSoonPreview from '../../../img/blocks-preview/course-coming-soon.jpg';
// @ts-ignore
import courseContentsPreview from '../../../img/blocks-preview/course-contents.jpg';
// @ts-ignore
import courseCurriculumPreview from '../../../img/blocks-preview/course-curriculum.jpg';
// @ts-ignore
import courseEnrollButtonPreview from '../../../img/blocks-preview/course-enroll-button.jpg';
// @ts-ignore
import courseFeatureImagePreview from '../../../img/blocks-preview/course-feature-image.jpg';
// @ts-ignore
import courseHighlightPreview from '../../../img/blocks-preview/course-highlight.jpg';
// @ts-ignore
import courseOverviewPreview from '../../../img/blocks-preview/course-overview.jpg';
// @ts-ignore
import coursePricePreview from '../../../img/blocks-preview/course-price.jpg';
// @ts-ignore
import courseReviewsPreview from '../../../img/blocks-preview/course-reviews.jpg';
// @ts-ignore
import courseStatsPreview from '../../../img/blocks-preview/course-stats.jpg';
// @ts-ignore
import courseTitlePreview from '../../../img/blocks-preview/course-title.jpg';
// @ts-ignore
import courseUserProgressPreview from '../../../img/blocks-preview/course-user-progress.jpg';
// @ts-ignore
import coursesPreview from '../../../img/blocks-preview/courses.jpg';
// @ts-ignore
import groupPriceButtonPreview from '../../../img/blocks-preview/group-price-button.jpg';
// @ts-ignore
import singleCoursePreview from '../../../img/blocks-preview/single-course.jpg';

const previewImages: Record<string, string> = {
	'course-author': courseAuthorPreview,
	'course-categories': courseCategoriesPreview,
	'course-category': courseCategoryPreview,
	'course-coming-soon': courseComingSoonPreview,
	'course-contents': courseContentsPreview,
	'course-curriculum': courseCurriculumPreview,
	'course-enroll-button': courseEnrollButtonPreview,
	'course-feature-image': courseFeatureImagePreview,
	'course-highlights': courseHighlightPreview,
	'course-overview': courseOverviewPreview,
	'course-price': coursePricePreview,
	'course-reviews': courseReviewsPreview,
	'course-stats': courseStatsPreview,
	'single-course-title': courseTitlePreview,
	'course-user-progress': courseUserProgressPreview,
	courses: coursesPreview,
	'group-price-button': groupPriceButtonPreview,
	'single-course': singleCoursePreview,
};

let blocks = [
	singleCourse,
	courseTitle,
	courseFeatureImage,
	courseAuthor,
	courseContent,
	coursePrice,
	courseEnrollButton,
	courseStats,
	courseHighlights,
	courses,
	courseCategories,
	courseCurriculum,
	courseReviews,
	courseOverview,
	courseComingSoon,
	courseCategory,
	groupPriceButton,
	courseUserProgress,
];

blocks = applyFilters('masteriyo.blocks', blocks);

export const registerBlocks = () => {
	for (const block of blocks) {
		const settings = applyFilters('masteriyo.block.metadata', block.settings);
		const slug = block.name.split('/')[1];

		if (blockIcons[slug]) {
			settings.icon = blockIcons[slug];
		}

		if (previewImages[slug]) {
			settings.example = {
				...settings.example,
				attributes: {
					...settings.example?.attributes,
					clientId: 'masteriyo-block-preview',
				},
			};

			const OriginalEdit = settings.edit;
			settings.edit = (props: any) => {
				if (props.attributes?.clientId === 'masteriyo-block-preview') {
					return createElement('img', {
						src: previewImages[slug],
						alt: `${slug} preview`,
						style: { width: '100%', height: 'auto' },
					});
				}
				return createElement(OriginalEdit, props);
			};
		}

		// Apply edit filters
		settings.edit = applyFilters(
			'masteriyo.block.edit',
			settings.edit,
			settings,
		);

		registerBlockType(block.name, settings);
	}
};

export default registerBlocks;
