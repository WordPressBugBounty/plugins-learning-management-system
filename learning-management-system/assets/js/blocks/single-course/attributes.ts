import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},

	courseId: {
		type: Number,
	},

	margin: {
		type: String,
		default: 'auto',
	},

	padding: {
		type: Object,
		default: {
			border: 1,
			padding: {
				desktop: {
					top: 0,
					right: 0,
					bottom: 0,
					left: 0,
					unit: 'px',
				},
				tablet: {
					top: 0,
					right: 0,
					bottom: 0,
					left: 0,
					unit: 'px',
				},
				mobile: {
					top: 0,
					right: 0,
					bottom: 0,
					left: 0,
					unit: 'px',
				},
			},
		},
	},

	// gap: {
	// 	type: String,
	// 	default: '100px',
	// },

	blockCSS: {
		type: String,
	},
};

export default attributes;
