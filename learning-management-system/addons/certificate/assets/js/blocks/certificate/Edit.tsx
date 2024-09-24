import { InnerBlocks, MediaPlaceholder } from '@wordpress/block-editor';
import { withNotices } from '@wordpress/components';
import { compose } from '@wordpress/compose';
import { dispatch } from '@wordpress/data';
import { __ } from '@wordpress/i18n';
import React, { useEffect } from 'react';
import BlockSettings from './BlockSettings';
import { useBlockCSS } from './block-css';

const Edit: React.FC<any> = compose([withNotices])((props) => {
	const { attributes, noticeUI, noticeOperations, setAttributes } = props;
	const { backgroundImageURL = '' } = attributes;

	useEffect(() => {
		dispatch('core/block-editor').setTemplateValidity(true);
	}, []);

	const { editorCSS } = useBlockCSS(props);

	const onUploadError = (message) => {
		noticeOperations.removeAllNotices();
		noticeOperations.createErrorNotice(message);
	};

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
		<>
			<BlockSettings {...props} />
			{!backgroundImageURL ? (
				<MediaPlaceholder
					onSelect={onSelect}
					notices={noticeUI}
					onError={onUploadError}
					accept="image/*"
					allowedTypes={['image']}
					value={{ src: backgroundImageURL }}
					labels={{
						title: __('Certificate Background Image'),
						instructions: __(
							'Upload an image file, pick one from your media library.',
						),
					}}
				/>
			) : (
				<div
					id="certificate"
					className="certificate"
					style={{
						position: 'relative',
						fontFamily: 'Arial, sans-serif',
						lineHeight: 1,
						color: 'black',
					}}
				>
					<style>{editorCSS}</style>
					<img
						src={backgroundImageURL}
						style={{
							position: 'absolute',
							width: '100%',
							height: '100%',
						}}
					/>
					<div className="certificate-content-wrapper">
						<InnerBlocks templateLock={false} />
					</div>
				</div>
			)}
		</>
	);
});

export default Edit;
