import { SkeletonText } from '@chakra-ui/react';
import React from 'react';
import { Td, Tr } from 'react-super-responsive-table';

export const SkeletonAccountGoogleMeetSessions: React.FC = () => {
	const lengths = [1, 2, 3, 4, 5, 6, 7, 8, 9, 10];
	return (
		<>
			{lengths.map((index) => (
				<Tr key={index}>
					<Td>
						<SkeletonText w="120px" noOfLines={2} />
					</Td>
					<Td>
						<SkeletonText w="40px" noOfLines={1} />
					</Td>
					<Td>
						<SkeletonText w="120px" noOfLines={1} />
					</Td>
					<Td>
						<SkeletonText w="40px" noOfLines={1} />
					</Td>
					<Td>
						<SkeletonText w="40px" noOfLines={2} />
					</Td>
					<Td>
						<SkeletonText w="60px" noOfLines={2} />
					</Td>
				</Tr>
			))}
		</>
	);
};
