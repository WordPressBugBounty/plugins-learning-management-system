import { BlockControls, useBlockProps } from '@wordpress/block-editor';
import {
	Disabled,
	Placeholder,
	Spinner,
	ToolbarButton,
	ToolbarGroup,
} from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { grid, list } from '@wordpress/icons';
import React from 'react';

import { useBlockCSS } from './../../hooks/useBlockCSS';
import useClientId from './../../hooks/useClientId';
import { useDeviceType } from './../../hooks/useDeviceType';

import BlockSettings from './components/BlockSettings';
import TemplatePicker from './components/TemplatePicker';

// Declare global wp object
declare const wp: any;

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId, viewType = 'grid', template = '' },
		setAttributes,
		attributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	const [deviceType] = useDeviceType();

	useClientId(props.clientId, setAttributes, attributes);
	useBlockCSS({
		blockName: 'courses',
		clientId,
		attributes,
		deviceType,
	});

	const blockProps = useBlockProps({
		className: 'masteriyo-block-editor-wrapper',
	});

	const handleTemplateSelect = (selectedTemplate: string) => {
		setAttributes({ template: selectedTemplate });
	};

	// Show template picker if no template is selected
	if (!template) {
		return (
			<div className="masteriyo" style={{ maxWidth: '1140px' }}>
				<div {...blockProps}>
					<TemplatePicker onSelectTemplate={handleTemplateSelect} />
				</div>
			</div>
		);
	}

	// Show courses block with selected template
	return (
		<>
			{template === 'simple' && (
				<BlockControls>
					<ToolbarGroup>
						<ToolbarButton
							icon={grid}
							label={__('Grid View', 'learning-management-system')}
							isPressed={viewType === 'grid'}
							onClick={() => setAttributes({ viewType: 'grid' })}
						/>
						<ToolbarButton
							icon={list}
							label={__('List View', 'learning-management-system')}
							isPressed={viewType === 'list'}
							onClick={() => setAttributes({ viewType: 'list' })}
						/>
					</ToolbarGroup>
				</BlockControls>
			)}

			<div className="masteriyo" style={{ maxWidth: '1140px' }}>
				<div {...blockProps}>
					<BlockSettings {...props} />

					<Disabled>
						<ServerSideRender
							block="masteriyo/courses"
							attributes={{
								clientId: clientId || '',
								count: attributes.count,
								columns: attributes.columns,
								categoryIds: attributes.categoryIds,
								viewType: attributes.viewType,
								enableCourseFilters: attributes.enableCourseFilters,
								enableCategoryFilter: attributes.enableCategoryFilter,
								enableDifficultyLevelFilter:
									attributes.enableDifficultyLevelFilter,
								enablePriceTypeFilter: attributes.enablePriceTypeFilter,
								enablePriceFilter: attributes.enablePriceFilter,
								enableRatingFilter: attributes.enableRatingFilter,
								enableSorting: attributes.enableSorting,
								enableSortByDate: attributes.enableSortByDate,
								enableSortByPrice: attributes.enableSortByPrice,
								enableSortByRating: attributes.enableSortByRating,
								enableSortByTitle: attributes.enableSortByTitle,
								sortBy: attributes.sortBy || 'date',
								sortOrder: attributes.sortOrder || 'desc',
								template: template,
								showSearch: attributes.showSearch,
								showThumbnail: attributes.showThumbnail,
								showDifficultyBadge: attributes.showDifficultyBadge,
								showCategories: attributes.showCategories,
								showCourseTitle: attributes.showCourseTitle,
								showAuthor: attributes.showAuthor,
								showAuthorAvatar: attributes.showAuthorAvatar,
								showAuthorName: attributes.showAuthorName,
								showRating: attributes.showRating,
								showCourseDescription: attributes.showCourseDescription,
								showMetadata: attributes.showMetadata,
								showCourseDuration: attributes.showCourseDuration,
								showStudentsCount: attributes.showStudentsCount,
								showLessonsCount: attributes.showLessonsCount,
								showCardFooter: attributes.showCardFooter,
								showPrice: attributes.showPrice,
								showEnrollButton: attributes.showEnrollButton,
							}}
							EmptyResponsePlaceholder={() => (
								<Placeholder
									icon={grid}
									label={__('No Courses Found', 'learning-management-system')}
									instructions={__(
										'No courses match the current filters. Try adjusting your settings.',
										'learning-management-system',
									)}
								/>
							)}
							LoadingResponsePlaceholder={() => (
								<Placeholder
									icon={grid}
									label={__('Loading Courses...', 'learning-management-system')}
								>
									<Spinner />
								</Placeholder>
							)}
							ErrorResponsePlaceholder={({ response }: any) => (
								<Placeholder
									icon={grid}
									label={__(
										'Error Loading Courses',
										'learning-management-system',
									)}
									instructions={
										response?.message ||
										__('An error occurred', 'learning-management-system')
									}
								/>
							)}
						/>
					</Disabled>
				</div>
			</div>
		</>
	);
};

export default Edit;
