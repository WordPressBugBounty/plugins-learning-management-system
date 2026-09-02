import {
	AlertDialog,
	AlertDialogBody,
	AlertDialogContent,
	AlertDialogFooter,
	AlertDialogHeader,
	AlertDialogOverlay,
	Avatar,
	Box,
	Button,
	ButtonGroup,
	Heading,
	HStack,
	Icon,
	IconButton,
	Image,
	Link,
	Stack,
	Tag,
	TagLabel,
	Text,
	Tooltip,
	useToast,
} from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __, _x, sprintf } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { AiFillHeart } from 'react-icons/ai';
import { CustomIcon } from '../../../../../../../assets/js/back-end/components/common/CustomIcon';
import {
	Check,
	Rating,
	Trash,
} from '../../../../../../../assets/js/back-end/constants/images';
import API from '../../../../../../../assets/js/back-end/utils/api';
import { isEmpty } from '../../../../../../../assets/js/back-end/utils/utils';
import urls from '../../../back-end/constants/urls';

const difficultyBadgeColors = {
	beginner: 'var(--masteriyo-color-badge-green)',
	intermediate: 'var(--masteriyo-color-warning)',
	expert: 'var(--masteriyo-color-badge-pink)',
};

interface Props {
	data: WishlistItemSchema;
}

