import { addFilter } from '@wordpress/hooks';
import { MediaUpload } from '@wordpress/media-utils';
import localized from '../../../../../assets/js/back-end/utils/global';

export function addMediaUpload() {
	addFilter(
		'editor.MediaUpload',
		'masteriyo/certificate-media-upload',
		() => MediaUpload,
	);
}

export function camelToKebab(text: string) {
	return text.replace(/([a-z0-9])([A-Z])/g, '$1-$2').toLowerCase();
}

export function addSupportedBlocks() {
	addFilter(
		'blockEditor.__unstableCanInsertBlockType',
		'masteriyo/removeBlocks',
		function (canInsert, blockType) {
			if (localized?.allowedBlockTypes?.includes(blockType.name)) {
				canInsert = true;
			} else {
				canInsert = false;
			}

			return canInsert;
		},
		100,
	);
}
