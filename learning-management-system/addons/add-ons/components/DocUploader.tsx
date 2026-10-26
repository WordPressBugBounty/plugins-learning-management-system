import {
	FormControl,
	FormLabel,
	Icon,
	Spacer,
	Stack,
	Text,
	useColorModeValue,
	useToast,
} from '@chakra-ui/react';
import { __, _x, sprintf } from '@wordpress/i18n';
import { uploadMedia } from '@wordpress/media-utils';
import React, { useEffect, useRef, useState } from 'react';
import { Accept, useDropzone } from 'react-dropzone';
import { useFormContext } from 'react-hook-form';
import { BiCloudUpload, BiPaperclip } from 'react-icons/bi';
import MediaUploader from '../../../assets/js/back-end/components/common/MediaUploader';
import ToolTip from '../../../assets/js/back-end/screens/settings/components/ToolTip';
import MediaAPI from '../../../assets/js/back-end/utils/media';
import {
	getFileNameFromURL,
	isEmpty,
} from '../../../assets/js/back-end/utils/utils';
import DocPreview from './DocPreview';

export type File = {
	name: string;
	preview: string;
	size: number;
};

export type Files = File[];

interface Props {
	defaultValue?: DownloadMaterials;
	name: {
		title: string;
		tooltipText?: string;
		keyIndex:
			| 'attachments'
			| 'download_materials'
			| 'assignment_attachments'
			| 'files'
			| 'pdf'
			| 'audio_source_files';
	};
	docPreviewNotice: string;
	maxUploadFileSize?: number;
	acceptedFileTypes?: Accept;
	useWPLibrary?: boolean;
	multiple?: boolean;
	showUploader?: boolean;
	fontSize?: string;
	/** `compact`: the player's small dashed "Attach a file" button, no label. */
	variant?: 'zone' | 'compact';
}
const defaultAcceptedFileTypes = {
	'image/*': ['.jpeg', '.png', '.jpg', '.gif'],
	'video/*': ['.mp4', '.mkv', '.avi', '.flv', '.mov', '.webm', '.wmv'],
	'video/x-flv': ['.flv'],
	'audio/*': [
		'.mpeg',
		'.wav',
		'.m4a',
		'.ogg',
		'.mp3',
		'.oga',
		'.opus',
		'.flac',
		'.wma',
	],
	'audio/x-m4a': ['.m4a'],
	'audio/ogg': ['.oga', '.ogg'],
	'audio/opus': ['.opus'],
	'audio/flac': ['.flac'],
	'audio/x-ms-wma': ['.wma'],
	'video/webm': ['.webm'],
	'video/x-ms-wmv': ['.wmv'],
	'application/zip': ['.zip'],
	'application/x-zip-compressed': ['.zip'],
	'application/msword': ['.msword'],
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
		'.docx',
	],
	'application/vnd.ms-powerpoint': ['.ppt'],
	'application/vnd.openxmlformats-officedocument.presentationml.presentation': [
		'.pptx',
	],
	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [
		'.xlsx',
	],
	'application/vnd.ms-excel': ['.xls'],
	'application/pdf': ['.pdf'],
};

const defaultAcceptedFileTypesWPMedia = [
	'application/pdf',
	'application/zip',
	'application/x-zip-compressed',
	'application/msword',
	'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
	'application/vnd.ms-powerpoint',
	'application/vnd.openxmlformats-officedocument.presentationml.presentation',
	'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
	'application/vnd.ms-excel',
	'image/jpeg',
	'image/png',
	'image/jpg',
	'image/gif',
	'video/mp4',
	'video/mkv',
	'video/avi',
	'video/flv',
	'video/mov',
	'video/webm',
	'video/x-ms-wmv',
	'video/x-flv',
	'video/quicktime',
	'audio/mpeg',
	'audio/wav',
	'audio/mpeg',
	'audio/wav',
	'audio/x-m4a',
	'audio/mp4',
	'audio/ogg',
	'audio/opus',
	'audio/flac',
	'audio/x-ms-wma',
	'audio/webm',
	'audio/x-wav',
];

