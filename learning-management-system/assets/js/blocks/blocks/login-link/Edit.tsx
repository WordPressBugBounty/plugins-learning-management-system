import { InspectorControls, useBlockProps } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { splitLoginoutLabel } from './utils';

const Edit: React.FC<any> = ({ attributes, setAttributes }) => {
	const { label } = attributes;
	const blockProps = useBlockProps({
		className:
			'wp-block-navigation-item wp-block-navigation-link masteriyo-nav-loginout',
	});
	const [loginLabel, logoutLabel] = splitLoginoutLabel(
		label,
		__('Login', 'learning-management-system'),
		__('Logout', 'learning-management-system'),
	);

	return (
		<>
			<InspectorControls>
				<PanelBody title={__('Labels', 'learning-management-system')}>
					<TextControl
						label={__('Custom labels', 'learning-management-system')}
						help={__(
							'Separate the login and logout labels with a pipe (|). Example: Sign In | Sign Out',
							'learning-management-system',
						)}
						value={label}
						onChange={(value: string) => setAttributes({ label: value })}
					/>
				</PanelBody>
			</InspectorControls>
			<li {...blockProps}>
				<span className="wp-block-navigation-item__content">
					<span className="wp-block-navigation-item__label">
						{loginLabel} | {logoutLabel}
					</span>
				</span>
			</li>
		</>
	);
};

export default Edit;
