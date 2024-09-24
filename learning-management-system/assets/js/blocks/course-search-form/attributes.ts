import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},

	height_n_width: {
		type: Object,
		default: {
			height: 400,
			width: 450,
		},
	},
};

export default attributes;
