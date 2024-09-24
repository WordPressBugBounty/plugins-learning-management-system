import {
	Box,
	ButtonGroup,
	Container,
	HStack,
	Image,
	Skeleton,
	SkeletonCircle,
	SkeletonText,
	Stack,
} from '@chakra-ui/react';
import React from 'react';
import { Td, Tr } from 'react-super-responsive-table';
import { Logo } from '../../../../assets/js/back-end/constants/images';

export const GoogleMeetMeetingsListSkeleton: React.FC = () => {
	const lengths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
	return (
		<>
			{lengths.map((index) => (
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
		</>
	);
};

export const GoogleMeetSettingsSkeleton: React.FC = () => (
	<Stack direction="column" spacing="8" alignItems="center">
		<Box bg="white" w="full" shadow="header" pb={['3', 0, 0]}>
			<Container maxW="container.xl">
				<Stack
					direction={['column', 'row']}
					justifyContent="space-between"
					align="center"
				>
					<Stack
						direction={['column', null, 'row']}
						spacing={['3', null, '8']}
						align="center"
						minHeight="16"
					>
						<Box display={['none', null, 'block']}>
							<Image src={Logo} w="36px" />
						</Box>
						<SkeletonText noOfLines={1} width="80px" />
						<Stack
							direction="row"
							alignItems="center"
							gap="5"
							mt="0px !important"
						>
							<Stack direction="row" gap="3" alignItems="center">
								<SkeletonCircle size="4" />
								<SkeletonText noOfLines={1} width="40px" />
							</Stack>
							<Stack direction="row" gap="3" alignItems="center">
								<SkeletonCircle size="4" />
								<SkeletonText noOfLines={1} width="40px" />
							</Stack>
							<Stack direction="row" gap="3" alignItems="center">
								<SkeletonCircle size="4" />
								<SkeletonText noOfLines={1} width="40px" />
							</Stack>
						</Stack>
					</Stack>
					<ButtonGroup>
						<Skeleton height="40px" width="70px" />
					</ButtonGroup>
				</Stack>
			</Container>
		</Box>
		<Container maxW="container.xl">
			<Stack direction={['column', 'column', 'column', 'row']} spacing="8">
				<Box bg="white" p="10" shadow="box" width="full">
					<Stack direction="column" spacing="6">
						<HStack spacing="5">
							<Skeleton height="40px" flex={1} />
							<Skeleton height="40px" flex={1} />
						</HStack>
						<Skeleton height="40px" />
					</Stack>
				</Box>
			</Stack>
		</Container>
	</Stack>
);
