import {
	FormControl,
	FormErrorMessage,
	FormLabel,
	Skeleton,
} from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useMemo } from 'react';
import { Controller, useFormContext } from 'react-hook-form';
import Select from 'react-select';
import urls from '../../../../../../assets/js/back-end/constants/urls';
import { CurrenciesSchema } from '../../../../../../assets/js/back-end/schemas';
import ToolTip from '../../../../../../assets/js/back-end/screens/settings/components/ToolTip';
import API from '../../../../../../assets/js/back-end/utils/api';
import { PriceZoneSchema } from '../../types/multiCurrency';

interface Props {
	defaultValue?: PriceZoneSchema['currency'];
}

const Currency: React.FC<Props> = ({ defaultValue }) => {
	const currenciesAPI = new API(urls.currencies);
	const {
		control,
		setValue,
		formState: { errors },
		trigger,
	} = useFormContext();

	const currenciesQuery = useQuery({
		queryKey: ['currencies'],
		queryFn: () => currenciesAPI.list(),
	});

	const currencyOptions = useMemo(() => {
		return currenciesQuery.isSuccess
			? currenciesQuery?.data?.map((currency: CurrenciesSchema) => ({
					value: currency?.code,
					label: `${currency.name} (${currency.symbol})`,
				}))
			: [];
	}, [currenciesQuery.isSuccess, currenciesQuery?.data]);

	const noOptionsMessage = () =>
		__('No currency found.', 'learning-management-system');

	return (
		<FormControl isInvalid={!!errors?.currency}>
			<FormLabel>
				{__('Currency', 'learning-management-system')}
				<ToolTip
					label={__(
						'Choose the currency of this zone. Customers from the countries of this zone will see the prices and pay in this currency.',
						'learning-management-system',
					)}
				/>
			</FormLabel>
			{currenciesQuery.isLoading ? (
				<Skeleton height="40px" width="100%" />
			) : (
				<Controller
					name="currency"
					rules={{
						required: __(
							'Please select a currency.',
							'learning-management-system',
						),
					}}
					control={control}
					defaultValue={defaultValue?.value}
					render={({ field: { onChange, value } }) => (
						<Select
							onChange={(selectedOption: any) => {
								setValue('currency', selectedOption?.value);
								trigger('currency');
							}}
							options={currencyOptions}
							isClearable={true}
							isSearchable={true}
							placeholder={__(
								'Select a currency',
								'learning-management-system',
							)}
							noOptionsMessage={noOptionsMessage}
							defaultValue={defaultValue}
						/>
					)}
				/>
			)}
			<FormErrorMessage>{errors?.currency?.message + ''}</FormErrorMessage>
		</FormControl>
	);
};

export default Currency;
