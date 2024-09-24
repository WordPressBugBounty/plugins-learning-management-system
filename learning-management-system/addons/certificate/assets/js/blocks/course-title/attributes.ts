import { BlockAttributesDefinition } from '../../../../../../assets/js/blocks/types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},

	blockCSS: {
		type: String,
	},

	alignment: {
		type: Object,
	},

	fontSize: {
		type: Object,
	},

	textColor: {
		type: String,
	},
	fontFamily: {
		type: String,
	},
};

export default attributes;
