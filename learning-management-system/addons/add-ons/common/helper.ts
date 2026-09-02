export const disabled = (isEnabled: boolean) => {
	if (isEnabled) {
		return {};
	}
	return {
		opacity: '0.5',
		cursor: 'not-allowed',
		textDecoration: 'none',
		color: 'muted',
	};
};
