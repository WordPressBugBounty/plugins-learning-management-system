import {
	Box,
	Container,
	HStack,
	Skeleton,
	SkeletonText,
	Stack,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Table, Tbody, Td, Th, Thead, Tr } from 'react-super-responsive-table';

export const GoogleClassroomSettingsSkeleton: React.FC = () => (
	<Stack direction="column" spacing="8" alignItems="center">
		<Container bg="white" shadow="box" maxW="container.xl">
			<Stack direction="column" spacing="6">
				<HStack spacing="8" align="start" height="full" alignItems="stretch">
					<Box p="6" minHeight="170px" flex="1" height="full">
						<VStack align="stretch" spacing="4">
							<Box pb="5">
								<Skeleton pb="5" height="30px" width="60%" mb="4" />
								<SkeletonText noOfLines={3} spacing="4" skeletonHeight="20px" />
							</Box>
							<HStack spacing="2">
								<Skeleton height="40px" width="full" />
								<Skeleton height="40px" width="100px" />
							</HStack>
						</VStack>
					</Box>
					<Box
						minHeight="170px"
						p="6"
						shadow="md"
						borderWidth="1px"
						flex="1"
						bg="white"
					>
						<VStack align="center" spacing="4" height={'full'} justify="center">
							<Skeleton height="20px" width="70%" />
							<Skeleton height="10px" width="100%" />
							<Skeleton height="60px" width="50%" />
						</VStack>
					</Box>
				</HStack>
			</Stack>
		</Container>
	</Stack>
);

export const GoogleClassroomListSkeleton: React.FC = () => (
	<>
		<Table>
			<Thead>
				<Tr>
					<Th>{__('Class Name', 'learning-management-system')}</Th>
					<Th>{__('Class Code', 'learning-management-system')}</Th>
					<Th>{__('Status', 'learning-management-system')}</Th>
					<Th>{__('Action', 'learning-management-system')}</Th>
				</Tr>
			</Thead>
			<Tbody>
				{[1, 2, 3, 4, 5, 6, 7, 8, 9, 10].map((index) => (
					<Tr key={index}>
						<Td>
							<SkeletonText noOfLines={1} />
						</Td>
						<Td>
							<SkeletonText noOfLines={1} />
						</Td>
						<Td>
							<SkeletonText noOfLines={1} />
						</Td>
						<Td>
							<SkeletonText noOfLines={1} />
						</Td>
					</Tr>
				))}
			</Tbody>
		</Table>
	</>
);
