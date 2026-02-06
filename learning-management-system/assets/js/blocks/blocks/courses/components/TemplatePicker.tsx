import { __ } from '@wordpress/i18n';
import React from 'react';
import {
	CourseDefaultLayout,
	CourseLayout1tLayout,
	CourseLayout2tLayout,
} from './../../../../back-end/constants/images';

interface TemplatePickerProps {
	onSelectTemplate: (template: string) => void;
}

const TemplatePicker: React.FC<TemplatePickerProps> = ({
	onSelectTemplate,
}) => {
	const templates = [
		{
			value: 'simple',
			label: __('Simple', 'learning-management-system'),
			image: CourseDefaultLayout,
		},
		{
			value: 'modern',
			label: __('Modern', 'learning-management-system'),
			image: CourseLayout1tLayout,
		},
		{
			value: 'overlay',
			label: __('Overlay', 'learning-management-system'),
			image: CourseLayout2tLayout,
		},
	];

	return (
		<div
			className="masteriyo-template-picker"
			style={{
				padding: '40px 20px',
				background: '#f9f9f9',
				border: '1px solid #ddd',
				borderRadius: '4px',
			}}
		>
			<div style={{ textAlign: 'center', marginBottom: '30px' }}>
				<h3 style={{ margin: '0 0 10px', fontSize: '18px', fontWeight: '600' }}>
					{__('Choose Layout', 'learning-management-system')}
				</h3>
				<p style={{ margin: '0', color: '#666', fontSize: '14px' }}>
					{__(
						'Select a template to display your courses',
						'learning-management-system',
					)}
				</p>
			</div>

			<div
				style={{
					display: 'grid',
					gridTemplateColumns: 'repeat(auto-fit, minmax(200px, 1fr))',
					gap: '20px',
					maxWidth: '800px',
					margin: '0 auto',
				}}
			>
				{templates.map((template) => (
					<div
						key={template.value}
						onClick={() => onSelectTemplate(template.value)}
						style={{
							cursor: 'pointer',
							background: '#fff',
							border: '2px solid #ddd',
							borderRadius: '8px',
							padding: '15px',
							transition: 'all 0.2s',
							textAlign: 'center',
						}}
						onMouseEnter={(e) => {
							e.currentTarget.style.borderColor = '#2271b1';
							e.currentTarget.style.boxShadow = '0 2px 8px rgba(0,0,0,0.1)';
						}}
						onMouseLeave={(e) => {
							e.currentTarget.style.borderColor = '#ddd';
							e.currentTarget.style.boxShadow = 'none';
						}}
					>
						<div
							style={{
								marginBottom: '12px',
								borderRadius: '4px',
								overflow: 'hidden',
								border: '1px solid #e0e0e0',
							}}
						>
							<img
								src={template.image}
								alt={template.label}
								style={{ width: '100%', height: 'auto', display: 'block' }}
							/>
						</div>
						<h4
							style={{
								margin: '0 0 8px',
								fontSize: '16px',
								fontWeight: '600',
								color: '#1e1e1e',
							}}
						>
							{template.label}
						</h4>
						<p
							style={{
								margin: '0',
								fontSize: '13px',
								color: '#666',
								lineHeight: '1.4',
							}}
						>
							{template.description}
						</p>
					</div>
				))}
			</div>

			<div style={{ marginTop: '20px', textAlign: 'center' }}>
				<p style={{ fontSize: '12px', color: '#999', margin: '0' }}>
					{__(
						'You can change the template later in block settings',
						'learning-management-system',
					)}
				</p>
			</div>
		</div>
	);
};

export default TemplatePicker;
