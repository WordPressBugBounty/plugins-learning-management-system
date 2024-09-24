import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},

	blockCSS: {
		type: String,
	},

	courseId: {
		type: Number,
	},

	minWidth: {
		type: Object,
		default: {
			value: 325,
			unit: 'px',
		},
	},
};
export default attributes;
