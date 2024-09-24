import { BlockAttributesDefinition } from '../types';

const attributes: BlockAttributesDefinition = {
	clientId: {
		type: String,
	},
	// startCourseButtonBorder: {
	// 	type: Object,
	// 	default: {
	// 		border: 1,
	// 		radius: {
	// 			desktop: {
	// 				top: 50,
	// 				right: 50,
	// 				bottom: 50,
	// 				left: 50,
	// 				unit: 'px',
	// 			},
	// 			tablet: {
	// 				top: 50,
	// 				right: 50,
	// 				bottom: 50,
	// 				left: 50,
	// 				unit: 'px',
	// 			},
	// 			mobile: {
	// 				top: 50,
	// 				right: 50,
	// 				bottom: 50,
	// 				left: 50,
	// 				unit: 'px',
	// 			},
	// 		},
	// 	},
	// 	style: [
	// 		{
	// 			selector:
	// 				'{{WRAPPER}} .masteriyo-btn-primary.masteriyo-btn-primary.masteriyo-btn-primary',
	// 		},
	// 	],
	// },
	alignment: {
		type: String,
		default: 'left',
	},
	blockCSS: {
		type: String,
	},
	// height_n_width: {
	// 	type: Object,
	// 	default: {
	// 		height: 400,
	// 		width: 450,
	// 	},
	// },
	courseId: {
		type: Number,
	},
};
export default attributes;
