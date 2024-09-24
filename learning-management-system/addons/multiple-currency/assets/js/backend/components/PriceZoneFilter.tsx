import { Box, Grid, Input } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useForm } from 'react-hook-form';
import { useSearchParams } from 'react-router-dom';
import { useOnType } from 'use-ontype';
import {
	deepClean,
	deepMerge,
} from '../../../../../../assets/js/back-end/utils/utils';

interface FilterParams {
	user?: string | number;
	search?: string;
}

interface Props {
	setFilterParams: any;
	filterParams: FilterParams;
}

const PriceZoneFilter: React.FC<Props> = ({
	filterParams,
	setFilterParams,
}) => {
	const [searchParams] = useSearchParams();
	const pricingZoneStatus = searchParams.get('status') || 'any';

	const { handleSubmit } = useForm();

	const onSearchInput = useOnType(
		{
			onTypeFinish: (val: string) => {
				setFilterParams({
					parent: 0,
					search: val,
					status: pricingZoneStatus,
				});
			},
		},
		800,
	);

	const onChange = (data: FilterParams) => {
		setFilterParams(
			deepClean(
				deepMerge(data, {
					search: filterParams.search,
					parent: 0,
					status: pricingZoneStatus,
				}),
			),
		);
	};

	return (
		<Box px={{ base: 6, md: 12 }}>
			<form onChange={handleSubmit(onChange)}>
				<Grid gridTemplateColumns={{ md: 'repeat(1, 1fr)' }} gap="4">
					<Input
						placeholder={__(
							'Search pricing zones',
							'learning-management-system',
						)}
						{...onSearchInput}
						height="40px"
					/>
				</Grid>
			</form>
		</Box>
	);
};

export default PriceZoneFilter;
