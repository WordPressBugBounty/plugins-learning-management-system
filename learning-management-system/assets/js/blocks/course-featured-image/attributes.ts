import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
		default: '',
	},
	blockCSS: {
		type: String,
	},
	courseId: {
		type: Number,
	},
};

export default attributes;
