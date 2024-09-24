export const prefixZero = (time: number | string): string => {
	time = time + '';

	if (time.length <= 1) {
		return '0' + time;
	}
	return time;
};
