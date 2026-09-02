import { Box, Flex, Skeleton, Stack } from '@chakra-ui/react';
import React from 'react';

export const UserCertificatesSkeleton: React.FC = () => {
	return (
		<Stack spacing={6} style={{ marginTop: '0px !important' }}>
			{[1, 2, 3, 4, 5].map((x) => (
				<Box
					key={x}
					p="6"
					border="1px"
					borderColor="icy-blue-gray"
					w={'100%'}
					rounded={'10px'}
				>
					<Stack
						direction={{ base: 'column', sm: 'column', md: 'row', lg: 'row' }}
						spacing="4"
						flex={1}
						align={{ base: 'flex-start', md: 'center' }}
					>
						<Skeleton
							width={'50px'}
							height={'50px'}
							rounded={'10px'}
							flexShrink={0}
						/>

						<Stack direction="column" gap={'10px'} w="100%" flex={1}>
							<Skeleton height="20px" width="70%" />
							<Skeleton height="16px" width="40%" />
						</Stack>

						<Flex
							gap={3}
							direction={{ base: 'row', sm: 'row' }}
							flexWrap="wrap"
							justify={{ base: 'flex-start', md: 'flex-end' }}
						>
							<Skeleton height="40px" width="100px" rounded="md" />
							<Skeleton height="40px" width="180px" rounded="md" />
						</Flex>
					</Stack>
				</Box>
			))}
		</Stack>
	);
};
