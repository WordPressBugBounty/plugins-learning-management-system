import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Panel, Tab } from '../../components';
import AdvanceSelect from '../../components/advance-select';

const BlockSettings = (props: any) => {
	const {
		attributes: { alignment, clientId },
		setAttributes,
	} = props;

	return (
		<InspectorControls>
			<Tab tabTitle={__('Settings', 'masteriyo')}>
				<Panel title={__('General', 'masteriyo')} initialOpen>
					<AdvanceSelect
						value={alignment}
						onChange={(val) => setAttributes({ alignment: val })}
						responsive={false}
						label={__('Alignment', 'masteriyo')}
						options={[
							{
								label: __('Left', 'masteriyo'),
								value: 'left',
								icon: 'text-align-left',
							},
							{
								label: __('Center', 'masteriyo'),
								value: 'center',
								icon: 'text-align-center',
							},
							{
								label: __('Right', 'masteriyo'),
								value: 'right',
								icon: 'text-align-right',
							},
							{
								label: __('Justify', 'masteriyo'),
								value: 'justify',
								icon: 'text-align-justify',
							},
						]}
					/>
				</Panel>

				{/* <Panel title={__('Button', 'masteriyo')}>
					<BorderSetting
						value={startCourseButtonBorder}
						onChange={(val) => setAttributes({ startCourseButtonBorder: val })}
					/>
				</Panel> */}
			</Tab>
		</InspectorControls>
	);
};

export default BlockSettings;
