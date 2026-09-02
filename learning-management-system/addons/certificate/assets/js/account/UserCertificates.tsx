import { Stack, VStack } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import EmptyState from '../../../../../assets/js/account/common/EmptyState';
import LazyLoader from '../../../../../assets/js/account/common/LazyLoader';
import PageTitle from '../../../../../assets/js/account/common/PageTitle';
import { certificateAddonUrls } from '../utils/urls';
import UserCertificateItem from './UserCertificateItem';
import { UserCertificatesSkeleton } from './skeletons';

const UserCertificates: React.FC = () => {
	const [myCertificates, setMyCertificates] = useState<UserCertificate[]>([]);
	const [certificatesLoading, setCertificatesLoading] = useState<boolean>(true);

	return (
		<VStack gap={8} align={'flex-start'} w={'full'}>
			<PageTitle
				title={__('Your Certificates', 'learning-management-system')}
			/>

			<Stack
				direction="column"
				spacing={6}
				className="mto-enrolled-courses-wrapper"
				w={'full'}
			>
				{myCertificates?.map((certificate) => {
					return (
						<UserCertificateItem key={certificate.id} data={certificate} />
					);
				})}

				<LazyLoader
					perPage={10}
					isLoading={certificatesLoading}
					setIsLoading={setCertificatesLoading}
					loaderComponent={<UserCertificatesSkeleton />}
					apiUrl={certificateAddonUrls.myCertificates}
					onDataLoaded={(newCertificates: UserCertificate[]) => {
						setMyCertificates((prev) => {
							const unique = newCertificates.filter(
								(nc) => !prev.some((pc) => pc.id === nc.id),
							);
							return prev.concat(unique);
						});
					}}
				/>
				{!myCertificates?.length && !certificatesLoading && (
					<EmptyState
						label={__(
							"You don't have any certificates yet.",
							'learning-management-system',
						)}
					/>
				)}
			</Stack>
		</VStack>
	);
};

export default UserCertificates;
