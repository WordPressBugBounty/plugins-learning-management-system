import { Box, Flex, FormControl, FormLabel, Textarea } from '@chakra-ui/react';
import { serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { uploadMedia } from '@wordpress/media-utils';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiExitFullscreen, BiFullscreen } from 'react-icons/bi';
import StandaloneEditor, {
	EditorLoaded,
	ToolbarSlot,
} from '../../../../../assets/js/back-end/components/common/StandaloneEditor';
import localized from '../../../../../assets/js/back-end/utils/global';
import { addMediaUpload, addSupportedBlocks } from '../utils/blocks';

type Props = {
	fullscreenMode: boolean;
	setFullscreenMode: (value: boolean) => void;
	defaultValue?: string;
	actions?: {
		label: string;
		action: () => void;
		variant?: 'primary' | 'secondary' | 'tertiary' | 'link';
		isLoading?: boolean;
	}[];
};

const BlockEditor: React.FC<Props> = (props) => {
	const { defaultValue, actions, fullscreenMode, setFullscreenMode } = props;
	const { register, setValue } = useFormContext();

	// Typed as an array in types/index.d.ts, but PHP localizes an object.
	const editorSettingsObject = localized.editorSettings as unknown as
		| { styles?: { css: string }[] }
		| undefined;
	const baseStyles = Array.isArray(editorSettingsObject?.styles)
		? editorSettingsObject.styles
		: [];
	const editorStyles = Array.isArray(localized.editorStyles)
		? localized.editorStyles
		: [];

	return (
		<FormControl>
			<FormLabel>
				{__('Certificate Content', 'learning-management-system')}
			</FormLabel>
			<Textarea {...register('html_content')} hidden />
			<Box
				height="2xl"
				className="masteriyo-standalone-editor"
				mt={{ base: 10, md: 0 }}
			>
				<StandaloneEditor
					id="masteriyo-certificate-builder"
					settings={{
						...localized.editorSettings,
						styles: [...baseStyles, ...editorStyles],
						availableTemplates: [],
						disablePostFormats: true,
						__experimentalBlockPatterns: [],
						__experimentalBlockPatternCategories: [],
						enableCustomFields: false,
						generateAnchors: false,
						canLockBlocks: true,
						supportsLayout: true,
						mediaUpload: uploadMedia,
						allowedBlockTypes: localized.allowedBlockTypes,
						templateLock: true,
						template: [['masteriyo/certificate']],
					}}
					onSaveBlocks={(blocks) =>
						setValue('html_content', serialize(blocks), { shouldDirty: true })
					}
					onLoad={(parse) => parse(defaultValue || '')}
				>
					<EditorLoaded
						onLoaded={() => {
							addMediaUpload();
							addSupportedBlocks();
						}}
					/>
					<ToolbarSlot>
						<Flex gap={3}>
							{actions && fullscreenMode
								? actions.map((action, index) => (
										<Button
											variant={action?.variant}
											onClick={action.action}
											isBusy={action.isLoading}
											key={index}
										>
											{action.label}
										</Button>
									))
								: null}
							<Button
								icon={fullscreenMode ? <BiExitFullscreen /> : <BiFullscreen />}
								onClick={() => {
									document.body.classList.toggle('is-fullscreen-mode');
									setFullscreenMode(!fullscreenMode);
								}}
								label={__('Toggle Fullscreen', 'learning-management-system')}
								isPressed={fullscreenMode}
							/>
						</Flex>
					</ToolbarSlot>
				</StandaloneEditor>
			</Box>
		</FormControl>
	);
};

export default BlockEditor;
