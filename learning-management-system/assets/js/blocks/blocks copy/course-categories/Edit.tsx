import { useBlockProps } from '@wordpress/block-editor';
import { Disabled } from '@wordpress/components';
import { Fragment } from '@wordpress/element';
import React from 'react';
import useClientId from './../../hooks/useClientId';
import BlockSettings from './components/BlockSettings';

const Edit: React.FC<any> = (props) => {
	const {
		attributes: { clientId },
		setAttributes,
	} = props;

	const ServerSideRender = wp.serverSideRender
		? wp.serverSideRender
		: wp.components.ServerSideRender;

	useClientId(props.clientId, setAttributes, props.attributes);

	const useProps = useBlockProps({
		className: 'masteriyo-block-editor-wrapper',
	});

	return (
		<Fragment>
			<div className="masteriyo">
				<div {...useProps}>
					<BlockSettings {...props} /> 
					<Disabled>
						<ServerSideRender
							block="masteriyo/course-categories"
							attributes={{
								clientId: clientId ? clientId : '',
								count: props.attributes.count,
								columns: props.attributes.columns,
								categoryIds: props.attributes.categoryIds,
								hide_courses_count: props.attributes.hide_courses_count,
								include_sub_categories: props.attributes.include_sub_categories,
							}}
						/>
					</Disabled>
				</div>
			</div>
		</Fragment>
	);
};

export default Edit;