const DocUploader: React.FC<Props> = (props) => {
	const {
		defaultValue,
		name: { title, keyIndex, tooltipText },
		docPreviewNotice,
		maxUploadFileSize, // In MB
		acceptedFileTypes,
		useWPLibrary = false,
		multiple = true,
		showUploader = true,
		fontSize,
		variant = 'zone',
	} = props;

	const [files, setFiles] = useState<DownloadMaterials>(defaultValue || []);
	const toast = useToast();
	const API = new MediaAPI();
	const { setValue } = useFormContext();
	const isMounted = useRef(false);

	useEffect(() => {
		if (isMounted.current) {
			setValue(keyIndex, files, { shouldDirty: true });
		} else {
			isMounted.current = true;
			setValue(keyIndex, files || []);
		}
	}, [files, setValue, keyIndex]);

	const { getRootProps, getInputProps, isDragActive } = useDropzone({
		accept: !isEmpty(acceptedFileTypes)
			? acceptedFileTypes
			: defaultAcceptedFileTypes,
		onDrop: (acceptedFiles) => {
			uploadMedia({
				filesList: acceptedFiles,
				allowedTypes: defaultAcceptedFileTypesWPMedia,
				maxUploadFileSize: maxUploadFileSize
					? maxUploadFileSize * 1048576
					: null,
				onFileChange: (attachments: any) => {
					setFiles([
						...files,
						...attachments?.map((attachment: any) => ({
							id: attachment?.id,
							url: attachment?.url,
							title: getFileNameFromURL(attachment?.url),
							mime_type: attachment?.mime_type,
							formatted_file_size: attachment?.masteriyo?.formatted_file_size,
							file_size: attachment?.masteriyo?.file_size,
						})),
					]);
				},

				onError: (err: any) => {
					toast({
						title: __('Error while uploading', 'learning-management-system'),
						// The message already leads with the file name — WordPress's
						// upload validators sprintf it in — so adding it here shows
						// the name twice.
						description: err?.message,
						status: 'error',
						isClosable: true,
					});
				},
			});
		},

		onDropRejected: () => {
			toast({
				title: __('Error while uploading', 'learning-management-system'),
				description: __(
					'Make sure you are uploading an appropriate file type',
					'learning-management-system',
				),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const onRemove = (attachment: DownloadMaterial) => {
		setFiles(files.filter((file) => file !== attachment));
	};

	// Light mode inherits the FormLabel theme color; the override only matters
	// on the interactive app's dark screens.
	const labelColor = useColorModeValue(undefined, 'white');
	const instructionColor = useColorModeValue('gray.700', 'gray.200');
	const zoneBg = useColorModeValue('gray.50', 'whiteAlpha.50');
	const zoneActiveBg = useColorModeValue('primary.100', 'whiteAlpha.100');
	const zoneBorder = useColorModeValue('gray.300', 'whiteAlpha.300');

	const sizeLimit = maxUploadFileSize
		? sprintf(
				/* translators: %d: maximum upload size in megabytes */
				_x(
					'Maximum upload size limit is %d MB.',
					'File upload size validation message',
					'learning-management-system',
				),
				maxUploadFileSize,
			)
		: undefined;

	if (variant === 'compact') {
		return (
			<FormControl w="auto">
				{showUploader ? (
					<Stack
						as="div"
						direction="row"
						display="inline-flex"
						align="center"
						spacing="6px"
						h="36px"
						px="12px"
						border="1px dashed"
						borderColor={isDragActive ? 'player.primary' : 'player.border2'}
						borderRadius="8px"
						bg={isDragActive ? 'player.tint' : 'player.card'}
						color="player.text3"
						fontSize="13px"
						fontWeight="600"
						cursor="pointer"
						_hover={{ bg: 'player.bg' }}
						sx={{ '&:focus-visible': { boxShadow: 'outline' } }}
						title={sizeLimit}
						{...getRootProps({ role: 'button' })}
					>
						<input {...getInputProps()} multiple={multiple} />
						<Icon as={BiPaperclip} fontSize="14px" aria-hidden="true" />
						<Text as="span">
							{__('Attach a file', 'learning-management-system')}
						</Text>
					</Stack>
				) : null}
				<DocPreview
					files={files}
					onRemove={(file) => onRemove(file)}
					docPreviewNotice={docPreviewNotice}
					fontSize={fontSize}
				/>
			</FormControl>
		);
	}

	return (
		<FormControl>
			<FormLabel color={labelColor}>
				{title}
				{tooltipText && <ToolTip label={tooltipText} />}
			</FormLabel>

			{showUploader && (
				<>
					<Stack
						direction="column"
						justify="center"
						align="center"
						border="1px dashed"
						borderColor={isDragActive ? 'primary.400' : zoneBorder}
						borderRadius="base"
						px="4"
						py="6"
						textAlign="center"
						cursor="pointer"
						transition="border-color 0.2s, background-color 0.2s"
						backgroundColor={isDragActive ? zoneActiveBg : zoneBg}
						_hover={{ borderColor: 'gray.400' }}
						{...getRootProps()}
					>
						<input {...getInputProps()} multiple={multiple} />
						<Icon
							as={BiCloudUpload}
							boxSize="6"
							color={isDragActive ? 'primary.400' : 'gray.400'}
						/>
						<Text fontSize="sm" fontWeight="medium" color={instructionColor}>
							{__(
								'Drop documents or click here to upload',
								'learning-management-system',
							)}
						</Text>
						{useWPLibrary ? (
							<MediaUploader
								buttonLabel={'WP Media Library'}
								modaltitle="Course Attachment"
								variant="surface"
								isMultiple={multiple}
								onSelect={(attachments: any) => {
									setFiles([
										...files,
										...attachments.map((attachment: any) => ({
											id: attachment?.id,
											url: attachment?.url,
											title: getFileNameFromURL(attachment?.url),
											mime_type: attachment?.mime,
											formatted_file_size: attachment?.filesizeHumanReadable,
											file_size: attachment?.filesizeInBytes,
										})),
									]);
								}}
								mediaType={defaultAcceptedFileTypesWPMedia}
								width={'auto'}
								size="sm"
							/>
						) : null}
						{sizeLimit ? (
							<Text fontSize="xs" color="gray.500">
								{sizeLimit}
							</Text>
						) : null}
					</Stack>
					<Spacer h="4" />
				</>
			)}

			<DocPreview
				files={files}
				onRemove={(file) => onRemove(file)}
				docPreviewNotice={docPreviewNotice}
				fontSize={fontSize}
			/>
		</FormControl>
	);
};

export default DocUploader;
