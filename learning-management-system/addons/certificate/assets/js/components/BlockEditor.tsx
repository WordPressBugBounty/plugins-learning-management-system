import IsolatedBlockEditor, {
	EditorLoaded,
	ToolbarSlot,
} from '@automattic/isolated-block-editor';
import { Box, Flex, FormControl, FormLabel, Textarea } from '@chakra-ui/react';
// @ts-ignore
import '@automattic/isolated-block-editor/build-browser/core.css';
import '@automattic/isolated-block-editor/build-browser/isolated-block-editor.css';
import { serialize } from '@wordpress/blocks';
import { Button } from '@wordpress/components';
import { __ } from '@wordpress/i18n';
import { uploadMedia } from '@wordpress/media-utils';
import React from 'react';
import { useFormContext } from 'react-hook-form';
import { BiExitFullscreen, BiFullscreen } from 'react-icons/bi';
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
				<style>
					{
						'.is-fullscreen-mode .masteriyo-standalone-editor {height: 100vh !important;left: 0;position: fixed;top: 0;width: 100%;}.masteriyo-standalone-editor > div {width: 100%;height: 100%;overflow: auto;}.masteriyo-standalone-editor .iso-editor, .masteriyo-standalone-editor .iso-editor > div + div {height: 100%;}.masteriyo-standalone-editor .interface-interface-skeleton {height: 100%;}'
					}
				</style>
				<IsolatedBlockEditor
					id="masteriyo-certificate-builder"
					settings={{
						iso: {
							blocks: {
								allowBlocks: localized.allowedBlockTypes,
							},
							moreMenu: {
								topToolbar: true,
							},
							sidebar: {
								inserter: true,
								inspector: true,
							},
							toolbar: {
								navigation: true,
								inspector: true,
							},
							allowEmbeds: [],
						},
						editor: {
							...localized.editorSettings,
							availableTemplates: [],
							disablePostFormats: true,
							__experimentalBlockPatterns: [],
							__experimentalBlockPatternCategories: [],
							supportsTemplateMode: true,
							enableCustomFields: false,
							generateAnchors: false,
							canLockBlocks: true,
							postLock: true,
							supportsLayout: true,
							mediaUpload: uploadMedia,
							templateLock: true,
							template: [['masteriyo/certificate']],
						},
						editorType: 'core',
						allowUrlEmbed: false,
						pastePlainText: false,
						replaceParagraphCode: false,
					}}
					onSaveBlocks={(blocks: any) =>
						setValue('html_content', serialize(blocks))
					}
					onLoad={(parse: any) => parse(defaultValue)}
					onError={() => {}}
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
				</IsolatedBlockEditor>
			</Box>
		</FormControl>
	);
};

export default BlockEditor;
