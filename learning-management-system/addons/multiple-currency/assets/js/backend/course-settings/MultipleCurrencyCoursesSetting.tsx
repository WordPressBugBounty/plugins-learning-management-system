import {
	Collapse,
	FormLabel,
	Icon,
	Stack,
	Switch,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext, useWatch } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import FormControlTwoCol from '../../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import { MultipleCurrencyData } from '../../../../../../assets/js/back-end/types/course';
import { isEmpty } from '../../../../../../assets/js/back-end/utils/utils';
import RenderPricingZone from './RenderPricingZone';

interface Props {
	multiple_currency_data?: MultipleCurrencyData;
}

const MultipleCurrencyCoursesSetting: React.FC<Props> = ({
	multiple_currency_data,
}) => {
	const { register, control } = useFormContext();

	const watchMultiCurrency = useWatch({
		name: 'multiple_currency.enabled',
		defaultValue: multiple_currency_data?.enabled,
		control,
	});
	return (
		<Stack direction="column" spacing={2}>
			<FormControlTwoCol>
				<FormLabel>
					{__('Enable Multiple Currency', 'learning-management-system')}
				</FormLabel>
				<Stack direction="column" flex={3}>
					<Switch
						{...register('multiple_currency.enabled')}
						defaultChecked={multiple_currency_data?.enabled}
					/>
				</Stack>
			</FormControlTwoCol>
			<Collapse in={watchMultiCurrency} animateOpacity>
				{!isEmpty(multiple_currency_data?.pricing_zones) ? (
					<>
						<Stack spacing="3">
							{multiple_currency_data?.pricing_zones?.map((zone, index) => (
								<RenderPricingZone
									key={index}
									zone={zone}
									zoneId={zone?.id.toString()}
								/>
							))}
						</Stack>
					</>
				) : (
					<Stack direction="row" spacing="1" align="center">
						<Icon as={BiInfoCircle} color="primary.400" />
						<Text as="span" fontWeight="medium" color="gray.600" fontSize="sm">
							{__(
								'No active pricing zone found.',
								'learning-management-system',
							)}
						</Text>
					</Stack>
				)}
			</Collapse>
		</Stack>
	);
};

export default MultipleCurrencyCoursesSetting;
