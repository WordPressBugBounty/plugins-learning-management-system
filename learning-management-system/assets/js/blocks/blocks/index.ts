import { registerBlockType } from '@wordpress/blocks';
import { applyFilters } from '@wordpress/hooks';
import { blockIcons } from './../components/icon';

import courseTitle from './course-title';
import singleCourse from './single-course';
import courseFeatureImage from './course-feature-image';
import courseAuthor from './course-author';
import courseContent from './course-contents';
import coursePrice from './course-price';
import courseEnrollButton from './course-enroll-button';
import courseStats from './course-stats';
import courseHighlights from './course-highlight';
import courseSearchForm from './course-search-form';
import courses from './courses';
import courseCategories from './course-categories';
import courseCurriculum from './course-curriculum';
import courseReviews from './course-reviews';
import courseOverview from './course-overview';

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
