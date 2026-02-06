import { InspectorControls } from '@wordpress/block-editor';
import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	SingleCourseDefaultLayout,
	SingleCourseLayout1Layout,
	SingleCourseMinimalLayout,
} from './../../../../back-end/constants/images';

const BlockSettings: React.FC<any> = ({ attributes, setAttributes }) => {
	const { template = 'default' } = attributes;

	const templates = [
		{
			value: 'default',
			label: __('Simple', 'learning-management-system'),
			image: SingleCourseDefaultLayout,
		},
		{
			value: 'modern',
			label: __('Modern', 'learning-management-system'),
			image: SingleCourseLayout1Layout,
		},
		{
			value: 'minimal',
			label: __('Minimal', 'learning-management-system'),
			image: SingleCourseMinimalLayout,
		},
	];

	return (
		<InspectorControls>
			<div style={{ padding: '16px' }}>
				<label
					className="components-base-control__label"
					style={{ marginBottom: '8px', display: 'block' }}
				>
					{__('Choose Layout', 'learning-management-system')}
				</label>

				<div
					style={{
						display: 'grid',
						gridTemplateColumns: '1fr',
						gap: '12px',
					}}
				>
					{templates.map((tmpl) => (
						<div
							key={tmpl.value}
							onClick={() => setAttributes({ template: tmpl.value })}
							className={`masteriyo-design-card__items ${
								template === tmpl.value
									? 'masteriyo-design-card__items--active'
									: ''
							}`}
							style={{
								cursor: 'pointer',
								border:
									template === tmpl.value
										? '2px solid #2271b1'
										: '2px solid #ddd',
								borderRadius: '4px',
								padding: '12px',
								background: template === tmpl.value ? '#f0f6fc' : '#fff',
								transition: 'all 0.2s',
							}}
						>
							<div className="preview-image" style={{ marginBottom: '8px' }}>
								<img
									src={tmpl.image}
									alt={tmpl.label}
									style={{ width: '100%', borderRadius: '2px' }}
								/>
							</div>
							<div
								className="status"
								style={{
									display: 'flex',
									justifyContent: 'space-between',
									alignItems: 'center',
								}}
							>
								<span className="title" style={{ fontWeight: '500' }}>
									{tmpl.label}
								</span>
								{template === tmpl.value && (
									<span
										className="active-label"
										style={{
											background: '#2271b1',
											color: '#fff',
											padding: '2px 8px',
											borderRadius: '3px',
											fontSize: '11px',
										}}
									>
										{__('Active', 'learning-management-system')}
									</span>
								)}
							</div>
						</div>
					))}
				</div>
			</div>
		</InspectorControls>
	);
};

export default BlockSettings;
