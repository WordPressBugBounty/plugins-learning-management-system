import metadata from './block.json';
import Edit from './Edit';
import Save from './Save';

export default {
	name: metadata.name,
	settings: {
		...metadata,
		edit: Edit,
		save: Save,
	},
};
