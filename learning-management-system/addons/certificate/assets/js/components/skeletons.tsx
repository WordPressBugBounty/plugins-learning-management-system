import { SkeletonText } from '@chakra-ui/react';
import React from 'react';
import { Td, Tr } from 'react-super-responsive-table';

export const CertificatesListSkeleton: React.FC = () => {
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
				</Tr>
			))}
		</>
	);
};
