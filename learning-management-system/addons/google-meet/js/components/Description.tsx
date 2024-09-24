import { Flex, FormControl, FormLabel } from '@chakra-ui/react';
import { createBlock, serialize } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import React, { useCallback, useState } from 'react';
import { UseFormReturn, useFormContext } from 'react-hook-form';
import { UseQueryResult } from 'react-query';
import BlockEditor from '../../../../assets/js/back-end/components/common/BlockEditor';
import ContentCreateWithAIModal from '../../../../assets/js/back-end/components/common/ContentCreateWithAIModal';
import Editor from '../../../../assets/js/back-end/components/common/Editor';
import localized from '../../../../assets/js/back-end/utils/global';

interface Props {
	defaultValue?: string;
	lessonName?: string;
	data?: UseQueryResult;
	isPublished?: () => boolean;
	isDrafted?: () => boolean;
	methods?: UseFormReturn;
	onSubmit?: (data: any, status?: 'draft' | 'publish') => void;
}

const Description: React.FC<Props> = (props) => {
	const { defaultValue, data, isDrafted, isPublished, methods, onSubmit } =
		props;
	const [editorValue, setEditorValue] = useState(defaultValue);
	const [blockAiContent, setBlockAiContent] = useState('');

	const actions = [
		{
			label: __('Preview', 'learning-management-system'),
			// action: () => window.open(data?.data?.preview_link, '_blank'),
			variant: 'tertiary',
		},
		{
			label:
				isDrafted && isDrafted()
					? __('Save to Draft', 'learning-management-system')
					: __('Switch To Draft', 'learning-management-system'),
			action: methods?.handleSubmit(
				(data) => onSubmit && onSubmit(data, 'draft'),
			),
			isLoading: data?.isLoading,
			variant: 'secondary',
		},
		{
			label:
				isPublished && isPublished()
					? __('Update', 'learning-management-system')
					: __('Publish', 'learning-management-system'),
			action: methods?.handleSubmit((data) => onSubmit && onSubmit(data)),
			isLoading: data?.isLoading,
			variant: 'primary',
		},
	];
	const { setValue } = useFormContext();

	const handleContentCreation = useCallback(
		(newContent: string) => {
			const data = serialize([
				createBlock('core/paragraph', {
					content: newContent,
				}),
			]);
			setEditorValue(data);
			setValue('answer', data);
			setBlockAiContent(newContent);
		},
		[setValue],
	);

	return (
		<FormControl>
			<Flex
				direction="row"
				alignItems="center"
				justifyContent="space-between"
				mb="4"
			>
				<FormLabel>{__('Description', 'learning-management-system')}</FormLabel>
				<ContentCreateWithAIModal
					onContentCreated={handleContentCreation}
					elementId="mto-assignment-description"
				/>
			</Flex>
			{'classic_editor' === localized.defaultEditor ? (
				<Editor
					id="mto-assignment-description"
					name="description"
					defaultValue={editorValue}
				/>
			) : (
				<BlockEditor
					defaultValue={editorValue}
					actions={actions}
					name="description"
					id="mto-assignment-description"
					blockAiContent={blockAiContent}
				/>
			)}
		</FormControl>
	);
};

export default Description;
