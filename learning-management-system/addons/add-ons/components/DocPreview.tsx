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
import { BiShow, BiTrash } from 'react-icons/bi';
import {
	BsFileArrowDown,
	BsFileExcel,
	BsFilePdf,
	BsFilePpt,
	BsFileWord,
	BsFileZip,
} from 'react-icons/bs';
import MasteriyoPlayer from '../../../assets/js/back-end/components/common/masteriyoPlayer/MasteriyoPlayer';
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
	} = props;
	const [currentFile, setCurrentFile] = useState<DownloadMaterial>();

	const onPreviewPress = (file: DownloadMaterial) => {
		setCurrentFile(file);
		onOpen();
	};

	const onDownloadPress = (file: DownloadMaterial) => {
		saveAs(file?.url, getFileNameFromURL(file?.url));
	};

	const videoMimeType = ['video/mp4', 'video/mpeg', 'video/webm', 'video/ogg'];
	const imageMimeType = [
		'image/jpeg',
		'image/png',
		'image/jpg',
		'image/gif',
		'image/webp',
	];

	return (
		<>
			<List>
				{isArray(files) &&
					files?.map((file, index) => (
						<ListItem
							key={index}
							borderBottom="1px"
							borderColor="gray.100"
							py="1"
							_last={{ border: 'none' }}
						>
							<Stack direction="row" align="center" justify="space-between">
								<Stack direction="row" align="center" justify={'center'}>
									<Center fontSize="md">{getIcon(file)}</Center>
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
											fontSize="xs"
											fontWeight="medium"
											cursor={isDownloadable ? 'pointer' : 'inherit'}
											_hover={{
												color: isDownloadable ? 'blue.500' : 'inherit',
											}}
											onClick={
												isDownloadable ? () => onDownloadPress(file) : undefined
											}
										>
											{getFileNameFromURL(file?.url)}
										</Text>
									</Tooltip>
								</Stack>
								<ButtonGroup size="md">
									<Text fontSize="x-small">{file?.formatted_file_size}</Text>
									{file?.mime_type !== 'application/zip' && isPreviewable ? (
										<IconButton
											w="auto"
											minW="auto"
											variant="link"
											_hover={{ color: 'blue' }}
											aria-label={__(
												'Delete download attachment file',
												'learning-management-system',
											)}
											icon={<BiShow />}
											onClick={() => onPreviewPress(file)}
										/>
									) : null}
									{onRemove ? (
										<IconButton
											w="auto"
											minW="auto"
											variant="link"
											_hover={{ color: 'red' }}
											aria-label={__(
												'Delete download attachment file',
												'learning-management-system',
											)}
											icon={<BiTrash />}
											onClick={() => onRemove(file)}
										/>
									) : null}
								</ButtonGroup>
							</Stack>
						</ListItem>
					))}
			</List>
			{!isEmpty(files) && !hidePreviewNotice ? (
				<>
					<Spacer h="10px" />
					<Text fontSize={'x-small'} color="gray.400">
						{docPreviewNotice}
					</Text>
				</>
			) : null}
			<Modal isOpen={isOpen} onClose={onClose} size="5xl">
				<ModalOverlay />
				<ModalContent h="calc(100vh - 100px)">
					<ModalHeader>{currentFile?.title}</ModalHeader>
					<ModalCloseButton />
					<ModalBody>
						{'audio/mpeg' === currentFile?.mime_type ||
						'audio/wav' === currentFile?.mime_type ? (
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
								videoURL={currentFile?.url || ''}
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
								url={currentFile?.url}
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
