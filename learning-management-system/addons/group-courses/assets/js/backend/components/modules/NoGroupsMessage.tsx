import { Icon, Stack, Text } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { BiInfoCircle } from 'react-icons/bi';
import { Td, Tr } from 'react-super-responsive-table';

const NoGroupsMessage: React.FC = () => {
	return (
		<Tr>
			<Td>
				<Stack direction="row" spacing="1" align="center">
					<Icon as={BiInfoCircle} color="primary.400" />
					<Text as="span" fontWeight="medium" color="gray.600" fontSize="sm">
						{__('No groups found.', 'learning-management-system')}
					</Text>
				</Stack>
			</Td>
			<Td></Td>
			<Td></Td>
			<Td></Td>
			<Td></Td>
			<Td></Td>
			<Td></Td>
			<Td></Td>
		</Tr>
	);
};

export default NoGroupsMessage;
