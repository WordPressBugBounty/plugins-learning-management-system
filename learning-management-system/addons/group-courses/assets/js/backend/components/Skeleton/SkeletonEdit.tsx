import { Box, Skeleton, Stack } from '@chakra-ui/react';
import React from 'react';

const SkeletonEdit = () => {
	return (
		<Stack direction={['column', 'column', 'column', 'row']} spacing="6">
			<Box bg="white" p="10" shadow="box" flex="1">
				<Stack direction="column" spacing="8">
					<Skeleton height="30px" width="100px" />
					<Stack mt="12px" direction="column" spacing="6">
						<Stack direction="column" spacing="3">
							<Skeleton height="10px" width="30%" />
							<Skeleton height="40px" />
							<Skeleton height="10px" width="35%" />
							<Skeleton height="400px" />
						</Stack>
						<Stack direction="row">
							<Skeleton height="40px" width="24" />
							<Skeleton height="40px" width="28" />
							<Skeleton height="40px" width="24" />
						</Stack>
					</Stack>
				</Stack>
			</Box>
			<Box bg="white" p="10" shadow="box" flex="0.5">
				<Stack direction="column" spacing="10">
					<Stack>
						<Skeleton height="10px" width="30%" />
						<Skeleton height="40px" />
					</Stack>
					<Stack>
						<Skeleton height="10px" width="30%" />
						<Skeleton height="40px" />
					</Stack>
				</Stack>
			</Box>
		</Stack>
	);
};

export default SkeletonEdit;