const WishlistItem: React.FC<Props> = (props) => {
	const { data } = props;
	const { course } = data;
	const courseTitle = course?.name ? course.name : data.course_title;
	const isCourseDeleted =
		!course || course?.status === 'trash' || course?.status === 'draft';
	const queryClient = useQueryClient();
	const toast = useToast();
	const [isDeleteModalOpen, setDeleteModalOpen] = useState(false);
	const wishlistItemsAPI = new API(urls.wishlist_items);
	const cancelDeleteModalRef = useRef<any>();

	const deleteWishlistItemMutation = useMutation({
		mutationFn: (id: number) => wishlistItemsAPI.delete(id),
		...{
			onSuccess: () => {
				toast({
					description: sprintf(
						/* translators: %s: course title */
						_x(
							'Removed "%s" from your wishlist.',
							'Wishlist remove success message',
							'learning-management-system',
						),
						courseTitle,
					),
					isClosable: true,
					status: 'success',
				});
				setDeleteModalOpen(false);
				queryClient.invalidateQueries({ queryKey: ['wishlistItems'] });
			},
			onError: (error: any) => {
				toast({
					title: sprintf(
						/* translators: %s: course title */
						_x(
							'Remove "%s" from your wishlist?',
							'Wishlist remove confirmation',
							'learning-management-system',
						),
						courseTitle,
					),
					description: `${error?.response?.data?.message}`,
					isClosable: true,
					status: 'error',
				});
			},
		},
	});

	const onDeletePress = () => {
		setDeleteModalOpen(true);
	};
	const onDeleteModalClose = () => {
		setDeleteModalOpen(false);
	};
	const onDeleteConfirm = () => {
		deleteWishlistItemMutation.mutate(data.id);
	};

	return (
		<Box
			borderWidth="1px"
			borderColor="icy-blue-gray"
			bg="white"
			pos="relative"
			rounded={'xl'}
			maxH={'fit-content'}
		>
			<Box as="figure" pos="relative" mt={0}>
				<Image
					src={
						course ? course.featured_image_url : data.default_featured_image_url
					}
					alt={courseTitle}
					height="176px"
					width={'100%'}
					objectFit="cover"
					roundedTopLeft={'xl'}
					roundedTopRight={'xl'}
				/>
				{course?.difficulty ? (
					<Tag
						colorScheme="primary"
						size="sm"
						borderRadius="base"
						variant={'outline'}
						color={'white'}
						textTransform={'capitalize'}
						py={1.5}
						px={3}
						fontSize={'xs'}
						fontWeight={'medium'}
						letterSpacing={'wide'}
						pos="absolute"
						top="4"
						left="4"
						bg={
							difficultyBadgeColors[course?.difficulty.slug]
								? difficultyBadgeColors[course?.difficulty.slug]
								: 'blue.500'
						}
						boxShadow={'none'}
					>
						<TagLabel>{course?.difficulty?.name}</TagLabel>
					</Tag>
				) : null}
				{course?.featured ? (
					<Text
						fontSize="sm"
						fontWeight={500}
						backgroundColor="var(--masteriyo-color-primary)"
						color="white"
						py={0}
						pr={3}
						pl={1}
						pos="absolute"
						right="-6px"
						top="3"
						_before={{
							content: '""',
							position: 'absolute',
							top: 0,
							left: '-24px',
							border: '12px solid var(--masteriyo-color-primary)',
							borderLeftColor: 'transparent',
						}}
						_after={{
							content: '""',
							position: 'absolute',
							height: 0,
							width: 0,
							borderBottom: '6px solid transparent',
							borderLeft: '5px solid var(--masteriyo-color-primary)',
							bottom: '-6px',
							right: '0px',
						}}
					>
						{__('Featured', 'learning-management-system')}
					</Text>
				) : null}
			</Box>
			<Stack direction="column" p={5} spacing="6">
				<Stack gap={3} width={'full'} align={'start'}>
					<Stack direction="column" spacing={2.5} w={'full'}>
						{!isEmpty(course?.categories) ? (
							<Stack direction="row" spacing="1" flexWrap={'wrap'}>
								{course?.categories?.map(
									(category: { id: number; name: string; slug: string }) => (
										<Tag
											key={category.id}
											colorScheme="primary"
											size="sm"
											borderRadius="base"
											border="1px"
											borderWidth={1}
											borderColor="icy-blue-gray"
											variant={'outline'}
											color={'primary.500'}
											textTransform={'uppercase'}
											py={1}
											px={2.5}
											fontSize={'2xs'}
											fontWeight={'medium'}
											lineHeight={'120%'}
										>
											<TagLabel>{category?.name}</TagLabel>
										</Tag>
									),
								)}
							</Stack>
						) : null}

						<Stack
							justifyContent={'space-between'}
							alignItems={'start'}
							spacing={0}
							w={'full'}
						>
							<HStack w={'full'} align={'flex-start'}>
								<Heading
									as="h3"
									fontSize="md"
									fontWeight={'semibold'}
									color={'oxford-night'}
									flex={1}
								>
									<Link
										href={course?.permalink}
										sx={{
											textDecoration: 'none !important',
											color: 'inherit !important',
										}}
									>
										{courseTitle}
									</Link>
								</Heading>
								{!isCourseDeleted ? (
									<Tooltip
										label={__(
											'Remove from Wishlist',
											'learning-management-system',
										)}
										hasArrow
										fontSize="xs"
									>
										<IconButton
											icon={<AiFillHeart size={25} />}
											variant="unstyled"
											fontSize="xl"
											aria-label={__(
												'Remove from Wishlist',
												'learning-management-system',
											)}
											height="auto"
											minW="initial"
											onClick={onDeletePress}
											color="coral-red"
										/>
									</Tooltip>
								) : null}
							</HStack>
						</Stack>
					</Stack>

					{!isCourseDeleted ? (
						<HStack
							direction="row"
							align="center"
							justify={'space-between'}
							w={'full'}
							gap={5}
						>
							<HStack flexGrow={1} gap={2}>
								<Avatar src={course?.author?.avatar_url} size="sm" />
								<Text
									color={'saint-blue'}
									fontSize={'sm'}
									fontWeight={'medium'}
								>
									{course?.author?.display_name}
								</Text>
							</HStack>

							<Tooltip
								hasArrow
								label={
									course?.review_count
										? `${__('Average Rating:', 'learning-management-system')} ${Number(course.average_rating)} (${Number(course?.review_count)} ${__('reviews', 'learning-management-system')})`
										: `${__('Average Rating:', 'learning-management-system')} ${Number(course.average_rating)}`
								}
								fontSize="sm"
								placement="top"
							>
								<HStack gap={1.5}>
									<Icon
										as={Rating}
										color={'rating-gold'}
										fill={'currentColor'}
									/>
									<Text
										color={'saint-blue'}
										fontSize={'sm'}
										fontWeight={'medium'}
									>
										{course?.review_count
											? `${Number(course.average_rating)} (${Number(course?.review_count)})`
											: `${Number(course.average_rating)}`}
									</Text>
								</HStack>
							</Tooltip>
						</HStack>
					) : null}
				</Stack>
				{!isCourseDeleted ? (
					<Stack
						direction="row"
						spacing="4"
						justify={{ base: 'center', sm: 'space-between' }}
						align="center"
						color="gray.500"
						fontSize="xs"
						flexWrap={{ base: 'wrap', sm: 'nowrap' }}
					>
						<HStack w={'full'}>
							<Link w={'full'} href={course?.buy_button.url}>
								<Button
									colorScheme="button"
									size="md"
									w="full"
									isDisabled={course?.progress_data?.status === 'completed'}
									variant={
										course?.progress_data?.status === 'completed'
											? 'link'
											: 'solid'
									}
									leftIcon={
										course?.progress_data?.status === 'completed' ? (
											<Icon fontSize="xl" as={Check} color={'green.400'} />
										) : undefined
									}
								>
									<Text
										dangerouslySetInnerHTML={{
											__html: course ? course?.buy_button.text : '',
										}}
									/>
								</Button>
							</Link>
						</HStack>
					</Stack>
				) : null}
			</Stack>

			{isCourseDeleted ? (
				<Box
					pos="absolute"
					left={0}
					right={0}
					top={0}
					bottom={0}
					bg="whiteAlpha.800"
					p={6}
					display="flex"
					alignItems="center"
					justifyContent="center"
				>
					<Stack alignItems="center">
						<Text fontWeight="semibold" fontSize="md">
							{__(
								'This course has been deleted.',
								'learning-management-system',
							)}
						</Text>
						<Button
							colorScheme="red"
							size="md"
							variant="link"
							_hover={{ backgroundColor: 'transparent' }}
							leftIcon={<CustomIcon icon={Trash} boxSize="12px" />}
							onClick={onDeletePress}
						>
							{__('Remove from Wishlist', 'learning-management-system')}
						</Button>
					</Stack>
				</Box>
			) : null}

			<AlertDialog
				isOpen={isDeleteModalOpen}
				onClose={onDeleteModalClose}
				isCentered
				leastDestructiveRef={cancelDeleteModalRef}
			>
				<AlertDialogOverlay>
					<AlertDialogContent>
						<AlertDialogHeader>
							{sprintf(
								/* translators: %s: course title */
								_x(
									'Remove "%s" from your wishlist?',
									'Wishlist remove confirmation prompt',
									'learning-management-system',
								),
								courseTitle,
							)}
						</AlertDialogHeader>
						<AlertDialogBody>
							{__(
								"Are you sure? You can't restore it after removing.",
								'learning-management-system',
							)}
						</AlertDialogBody>
						<AlertDialogFooter>
							<ButtonGroup>
								<Button
									ref={cancelDeleteModalRef}
									onClick={onDeleteModalClose}
									variant="outline"
									colorScheme={'button'}
								>
									{__('Cancel', 'learning-management-system')}
								</Button>
								<Button
									colorScheme="red"
									onClick={onDeleteConfirm}
									isLoading={deleteWishlistItemMutation.isPending}
								>
									{__('Remove', 'learning-management-system')}
								</Button>
							</ButtonGroup>
						</AlertDialogFooter>
					</AlertDialogContent>
				</AlertDialogOverlay>
			</AlertDialog>
		</Box>
	);
};

export default WishlistItem;
