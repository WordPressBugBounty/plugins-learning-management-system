import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},

	blockCSS: {
		type: String,
	},

	alignment: {
		type: String,
	},

	fontSize: {
		type: Object,
		default: { value: '24', unit: 'px' },
	},

	textColor: {
		type: String,
	},

	nameFormat: {
		type: String,
	},

	title: {
		type: String,
	},
	courseId: {
		type: Number,
	},
};

export default attributes;
