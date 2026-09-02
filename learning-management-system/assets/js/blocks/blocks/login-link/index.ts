import metadata from './block.json';
import Edit from './Edit';

export default {
	name: metadata.name,
	settings: {
		...metadata,
		edit: Edit,
		save: () => null,
	},
};
