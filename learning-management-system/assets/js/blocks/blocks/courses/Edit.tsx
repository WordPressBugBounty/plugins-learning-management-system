import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import React from 'react';

import { useBlockCSS } from './../../hooks/useBlockCSS';
import useClientId from './../../hooks/useClientId';
import { useDeviceType } from './../../hooks/useDeviceType';

import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId },
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

	return (
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
						}}
					/>
				</Disabled>
			</div>
		</div>
	);
};

export default Edit;
