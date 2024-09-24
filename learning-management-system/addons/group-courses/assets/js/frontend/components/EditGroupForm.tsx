import {
	Box,
	Button,
	ButtonGroup,
	FormControl,
	FormLabel,
	Stack,
	useBreakpointValue,
	useToast,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { FormProvider, useForm } from 'react-hook-form';
import { useMutation, useQueryClient } from 'react-query';
import { useNavigate } from 'react-router-dom';
import Editor from '../../../../../../assets/js/back-end/components/common/Editor';
import API from '../../../../../../assets/js/back-end/utils/api';
import { deepClean } from '../../../../../../assets/js/back-end/utils/utils';
import EmailsInput from '../../common/components/EmailsInput';
import Name from '../../common/components/Name';
import { urls } from '../../constants/urls';
import { groupsBackendRoutes } from '../../routes/routes';
import { GroupSchema } from '../../types/group';

interface Props {
	group: GroupSchema;
	onExpandedGroupsChange: (value: number | null) => void;
}

const EditGroupForm: React.FC<Props> = ({ group, onExpandedGroupsChange }) => {
	const methods = useForm<GroupSchema>();
	const toast = useToast();
	const navigate = useNavigate();
	const groupAPI = new API(urls.groups);
	const queryClient = useQueryClient();
	const buttonSize = useBreakpointValue(['sm', 'md']);

	const updateGroup = useMutation<GroupSchema>(
		(data) => groupAPI.update(group.id, data),
		{
			onSuccess: () => {
				queryClient.invalidateQueries(`group${group.id}`);
				queryClient.invalidateQueries(`groupsList`);
				onExpandedGroupsChange(null);
				toast({
					title: __(
						'Group updated successfully.',
						'learning-management-system',
					),
					isClosable: true,
					status: 'success',
				});
				navigate(groupsBackendRoutes.list);
			},

			onError: (error: any) => {
				const message: any = error?.message
					? error?.message
					: error?.data?.message;

				toast({
					title: __(
						'Failed to update the group.',
						'learning-management-system',
					),
					description: message ? `${message}` : undefined,
					status: 'error',
					isClosable: true,
				});
			},
		},
	);

	const onSubmit = (data: GroupSchema) => {
		updateGroup.mutate(deepClean(data));
	};

	return (
		<Box mt={3}>
			<FormProvider {...methods}>
				<form onSubmit={methods.handleSubmit(onSubmit)}>
					<Stack direction="column" spacing="6">
						<Name defaultValue={group?.title || ''} />
						<FormControl>
							<FormLabel>
								{__('Group Description', 'learning-management-system')}
							</FormLabel>
							<Editor
								id="mto-group-description"
								name="description"
								defaultValue={group?.description || ''}
								height={200}
							/>
						</FormControl>
						<EmailsInput defaultValue={group?.emails || []} />
						<ButtonGroup>
							<Button
								type="submit"
								size={buttonSize}
								colorScheme="primary"
								isLoading={updateGroup.isLoading}
							>
								{__('Update Group', 'learning-management-system')}
							</Button>
							<Button
								size={buttonSize}
								variant="outline"
								onClick={() => onExpandedGroupsChange(null)}
							>
								{__('Cancel', 'learning-management-system')}
							</Button>
						</ButtonGroup>
					</Stack>
				</form>
			</FormProvider>
		</Box>
	);
};

export default EditGroupForm;
