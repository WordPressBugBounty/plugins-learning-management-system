import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},
	authors: {
		type: Array,
		default: [],
	},
	blockCSS: {
		type: String,
	},
	hideAuthorsAvatar: {
		type: String,
		default: 'no',
	},
	hideAuthorsName: {
		type: String,
		default: 'no',
	},
	height_n_width: {
		type: Object,
		default: {
			height: 400,
			width: 450,
		},
	},
	margin: {
		type: String,
		default: 'left',
	},

	courseId: {
		type: Number,
	},
};
export default attributes;
