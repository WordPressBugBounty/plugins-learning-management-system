import { SimpleGrid, Stack } from '@chakra-ui/react';
import { useQuery } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import EmptyState from '../../../../../../assets/js/account/common/EmptyState';
import PageTitle from '../../../../../../assets/js/account/common/PageTitle';
import CoursesSkeleton from '../../../../../../assets/js/account/skeleton/Courses';
import localized from '../../../../../../assets/js/account/utils/global';
import MasteriyoPagination from '../../../../../../assets/js/back-end/components/common/MasteriyoPagination';
import API from '../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../assets/js/back-end/utils/utils';
import urls from '../../back-end/constants/urls';
import WishlistItem from './components/WishlistItem';

interface PaginationParams {
	per_page?: number;
	page?: number;
}

const Wishlist: React.FC = () => {
	const [paginationParams, setPaginationParams] = useState<PaginationParams>({
		per_page: 5,
		page: 1,
	});

	const wishlistItemsAPI = new API(urls.wishlist_items);
	const wishlistItemsQuery = useQuery<WishlistItemsApiResponse>({
		queryKey: ['wishlistItems', paginationParams],
		queryFn: () =>
			wishlistItemsAPI.list({
				...paginationParams,
				author: localized.current_user_id,
			}),
	});

	if (wishlistItemsQuery.isLoading) {
		return <CoursesSkeleton />;
	}

	return (
		<Stack direction="column" spacing={8} width="full">
			<PageTitle title={__('Wishlist', 'learning-management-system')} />
			<Stack direction="column" spacing="8" width="full">
				{isEmpty(wishlistItemsQuery.data?.data) ? (
					<EmptyState
						label={__(
							"You don't have any course in wishlist.",
							'learning-management-system',
						)}
					/>
				) : (
					<SimpleGrid
						columns={{
							base: 1,
							md: 2,
							xl: 'no' === localized?.showHeaderFooter ? 4 : 3,
						}}
						spacing="6"
					>
						{wishlistItemsQuery.data?.data.map((item) => (
							<WishlistItem key={item.id} data={item} />
						))}
					</SimpleGrid>
				)}

				{wishlistItemsQuery.isSuccess &&
					!isEmpty(wishlistItemsQuery?.data?.data) && (
						<MasteriyoPagination
							metaData={wishlistItemsQuery?.data?.meta}
							setFilterParams={setPaginationParams}
							perPageText={__('Items Per Page:', 'learning-management-system')}
						/>
					)}
			</Stack>
		</Stack>
	);
};

export default Wishlist;
