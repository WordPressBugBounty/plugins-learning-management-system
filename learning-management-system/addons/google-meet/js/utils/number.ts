export const numberRange = (
	from: number,
	to: number,
	interval: number = 1,
): number[] => {
	const list: number[] = [];

	if (from > to) {
		for (let i = from; i >= to; i = i - interval) {
			list.push(i);
		}
	} else {
		for (let i = from; i <= to; i = i + interval) {
			list.push(i);
		}
	}

	return list;
};

export const formatDate = (dateString: string): string => {
	const date = new Date(dateString);
	if (isNaN(date.getTime())) {
		return ''; // Return empty string if dateString is not a valid date
	}
	const year = date.getFullYear();
	const month = ('0' + (date.getMonth() + 1)).slice(-2);
	const day = ('0' + date.getDate()).slice(-2);
	const hour = ('0' + date.getHours()).slice(-2);
	const minute = ('0' + date.getMinutes()).slice(-2);
	const period = date.getHours() < 12 ? 'AM' : 'PM';

	return `${year}-${month}-${day}, ${hour}:${minute} ${period}`;
};
