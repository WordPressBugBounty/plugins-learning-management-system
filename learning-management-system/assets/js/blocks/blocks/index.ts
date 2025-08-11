import { registerBlockType } from '@wordpress/blocks';
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
import courseSearchForm from './course-search-form';
import courseStats from './course-stats';
import courseTitle from './course-title';
import courseUserProgress from './course-user-progress';
import courses from './courses';
import groupPriceButton from './group-price-button';
import singleCourse from './single-course';

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
	courseSearchForm,
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
