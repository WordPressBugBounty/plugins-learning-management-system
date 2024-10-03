import {
	FormControl,
	FormLabel,
	Icon,
	Spacer,
	Stack,
	Text,
	useToast,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import { uploadMedia } from '@wordpress/media-utils';
import React, { useEffect, useState } from 'react';
import { Accept, useDropzone } from 'react-dropzone';
import { useFormContext } from 'react-hook-form';
import { BiPlus } from 'react-icons/bi';
import { useMutation } from 'react-query';
import MediaUploader from '../../../assets/js/back-end/components/common/MediaUploader';
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
		keyIndex:
			| 'attachments'
			| 'download_materials'
			| 'assignment_attachments'
			| 'files';
	};
	docPreviewNotice: string;
	maxUploadFileSize?: number;
	acceptedFileTypes?: Accept;
	useWPLibrary?: boolean;
}

const DocUploader: React.FC<Props> = (props) => {
	const {
		defaultValue,
		name: { title, keyIndex },
		docPreviewNotice,
		maxUploadFileSize, // In MB
		acceptedFileTypes,
		useWPLibrary = false,
	} = props;

	const [files, setFiles] = useState<DownloadMaterials>(defaultValue || []);
	const toast = useToast();
	const API = new MediaAPI();
	const { setValue } = useFormContext();

	useEffect(() => {
		setValue(keyIndex, files);
	}, [files, setValue, keyIndex]);

	const deleteFileMutation = useMutation((id: number) => API.delete(id), {
		onSuccess: (data: { deleted: boolean; previous: any }) => {
			toast({
				title: `${data?.previous?.title?.raw} has been deleted`,
				status: 'success',
				isClosable: true,
			});
		},

		onError: (data: any) => {
			toast({
				title: data?.message,
				status: 'error',
				isClosable: true,
			});
		},
	});

	const defaultAcceptedFileTypes = {
		'image/*': ['.jpeg', '.png', '.jpg', '.gif'],
		'video/*': ['.mp4', '.mkv', '.avi', '.flv', '.mov'],
		'audio/*': ['.mpeg', '.wav'],
		'application/zip': ['.zip'],
		'application/msword': ['.msword'],
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document': [
			'.docx',
		],
		'application/vnd.ms-powerpoint': ['.ppt'],
		'application/vnd.openxmlformats-officedocument.presentationml.presentation':
			['.pptx'],
		'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet': [
			'.xlsx',
		],
		'application/vnd.ms-excel': ['.xls'],
	};

	const defaultAcceptedFileTypesWPMedia = [
		'application/pdf',
		'application/zip',
		'application/msword',
		'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
		'	application/vnd.ms-powerpoint',
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
		'audio/mpeg',
		'audio/wav',
	];

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
						description: `${err?.file?.name}  ${err?.message}`,
						status: 'error',
						isClosable: true,
					});
				},
			});
		},

		onDropRejected: () => {
			toast({
				title: __('Error while uploading', 'learning-management-system'),
				description: __('make sure you are uploading appropriate file type'),
				status: 'error',
				isClosable: true,
			});
		},
	});

	const onRemove = (attachment: DownloadMaterial) => {
		deleteFileMutation.mutate(attachment.id, {
			onSuccess: () => {
				setFiles(files.filter((file) => file !== attachment));
			},
		});
	};

	return (
		<FormControl>
			<FormLabel>{title}</FormLabel>
			<Stack
				direction="column"
				justify="center"
				align="center"
				border="1px"
				borderStyle="dashed"
				borderColor="gray.400"
				px="4"
				py="8"
				textAlign="center"
				backgroundColor={isDragActive ? 'blue.100' : 'transparent'}
				{...getRootProps()}
			>
				<input {...getInputProps()} />
				<Stack
					direction="row"
					color="blue.700"
					align="center"
					fontWeight="medium"
				>
					<Icon as={BiPlus} fontSize="2xl" />
					<Text>
						{__(
							'Drop documents or click here to upload',
							'learning-management-system',
						)}
					</Text>
				</Stack>
				{useWPLibrary ? (
					<MediaUploader
						buttonLabel={'WP Media Library'}
						modalTitle="Course Attachment"
						onSelect={(attachments: any) => {
							setFiles([
								...files,
								...attachments.map((attachment: any) => ({
									id: attachment?.id,
									url: attachment?.url,
									title: getFileNameFromURL(attachment?.url),
									mime_type: attachment?.mime_type,
									formatted_file_size:
										attachment?.masteriyo?.formatted_file_size,
									file_size: attachment?.masteriyo?.file_size,
								})),
							]);
						}}
						mediaType={defaultAcceptedFileTypesWPMedia}
						width={'auto'}
						size="sm"
					/>
				) : null}
				{maxUploadFileSize ? (
					<Text fontSize="sm" color="gray.500">
						(
						{sprintf(
							__(
								'Maximum upload size limit is %d MB.',
								'learning-management-system',
							),
							maxUploadFileSize,
						)}
						)
					</Text>
				) : null}
			</Stack>
			<Spacer h="30px" />

			<DocPreview
				files={files}
				onRemove={(file) => onRemove(file)}
				docPreviewNotice={docPreviewNotice}
			/>
		</FormControl>
	);
};

export default DocUploader;
