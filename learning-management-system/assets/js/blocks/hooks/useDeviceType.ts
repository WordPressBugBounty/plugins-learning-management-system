import { dispatch, useSelect } from '@wordpress/data';
import { useCallback } from '@wordpress/element';

export const useDeviceType = (): [string, CallableFunction] => {
	const deviceType: string = useSelect((select) => {
		const store = select('masteriyo/device-type');
		return store?.getPreviewDeviceType?.() || 'desktop';
	}, []);

	const setDeviceType = useCallback((state) => {
		const storeDispatcher = dispatch('masteriyo/device-type');

		if (storeDispatcher?.setPreviewDeviceType) {
			storeDispatcher.setPreviewDeviceType(state);
		} else {
			console.warn('masteriyo/device-type store not available yet.');
		}
	}, []);

	return [deviceType, setDeviceType];
};
