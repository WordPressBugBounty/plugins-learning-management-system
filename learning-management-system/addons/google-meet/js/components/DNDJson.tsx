import { Box, Container, Input, Text, useToast } from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React, { useRef, useState } from 'react';
import { LuFileJson } from 'react-icons/lu';
import { useQuery } from 'react-query';
import API from '../../../../assets/js/back-end/utils/api';
import GoogleMeetUrls from '../../constants/urls';

interface Props {
	handleFileUpload: any;
}

const DNDJson: React.FC<Props> = ({ handleFileUpload }) => {
	const [fileDNDInProgress, setFileDNDInProgress] = useState<boolean>(false);
	const fileInputRef = useRef<HTMLInputElement>(null);
	const toast = useToast();

	const handleFileOpenClick = () => {
		if (fileInputRef.current) {
			fileInputRef.current.click();
		}
	};

	const handleFileChange = (e: React.ChangeEvent<HTMLInputElement>) => {
		e.preventDefault();
		if (e.target.files) {
			const selectedFile = e.target.files[0];

			if (selectedFile.type !== 'application/json') {
				return toast({
					title: __(
						'Selected file must only be JSON',
						'learning-management-system',
					),
					status: 'error',
					isClosable: true,
				});
			}

			// Might need an API call here
			handleFileUpload.mutateAsync(e.target.files[0]);

			setFileDNDInProgress(false);
		}
	};

	const handleDragStart = (e: React.DragEvent<HTMLDivElement>) => {
		e.dataTransfer.setData('text/plain', '');
	};

	const handleDragOver = (e: React.DragEvent<HTMLDivElement>) => {
		e.preventDefault();
		setFileDNDInProgress(true);
	};

	const handleDragLeave = (e: React.DragEvent<HTMLDivElement>) => {
		e.preventDefault();
		setFileDNDInProgress(false);
	};

	const handleDrop = (e: React.DragEvent<HTMLDivElement>) => {
		e.preventDefault();
		const selectedFile = e.dataTransfer.files[0];
		if (selectedFile.type !== 'application/json') {
			setFileDNDInProgress(false);
			return toast({
				title: __(
					'Selected file must only be JSON',
					'learning-management-system',
				),
				status: 'error',
				isClosable: true,
			});
		}

		// Might need an API call here
		handleFileUpload.mutateAsync(e.dataTransfer.files[0]);

		setFileDNDInProgress(false);
	};

	const GoogleMeetAPI = new API(GoogleMeetUrls.settings);

	const settingQuery = useQuery(
		['googleMeetSettings'],
		() => GoogleMeetAPI.list(),
		{
			keepPreviousData: true,
		},
	);

	return (
		<Container
			maxW="container.xl"
			width={'100%'}
			borderRadius={10}
			bg={'gray.50'}
			borderWidth={'medium'}
			borderStyle={'dashed'}
			padding={10}
			mt={{ base: 7, md: 0 }}
			onDragOver={handleDragOver}
			onDrop={handleDrop}
			onDragLeave={handleDragLeave}
			onDragStart={handleDragStart}
		>
			<Input
				display={'none'}
				ref={fileInputRef}
				type="file"
				onChange={handleFileChange}
			/>
			<Box
				style={{ transform: `scale(${fileDNDInProgress ? 1.1 : 1})` }}
				transition="transform 0.2s ease-in-out"
				onClick={handleFileOpenClick}
				display={'flex'}
				justifyContent={'center'}
				alignItems={'center'}
				flexDirection={'column'}
				cursor={'pointer'}
			>
				<Box bgColor={'primary.300'} padding={5} borderRadius={'50%'}>
					<LuFileJson size={50} color={'primary'} />
				</Box>
				<Text mt={4} fontSize={'large'} fontWeight={500}>
					{__(
						`Click / Drag & Drop Your JSON File Here`,
						'learning-management-system',
					)}
				</Text>
			</Box>
		</Container>
	);
};

export default DNDJson;
