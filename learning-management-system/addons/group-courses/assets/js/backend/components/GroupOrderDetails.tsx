import {
	Accordion,
	AccordionButton,
	AccordionIcon,
	AccordionItem,
	AccordionPanel,
	Badge,
	Box,
	Divider,
	Flex,
	Heading,
	Icon,
	Text,
	useColorModeValue,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { MdGroup } from 'react-icons/md';

interface Props {
	groups: {
		id: number;
		title: string;
		emails: string[];
		edit_url: string;
	}[];
}

const GroupOrderDetails: React.FC<Props> = ({ groups }) => {
	const bgColor = useColorModeValue('gray.50', 'gray.700');
	const borderColor = useColorModeValue('gray.200', 'gray.600');

	return (
		<>
			<Heading as="h2" fontSize="medium">
				{__('Groups Details', 'learning-management-system')}
			</Heading>
			<Accordion allowToggle>
				{groups.map((group) => (
					<AccordionItem
						key={group.id}
						border="1px"
						borderColor={borderColor}
						borderRadius="md"
						bg={bgColor}
						mb={4}
					>
						<AccordionButton _expanded={{ bg: 'blue.100', color: 'blue.800' }}>
							<Box flex="1" textAlign="left">
								<Flex align="center">
									<Icon as={MdGroup} mr={2} />
									<Text fontWeight="bold">{group.title}</Text>
									<Badge ml={3} colorScheme="green">
										{group.emails.length}{' '}
										{__('Enrolled Members', 'learning-management-system')}
									</Badge>
								</Flex>
							</Box>
							<AccordionIcon />
						</AccordionButton>
						<AccordionPanel pb={4}>
							<Flex direction="column" gap={2}>
								<Text>
									<strong>
										{__('Enrolled Members', 'learning-management-system')}:
									</strong>{' '}
									{group.emails.join(', ')}
								</Text>
							</Flex>
						</AccordionPanel>
					</AccordionItem>
				))}
			</Accordion>

			<Box>
				<Divider />
			</Box>
		</>
	);
};

export default GroupOrderDetails;
