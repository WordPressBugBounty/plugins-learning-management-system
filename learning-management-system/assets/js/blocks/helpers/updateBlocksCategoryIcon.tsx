import { updateCategory } from '@wordpress/blocks';
import { masteriyoLogo } from '../components/icon/logo';

export function updateBlocksCategoryIcon() {
	updateCategory('masteriyo', {
		icon: masteriyoLogo,
	});
}
