import { Box, Skeleton, SkeletonText, Stack } from '@chakra-ui/react';
import React from 'react';

const CertificateSkeleton = () => (
	<Stack direction="row" width="full">
		<Box
			bg="white"
			p={['4', null, '10']}
			shadow="box"
			display="flex"
			flex="2"
			flexDirection="column"
			justifyContent="space-between"
		>
			<Stack direction="column" spacing="6">
				<Stack direction="column">
					<SkeletonText noOfLines={1} width="70px" />
					<Skeleton height="40px" />
				</Stack>
				<Stack direction="column">
					<SkeletonText noOfLines={1} width="70px" />
					<Skeleton height="700px" />
				</Stack>
			</Stack>
		</Box>
	</Stack>
);

export default CertificateSkeleton;
