import {
	Collapse,
	Divider,
	FormLabel,
	Heading,
	Stack,
	Switch,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useFormContext, useWatch } from 'react-hook-form';
import EmptyInfo from '../../../../../../assets/js/back-end/components/common/EmptyInfo';
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
						<Heading as="h3" size="xs" ml="2">
							{__('Pricing Zone', 'learning-management-system')}
						</Heading>
						<Stack spacing="3" borderLeft="1px solid #efefef">
							<Divider />
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
					<EmptyInfo
						message={__(
							'No active pricing zone found.',
							'learning-management-system',
						)}
					/>
				)}
			</Collapse>
		</Stack>
	);
};

export default MultipleCurrencyCoursesSetting;
