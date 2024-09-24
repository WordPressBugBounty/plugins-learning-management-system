import { Image } from '@chakra-ui/react';
import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { CourseCategoriesBlockGridDesign } from '../../../back-end/constants/images';
import { Panel, Slider, Tab, TabPanel } from '../../components';
import Toggle from '../../components/toggle';

const BlockSettings = (props: any) => {
	const {
		attributes: { count, columns, hide_courses_count, include_sub_categories },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<TabPanel>
				<Tab tabTitle={__('Design', 'learning-management-system')}>
					<div className="masteriyo-design-card">
						<div className="masteriyo-design-card__items masteriyo-design-card__items--active">
							<div className="preview-image">
								<Image
									src={CourseCategoriesBlockGridDesign}
									alt="Grid Design"
								/>
							</div>
							<div className="status">
								<span className="title">
									{__('Grid', 'learning-management-system')}
								</span>
								<span className="active-label">
									{__('Active', 'learning-management-system')}
								</span>
							</div>
						</div>
					</div>
					<div className="coming-soon-notice">
						<span>{__('New Design', 'learning-management-system')}</span>
						<span>{__('Coming Soon', 'learning-management-system')}</span>
					</div>
				</Tab>
				<Tab tabTitle={__('Settings', 'learning-management-system')}>
					<Panel
						title={__('General', 'learning-management-system')}
						initialOpen
					>
						<Slider
							onChange={(val: number) =>
								setAttributes({ count: val ? val : 1 })
							}
							label={__('No. of Categories', 'learning-management-system')}
							min={1}
							step={1}
							value={count}
						/>
						<Toggle
							checked={hide_courses_count === 'yes'}
							onChange={(val: boolean) =>
								setAttributes({ hide_courses_count: val ? 'yes' : 'no' })
							}
							label={__('Hide courses count', 'learning-management-system')}
						/>
						<Toggle
							checked={include_sub_categories}
							onChange={(val: boolean) =>
								setAttributes({ include_sub_categories: val })
							}
							label={__('Include sub-categories', 'learning-management-system')}
						/>
					</Panel>
					<Panel title={__('Layout', 'learning-management-system')}>
						<Slider
							onChange={(val: number) =>
								setAttributes({ columns: val ? val : 1 })
							}
							label={__('Columns', 'learning-management-system')}
							min={1}
							max={4}
							step={1}
							value={columns}
						/>
					</Panel>
				</Tab>
			</TabPanel>
		</InspectorControls>
	);
};

export default BlockSettings;
