import { Box, FormControl, Grid, Input } from '@chakra-ui/react';
import { __, _x } from '@wordpress/i18n';
import React from 'react';
import { useForm } from 'react-hook-form';
import { useSearchParams } from 'react-router-dom';
import { useOnType } from 'use-ontype';
import {
	deepClean,
	deepMerge,
} from '../../../../assets/js/back-end/utils/utils';

const pricingOptions = [
	{
		label: _x('Free', 'Course price text', 'learning-management-system'),
		value: 'free',
	},
	{
		label: _x('Paid', 'Course price text', 'learning-management-system'),
		value: 'paid',
	},
];

interface FilterParams {
	category?: string | number;
	search?: string;
	status?: string;
	priceType?: string;
}

interface Props {
	setFilterParams: any;
	filterParams: FilterParams;
	status?: string;
}

const GoogleMeetFilter: React.FC<Props> = (props) => {
	const { setFilterParams, filterParams, status } = props;
	const { handleSubmit, control } = useForm();
	const [searchParams] = useSearchParams();
	const courseStatus = searchParams.get('status') || status || 'any';

	const onSearchInput = useOnType(
		{
			onTypeFinish: (val: string) => {
				setFilterParams({
					...filterParams,
					search: val,
					status: courseStatus,
				});
			},
		},
		800,
	);

	const onChange = (data: any) => {
		setFilterParams(
			deepClean(
				deepMerge(data, {
					search: filterParams.search,
					status: data.status?.value,
				}),
			),
		);
	};

	return (
		<Box px={{ base: 6, md: 12 }}>
			<form onChange={handleSubmit(onChange)}>
				<Grid gridTemplateColumns={{ md: '2, 1fr' }} gap="4">
					<FormControl>
						<Input
							placeholder={__('Search meetings', 'learning-management-system')}
							{...onSearchInput}
						/>
					</FormControl>
				</Grid>
			</form>
		</Box>
	);
};

export default GoogleMeetFilter;
