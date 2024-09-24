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
		default: 'left',
	},

	fontSize: {
		type: Object,
	},

	textColor: {
		type: String,
	},

	nameFormat: {
		type: String,
	},

	price: {
		type: String,
	},

	courseId: {
		type: Number,
	},
};

export default attributes;
