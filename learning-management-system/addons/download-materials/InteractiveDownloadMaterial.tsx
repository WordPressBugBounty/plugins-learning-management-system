import {
	Box,
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
import { __ } from '@wordpress/i18n';
import React, { useState } from 'react';
import { BiLock, BiShow } from 'react-icons/bi';
import MasteriyoPlayer from '../../assets/js/back-end/components/common/masteriyoPlayer/MasteriyoPlayer';
import { getIcon } from '../add-ons/components/DocPreview';

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
			<Box
				display="flex"
				alignItems="center"
				gap="2"
				p="3"
				borderRadius="md"
				bg="orange.50"
				borderWidth="1px"
				borderColor="orange.200"
			>
				<Icon as={BiLock} color="orange.500" boxSize="4" flexShrink={0} />
				<Text fontSize="sm" color="orange.700">
					{accessMessage}
				</Text>
			</Box>
		);
	}

	return (
		<>
			<List>
				{attachments?.map((file, index) => (
					<ListItem
						key={index}
						borderBottom="1px"
						borderColor="gray.100"
						py="1"
						_last={{ border: 'none' }}
					>
						<Stack direction="row" align="center" justify="space-between">
							<Stack direction="row" align="center">
								<Box fontSize="md">{getIcon(file)}</Box>
								<Text
									as="a"
									href={file.url}
									fontSize="xs"
									fontWeight="normal"
									color="saint-blue"
									lineHeight="24px"
									cursor="pointer"
									_hover={{ color: 'blue.500' }}
									download
								>
									{file.title}
								</Text>
							</Stack>
							<Stack direction="row" align="center" spacing="2" flexShrink={0}>
								<Text fontSize="x-small" color="gray.500">
									{file.formatted_file_size}
								</Text>
								{isPreviewable(file) && (
									<IconButton
										w="auto"
										minW="auto"
										variant="link"
										_hover={{ color: 'blue.500' }}
										aria-label={__(
											'Preview file',
											'learning-management-system',
										)}
										icon={<BiShow />}
										onClick={() => setPreviewFile(file)}
									/>
								)}
							</Stack>
						</Stack>
					</ListItem>
				))}
			</List>

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
