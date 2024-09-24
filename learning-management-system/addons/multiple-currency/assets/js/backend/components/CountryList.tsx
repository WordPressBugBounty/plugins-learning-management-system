import {
	Badge,
	Menu,
	MenuButton,
	MenuItem,
	MenuList,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { PriceZoneSchema } from '../../types/multiCurrency';
interface Props {
	countries: PriceZoneSchema['countries'];
}

const CountryList: React.FC<Props> = (props) => {
	let { countries } = props;

	if (countries.length === 0) {
		return <Text>{__('N/A', 'learning-management-system')}</Text>;
	}

	const firstCountry = countries[0];
	const remainingCountries = countries.slice(1);

	if (remainingCountries.length > 0) {
		return (
			<Menu>
				<MenuButton>
					{firstCountry?.label}
					<Badge
						colorScheme="green"
						ml={2}
						title={
							remainingCountries?.length.toString() +
							__(' More Countries', 'learning-management-system')
						}
					>
						+{remainingCountries?.length}
					</Badge>
				</MenuButton>
				<MenuList pb="0px">
					<Stack direction="row" alignItems="center" spacing="1" ml="3" mb="1">
						<Text fontSize="sm" ml="3" fontWeight="bold">
							{__('Countries', 'learning-management-system')}
						</Text>
					</Stack>
					{remainingCountries.map((country) => (
						<MenuItem
							key={country?.value}
							display="flex"
							flexDirection="row"
							gap="2"
							_hover={{ background: 'none' }}
							_odd={{ background: 'gray.100' }}
						>
							<Text>
								{sprintf(
									/* translators: %s: Country */
									__('%s', 'learning-management-system'),
									country?.label,
								)}
							</Text>
						</MenuItem>
					))}
				</MenuList>
			</Menu>
		);
	}

	return (
		<Stack direction="row">
			<Text>
				{sprintf(
					/* translators: %s: Country */
					__('%s', 'learning-management-system'),
					firstCountry?.label,
				)}
			</Text>
		</Stack>
	);
};

export default CountryList;
