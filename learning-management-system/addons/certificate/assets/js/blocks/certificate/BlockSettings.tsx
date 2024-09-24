import {
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import { Button, Placeholder } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import classNames from 'classnames';
import React from 'react';
import {
	Panel,
	Select,
	Slider,
} from '../../../../../../assets/js/blocks/components';
import './editor.scss';

const BlockSettings: React.FC<any> = (props) => {
	const { attributes, setAttributes } = props;
	const {
		backgroundImageURL = '',
		containerWidth,
		pageSize = 'Letter',
		pageOrientation = 'L',
		paddingTop,
	} = attributes;

	const onSelect = (media) => {
		if (!media?.url) {
			setAttributes({
				backgroundImageURL: undefined,
				backgroundImageID: 0,
			});
		}

		setAttributes({
			backgroundImageURL: media.url,
			backgroundImageID: media ? media.id : 0,
		});
	};

	return (
		<InspectorControls>
			<Panel title={__('General', 'learning-management-system')} initialOpen>
				<div
					className={classNames(
						'masteriyo-control',
						'masteriyo-certificate-background-control',
					)}
				>
					<div className="masteriyo-control-head">
						<label className="masteriyo-control-label">
							{__('Background Image', 'learning-management-system')}
						</label>
					</div>
					<div className="masteriyo-control-body">
						<MediaUploadCheck>
							<MediaUpload
								onSelect={onSelect}
								accept="image/*"
								allowedTypes={['image']}
								title={__('Upload Image', 'learning-management-system')}
								render={({ open }) => (
									<>
										<Placeholder>
											{backgroundImageURL ? (
												<img src={backgroundImageURL} width="100%" />
											) : (
												<Button
													onClick={open}
													icon="plus-alt"
													style={{
														height: 100,
														width: '100%',
														justifyContent: 'center',
														backgroundColor: 'rgba(0,0,0,.04)',
														color: 'var(--wp-admin-theme-color)',
													}}
												>
													{__('Select Image', 'learning-management-system')}
												</Button>
											)}
										</Placeholder>
										{backgroundImageURL && (
											<Button
												onClick={open}
												icon="format-image"
												isPrimary
												style={{ marginTop: 10 }}
											>
												{__('Change', 'learning-management-system')}
											</Button>
										)}
									</>
								)}
							/>
						</MediaUploadCheck>
					</div>
				</div>
			</Panel>
			<Panel title={__('Layout', 'learning-management-system')}>
				<Slider
					value={paddingTop}
					onChange={(val) => setAttributes({ paddingTop: val })}
					responsive={false}
					min={0}
					max={500}
					inline={true}
					units={['px']}
					defaultUnit="px"
					label={__('Padding Top', 'learning-management-system')}
				/>
				<Slider
					value={containerWidth || ''}
					onChange={(val) => setAttributes({ containerWidth: val })}
					responsive={false}
					min={50}
					max={100}
					step={1}
					inline={true}
					units={['%']}
					defaultUnit="%"
					label={__('Container width', 'learning-management-system')}
				/>
				<Select
					value={pageSize}
					label={__('Page Size', 'learning-management-system')}
					options={[
						{
							value: 'Letter',
							label: __('Letter / US Letter', 'learning-management-system'),
						},
						{ value: 'A4', label: __('A4', 'learning-management-system') },
					]}
					onChange={(val) => setAttributes({ pageSize: val })}
					inline={false}
				/>
				<Select
					value={pageOrientation}
					label={__('Page Orientation', 'learning-management-system')}
					options={[
						{ value: 'P', label: __('Portrait', 'learning-management-system') },
						{
							value: 'L',
							label: __('Landscape', 'learning-management-system'),
						},
					]}
					onChange={(val) => setAttributes({ pageOrientation: val })}
					inline={false}
				/>
			</Panel>
		</InspectorControls>
	);
};

export default BlockSettings;
