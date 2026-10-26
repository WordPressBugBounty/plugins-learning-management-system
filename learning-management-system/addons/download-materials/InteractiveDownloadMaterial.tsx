import {
	Button,
	Icon,
	IconButton,
	Image,
	List,
	ListItem,
	Modal,
	ModalBody,
	ModalCloseButton,
	ModalContent,
	ModalHeader,
	ModalOverlay,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __, _n, sprintf } from '@wordpress/i18n';
import React, { useState } from 'react';
import { BiDownload, BiLock, BiShow } from 'react-icons/bi';
import MasteriyoPlayer from '../../assets/js/back-end/components/common/masteriyoPlayer/MasteriyoPlayer';

interface Props {
	items: DownloadMaterials;
	accessMessage?: string;
}

const videoMimeTypes = [
	'video/mp4',
	'video/mpeg',
	'video/webm',
	'video/ogg',
	'video/mkv',
	'video/avi',
	'video/x-flv',
	'video/quicktime',
	'video/x-ms-wmv',
	'video/flv',
];

const audioMimeTypes = [
	'audio/mp3',
	'audio/m4a',
	'audio/oga',
	'audio/ogg',
	'audio/wav',
	'audio/opus',
	'audio/flac',
	'audio/x-m4a',
	'audio/x-ms-wma',
	'audio/mpeg',
	'audio/wma',
];

const imageMimeTypes = [
	'image/jpeg',
	'image/png',
	'image/jpg',
	'image/gif',
	'image/webp',
];

const isPreviewable = (file: DownloadMaterial) =>
	file.mime_type !== 'application/zip' &&
	(audioMimeTypes.includes(file.mime_type as string) ||
		videoMimeTypes.includes(file.mime_type as string) ||
		imageMimeTypes.includes(file.mime_type as string) ||
		file.mime_type === 'application/pdf');

const InteractiveDownloadMaterial: React.FC<Props> = (props) => {
	const { items: attachments, accessMessage } = props;
	const [previewFile, setPreviewFile] = useState<DownloadMaterial | null>(null);

	if (accessMessage) {
		return (
			<Stack
				direction="row"
				align="flex-start"
				spacing="10px"
				px="14px"
				py="12px"
				borderRadius="8px"
				border="1px solid"
				borderColor="player.border"
				bg="player.bg"
				fontSize="14px"
				lineHeight="1.5"
				color="player.text2"
			>
				<Icon as={BiLock} fontSize="16px" flexShrink={0} mt="2px" />
				<Text>{accessMessage}</Text>
			</Stack>
		);
	}

	const extension = (file: DownloadMaterial) => {
		const fromTitle = (file.title || '').split('.').pop() || '';
		const fromUrl = (file.url || '').split('?')[0].split('.').pop() || '';
		const ext = fromTitle.length <= 5 ? fromTitle : fromUrl;
		return ext.slice(0, 5).toUpperCase() || 'FILE';
	};

	return (
		<>
			<Stack direction="column" spacing="8px" mt="6px">
				<Text fontSize="13px" fontWeight="700" color="player.text">
					{sprintf(
						/* translators: %d: number of files */
						_n(
							'Materials · %d file',
							'Materials · %d files',
							attachments?.length ?? 0,
							'learning-management-system',
						),
						attachments?.length ?? 0,
					)}
				</Text>
				<List spacing="8px">
					{attachments?.map((file, index) => (
						<ListItem
							key={index}
							display="flex"
							alignItems="center"
							gap="12px"
							border="1px solid"
							borderColor="player.border"
							borderRadius="10px"
							px="14px"
							py="10px"
						>
							<Text
								as="span"
								fontFamily="mono"
								data-mono
								fontSize="9px"
								fontWeight="600"
								lineHeight="1"
								color="player.text3"
								bg="player.subtle"
								borderRadius="4px"
								px="5px"
								py="4px"
								flexShrink={0}
							>
								{extension(file)}
							</Text>
							<Text
								as="span"
								flex="1"
								minW={0}
								fontSize="14px"
								color="player.text"
								isTruncated
								title={file.title}
							>
								{file.title}
							</Text>
							<Text
								as="span"
								fontSize="12px"
								color="player.muted"
								flexShrink={0}
							>
								{file.formatted_file_size}
							</Text>
							{isPreviewable(file) && (
								<IconButton
									aria-label={__('Preview file', 'learning-management-system')}
									title={__('Preview file', 'learning-management-system')}
									icon={<BiShow />}
									onClick={() => setPreviewFile(file)}
									variant="player-secondary"
									w="32px"
									h="32px"
									px={0}
								/>
							)}
							<Button
								as="a"
								href={file.url}
								download
								variant="player-secondary"
								h="32px"
								px="12px"
								fontSize="13px"
								flexShrink={0}
								leftIcon={<Icon as={BiDownload} fontSize="14px" />}
								iconSpacing="6px"
							>
								{__('Download', 'learning-management-system')}
							</Button>
						</ListItem>
					))}
				</List>
			</Stack>

			<Modal
				isOpen={!!previewFile}
				onClose={() => setPreviewFile(null)}
				size="5xl"
				isCentered
			>
				<ModalOverlay />
				<ModalContent
					h={
						'application/pdf' === previewFile?.mime_type
							? 'calc(100vh - 100px)'
							: undefined
					}
					maxH="calc(100vh - 100px)"
				>
					<ModalHeader>{previewFile?.title}</ModalHeader>
					<ModalCloseButton />
					<ModalBody overflow="auto" pb="6">
						{audioMimeTypes.includes(previewFile?.mime_type as string) ? (
							<audio
								controls
								controlsList="nodownload"
								style={{ width: '100%' }}
							>
								<source
									src={previewFile?.preview_url || previewFile?.url}
									type={previewFile?.mime_type}
								/>
							</audio>
						) : videoMimeTypes.includes(previewFile?.mime_type as string) ? (
							<MasteriyoPlayer
								sourceUrl={previewFile?.preview_url || previewFile?.url || ''}
								enableSeeking
							/>
						) : imageMimeTypes.includes(previewFile?.mime_type as string) ? (
							<Image
								src={previewFile?.preview_url || previewFile?.url}
								maxH="100%"
								objectFit="contain"
							/>
						) : previewFile?.mime_type === 'application/pdf' ? (
							<iframe
								src={previewFile?.preview_url || previewFile?.url}
								style={{ width: '100%', height: '100%', border: 'none' }}
								title={previewFile?.title}
							/>
						) : null}
					</ModalBody>
				</ModalContent>
			</Modal>
		</>
	);
};

export default InteractiveDownloadMaterial;
