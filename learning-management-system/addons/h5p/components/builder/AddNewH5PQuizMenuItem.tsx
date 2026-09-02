import { Button, Icon, MenuItem, useToast } from '@chakra-ui/react';
import { useMutation, useQueryClient } from '@tanstack/react-query';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { useNavigate } from 'react-router-dom';
import { ProText } from '../../../../assets/js/back-end/components/common/upsell/ProShowcaseComponent';
import { triggerLicenseCheck } from '../../../../assets/js/back-end/components/LicenseCheck';
import { H5PQuiz } from '../../../../assets/js/back-end/constants/images';
import API from '../../../../assets/js/back-end/utils/api';
import {
	addContentToBuilderCache,
	isLicensePlanActive,
	removeContentFromBuilderCache,
} from '../../../../assets/js/back-end/utils/utils';
import h5pRoutes from '../../constants/routes';
import h5pUrls from '../../constants/urls';

interface Props {
	sectionID: number;
	courseID: number;
	/* Menu order for the new quiz: the section's current content count. */
	contentsCount?: number;
	/* Opens the new row's inline rename editor. */
	onContentCreated?: (id: number) => void;
}

/**
 * "Interactive quiz (H5P)" entry for the section builder's compact
 * add-content menu. Creation happens inline like the other quick-adds — the
 * quiz lands in the section as a draft and the toast's "Open editor" link is
 * the fast path to its builder, where H5P content is picked.
 */
const AddNewH5PQuizMenuItem: React.FC<Props> = ({
	sectionID,
	courseID,
	contentsCount = 0,
	onContentCreated,
}) => {
	const navigate = useNavigate();
	const toast = useToast();
	const queryClient = useQueryClient();
	const h5pQuizAPI = new API(h5pUrls.h5pQuizzes);
	const isPlanAllowed = isLicensePlanActive();

	const addSkeletonRow = (): number => {
		const temporaryId = -Date.now();
		addContentToBuilderCache(
			queryClient,
			[`builder${courseID}`, courseID],
			{
				id: temporaryId,
				name: __('Untitled interactive quiz', 'learning-management-system'),
				parent_id: sectionID,
				menu_order: contentsCount,
				status: 'draft',
				isLoading: true,
			},
			'h5p-quiz',
		);
		return temporaryId;
	};

	const removeSkeletonRow = (temporaryId?: number) => {
		if (!temporaryId) return;
		removeContentFromBuilderCache(
			queryClient,
			[`builder${courseID}`, courseID],
			temporaryId,
			sectionID,
		);
	};

	const addH5PQuiz = useMutation({
		mutationFn: () =>
			h5pQuizAPI.store({
				name: __('Untitled interactive quiz', 'learning-management-system'),
				course_id: courseID,
				parent_id: sectionID,
				status: 'draft',
				menu_order: contentsCount,
				h5p_content_ids: [],
			}),
		onMutate: () => addSkeletonRow(),
		onSuccess: (data: { id: number }, _variables, temporaryId) => {
			removeSkeletonRow(temporaryId);
			addContentToBuilderCache(
				queryClient,
				[`builder${courseID}`, courseID],
				data,
				'h5p-quiz',
			);
			onContentCreated?.(data.id);
			queryClient.invalidateQueries({ queryKey: [`builder${courseID}`] });

			toast({
				title: __(
					'Interactive quiz added to the outline.',
					'learning-management-system',
				),
				description: (
					<Button
						size="sm"
						variant="link"
						colorScheme="whiteAlpha"
						color="white"
						textDecoration="underline"
						onClick={() =>
							navigate(
								h5pRoutes.h5pQuiz.builder.edit
									.replace(':courseId', String(courseID))
									.replace(':h5pQuizId', String(data.id)),
							)
						}
					>
						{__('Open editor', 'learning-management-system')}
					</Button>
				),
				status: 'success',
				duration: 6000,
				isClosable: true,
			});
		},
		// Mutations bypass the QueryCache onError, so without this a failed
		// create is silent: the skeleton just disappears and no quiz appears.
		onError: (
			err: {
				message?: string;
				response?: { data?: { message?: string } };
			},
			_variables,
			temporaryId,
		) => {
			removeSkeletonRow(temporaryId);
			toast({
				title: __(
					'Failed to create the interactive quiz.',
					'learning-management-system',
				),
				description: err?.response?.data?.message || err?.message,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const icon = (
		<Icon as={H5PQuiz} fontSize="lg" fill="none" color="primary.500" />
	);

	if (!isPlanAllowed) {
		return (
			<MenuItem icon={icon} isDisabled>
				{__('Interactive quiz (H5P)', 'learning-management-system')} <ProText />
			</MenuItem>
		);
	}

	return (
		<MenuItem
			icon={icon}
			onClick={() => {
				if (!triggerLicenseCheck()) return;
				addH5PQuiz.mutate();
			}}
		>
			{__('Interactive quiz (H5P)', 'learning-management-system')}
		</MenuItem>
	);
};

export default AddNewH5PQuizMenuItem;
