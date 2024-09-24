import {
	Box,
	ButtonGroup,
	Flex,
	SkeletonCircle,
	SkeletonText,
} from '@chakra-ui/react';
import React from 'react';
import { Col, Row } from 'react-grid-system';

const GroupsSkeleton: React.FC = () => {
	const skeletonRows = 5;

	return (
		<>
			<Row>
				{[...Array(skeletonRows)].map((_, index) => (
					<Col xs={12} md={6} key={Date.now().toString()}>
						<Box
							borderRadius={'lg'}
							key={index}
							bgColor="white"
							my={3}
							border="1px"
							borderColor="gray.200"
							boxShadow={'lg'}
						>
							<Flex
								justifyContent={'space-between'}
								alignItems={'center'}
								p={3}
							>
								<SkeletonText width="90px" noOfLines={1} />
								<ButtonGroup isAttached size="sm">
									<SkeletonCircle size="8" />
									<SkeletonCircle size="8" />
									<SkeletonCircle size="8" />
								</ButtonGroup>
							</Flex>
						</Box>
					</Col>
				))}
			</Row>
		</>
	);
};

export default GroupsSkeleton;
