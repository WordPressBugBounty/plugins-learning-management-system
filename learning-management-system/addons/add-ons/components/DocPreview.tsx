import {
	ButtonGroup,
	Center,
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
	Spacer,
	Stack,
	Text,
	Tooltip,
	useDisclosure,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import saveAs from 'file-saver';
import React, { useState } from 'react';
import { DocumentViewer } from 'react-documents';
import { BiShow } from 'react-icons/bi';
import {
	BsFileArrowDown,
	BsFileExcel,
	BsFilePdf,
	BsFilePpt,
	BsFileWord,
	BsFileZip,
} from 'react-icons/bs';
import { CustomIcon } from '../../../assets/js/back-end/components/common/CustomIcon';
import MasteriyoPlayer from '../../../assets/js/back-end/components/common/masteriyoPlayer/MasteriyoPlayer';
import { Trash } from '../../../assets/js/back-end/constants/images';
import {
	getFileNameFromURL,
	isArray,
	isEmpty,
} from '../../../assets/js/back-end/utils/utils';
interface Props {
	files: DownloadMaterials;
	isDownloadable?: boolean;
	isPreviewable?: boolean;
	onRemove?: (file: DownloadMaterial) => void;
	docPreviewNotice: string;
	hidePreviewNotice?: boolean;
	fontSize?: string;
}

export const getIcon = (file: DownloadMaterial) => {
	switch (file.mime_type) {
		case 'application/msword':
			return <Icon as={BsFileWord} color="blue.400" />;
		case 'application/pdf':
			return <Icon as={BsFilePdf} color="red.400" />;
		case 'application/vnd.openxmlformats-officedocument.presentationml.presentation':
			return <Icon as={BsFilePpt} color="red.400" />;
		case 'application/vnd.openxmlformats-officedocument.wordprocessingml.document':
			return <Icon as={BsFileWord} color="blue.400" />;
		case 'application/zip':
			return <Icon as={BsFileZip} color="gray.500" />;
		case 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet':
			return <Icon as={BsFileExcel} color="green.400" />;
		case 'application/vnd.ms-excel':
			return <Icon as={BsFileExcel} color="green.400" />;
		default:
			return <Icon as={BsFileArrowDown} color="blue.400" />;
	}
};

const DocPreview: React.FC<Props> = (props) => {
	const { isOpen, onClose, onOpen } = useDisclosure();

	const {
		files,
		onRemove,
		isDownloadable,
		isPreviewable = true,
		docPreviewNotice,
		hidePreviewNotice = false,
		fontSize,
	} = props;

	const [currentFile, setCurrentFile] = useState<DownloadMaterial>();

	const onPreviewPress = (file: DownloadMaterial) => {
		setCurrentFile(file);
		onOpen();
	};

	const onDownloadPress = (file: DownloadMaterial) => {
		saveAs(file?.url, getFileNameFromURL(file?.url));
	};

	const videoMimeType = [
		'video/mp4',
		'video/mpeg',
		'video/webm',
		'video/ogg',
		'video/mkv',
		'video/avi',
		'video/webm',
		'video/x-flv',
		'video/quicktime',
		'video/x-ms-wmv',
		'video/flv',
	];

	const audioMimeType = [
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

	const imageMimeType = [
		'image/jpeg',
		'image/png',
		'image/jpg',
		'image/gif',
		'image/webp',
	];

	// The document viewer's iframe is height:100%; media sizes itself.
	const isDocument = ![
		...audioMimeType,
		...videoMimeType,
		...imageMimeType,
	].includes(currentFile?.mime_type as string);

	return (
		<>
			<List>
				{isArray(files) &&
					files?.map((file, index) => (
						<ListItem
							key={index}
							border="1px"
							borderColor="gray.100"
							rounded="base"
							bg="white"
							px="3"
							py="2"
							mb="3"
							_last={{ mb: 0 }}
							_hover={{ bg: 'gray.50' }}
						>
							<Stack direction="row" align="center" justify="space-between">
								<Stack
									direction="row"
									align="center"
									spacing="2"
									minW="0"
									flex="1"
								>
									<Center fontSize={fontSize || 'md'} flexShrink={0}>
										{getIcon(file)}
									</Center>

									<Tooltip
										hasArrow
										fontSize="xs"
										label={
											isDownloadable
												? __(
														'Click to download the file.',
														'learning-management-system',
													)
												: ''
										}
									>
										<Text
											fontSize={fontSize || 'sm'}
											fontWeight="normal"
											noOfLines={1}
											minW="0"
											cursor={isDownloadable ? 'pointer' : 'inherit'}
											_hover={{
												color: isDownloadable ? 'blue.500' : 'inherit',
											}}
											onClick={
												isDownloadable ? () => onDownloadPress(file) : undefined
											}
											color="gray.700"
											lineHeight={'24px'}
										>
											{file?.title || getFileNameFromURL(file?.url)}
										</Text>
									</Tooltip>
									<Text
										fontSize={fontSize || 'xs'}
										color="gray.400"
										flexShrink={0}
									>
										{file?.formatted_file_size}
									</Text>
								</Stack>
								<ButtonGroup size="sm" spacing="0.5" color="gray.400">
									{file?.mime_type !== 'application/zip' && isPreviewable ? (
										<Tooltip
											label={__('Preview', 'learning-management-system')}
										>
											<IconButton
												w="auto"
												minW="auto"
												variant="icon"
												// The icon variant sets no bg/color, so on frontend
												// pages the site theme's own button CSS leaks in
												// (white icon, theme bg on hover) — pin them.
												bg="transparent"
												border="none"
												color="gray.400"
												_hover={{ color: 'gray.900', bg: 'transparent' }}
												aria-label={__(
													'Preview file',
													'learning-management-system',
												)}
												icon={<BiShow fontSize={fontSize} />}
												onClick={() => onPreviewPress(file)}
											/>
										</Tooltip>
									) : null}
									{onRemove ? (
										<Tooltip label={__('Remove', 'learning-management-system')}>
											<IconButton
												minW="auto"
												variant="icon"
												_hover={{ color: 'red.500' }}
												aria-label={__(
													'Remove file',
													'learning-management-system',
												)}
												icon={<CustomIcon icon={Trash} boxSize="16px" />}
												onClick={() => onRemove(file)}
											/>
										</Tooltip>
									) : null}
								</ButtonGroup>
							</Stack>
						</ListItem>
					))}
			</List>
			{!isEmpty(files) && docPreviewNotice.length > 0 && !hidePreviewNotice ? (
				<>
					<Spacer h="10px" />
					<Text fontSize={'x-small'} color="gray.400">
						{docPreviewNotice}
					</Text>
				</>
			) : null}
			<Modal isOpen={isOpen} onClose={onClose} size="5xl" isCentered>
				<ModalOverlay />
				<ModalContent
					h={isDocument ? 'calc(100vh - 100px)' : undefined}
					maxH="calc(100vh - 100px)"
				>
					<ModalHeader>{currentFile?.title}</ModalHeader>
					<ModalCloseButton />
					<ModalBody overflow="auto" pb="6">
						{audioMimeType.includes(currentFile?.mime_type as string) ? (
							<audio
								controls
								controlsList="nodownload"
								style={{
									position: 'relative',
									width: '100%',
									background: 'gray.100',
								}}
							>
								<source
									src={currentFile?.url}
									type={currentFile?.mime_type}
								></source>
							</audio>
						) : videoMimeType.includes(currentFile?.mime_type as string) ? (
							<MasteriyoPlayer
								sourceUrl={currentFile?.url || ''}
								enableSeeking
							/>
						) : imageMimeType.includes(currentFile?.mime_type as string) ? (
							<Image src={currentFile?.url} />
						) : (
							<DocumentViewer
								viewerUrl={
									currentFile?.mime_type !== 'application/pdf'
										? 'https://docs.google.com/gview?url=%URL%&embedded=true'
										: undefined
								}
								url={currentFile?.preview_url || currentFile?.url}
								viewer="url"
							/>
						)}
					</ModalBody>
				</ModalContent>
			</Modal>
		</>
	);
};

export default DocPreview;
