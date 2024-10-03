import {
	Box,
	Collapse,
	FormLabel,
	Icon,
	Skeleton,
	Stack,
	Switch,
	Tooltip,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import React from 'react';
import { Controller, useFormContext, useWatch } from 'react-hook-form';
import { BiInfoCircle } from 'react-icons/bi';
import { useQuery } from 'react-query';
import AsyncSelect from '../../../../../assets/js/back-end/components/common/AsyncSelect';
import FormControlTwoCol from '../../../../../assets/js/back-end/components/common/FormControlTwoCol';
import {
	infoIconStyles,
	reactSelectStyles,
} from '../../../../../assets/js/back-end/config/styles';
import { CourseDataMap } from '../../../../../assets/js/back-end/types/course';
import { isEmpty } from '../../../../../assets/js/back-end/utils/utils';
import { getAllCertificates } from '../utils/certificates';
import { CertificateStatus } from '../utils/enums';

interface Props {
	courseData?: CourseDataMap;
}

const CertificateCourseSettings: React.FC<Props> = (props) => {
	const { courseData } = props;
	const { register, control } = useFormContext();

	const isCertificateEnabled = useWatch({
		name: 'certificate_enabled',
		defaultValue: courseData?.certificate?.enabled,
		control,
	});

	const certificatesQuery = useQuery(
		'certificatesList',
		() =>
			getAllCertificates({
				order: 'desc',
				orderby: 'date',
				status: CertificateStatus.Publish,
				per_page: 10,
			}),
		{
			enabled: isCertificateEnabled,
		},
	);

	return (
		<Stack direction="column" spacing={8}>
			<FormControlTwoCol>
				<Stack direction="row">
					<FormLabel minW="160px">
						{__('Enable Certificate', 'learning-management-system')}
						<Tooltip
							label={__(
								'Allow students to get certificate after course completion.',
								'learning-management-system',
							)}
							hasArrow
							fontSize="xs"
						>
							<Box as="span" sx={infoIconStyles}>
								<Icon as={BiInfoCircle} />
							</Box>
						</Tooltip>
					</FormLabel>
					<Switch
						{...register('certificate_enabled')}
						defaultChecked={courseData?.certificate?.enabled}
					/>
				</Stack>
			</FormControlTwoCol>

			{isCertificateEnabled && certificatesQuery.isLoading ? (
				<Skeleton height="40px" />
			) : null}
			{isCertificateEnabled && certificatesQuery.isSuccess ? (
				<Collapse in={isCertificateEnabled}>
					<Stack direction={'column'} gap={8}>
						<FormControlTwoCol>
							<FormLabel minW="160px" mb={0}>
								{__('Certificate', 'learning-management-system')}
								<Tooltip
									label={__(
										'Select which certificate to use for this course.',
										'learning-management-system',
									)}
									hasArrow
									fontSize="xs"
								>
									<Box as="span" sx={infoIconStyles}>
										<Icon as={BiInfoCircle} />
									</Box>
								</Tooltip>
							</FormLabel>
							<Controller
								name="certificate_id"
								control={control}
								defaultValue={
									courseData?.certificate?.id
										? {
												value: courseData.certificate.id,
												label: courseData.certificate.name,
											}
										: undefined
								}
								render={({ field: { onChange, value } }) => (
									<AsyncSelect
										styles={{
											...reactSelectStyles,
										}}
										cacheOptions={true}
										loadingMessage={() =>
											__('Searching...', 'learning-management-system')
										}
										noOptionsMessage={({ inputValue }) =>
											!isEmpty(inputValue)
												? __(
														'Certificate not found.',
														'learning-management-system',
													)
												: __(
														'Please enter one or more characters.',
														'learning-management-system',
													)
										}
										isClearable={true}
										placeholder={__(
											'Search certificate...',
											'learning-management-system',
										)}
										value={value}
										onChange={onChange}
										defaultOptions={
											certificatesQuery.isSuccess
												? certificatesQuery.data?.data?.map((certificate) => ({
														value: certificate.id,
														label: certificate.name,
													}))
												: []
										}
										loadOptions={(searchValue, callback) => {
											if (isEmpty(searchValue)) {
												return callback([]);
											}
											getAllCertificates({
												search: searchValue,
												order: 'desc',
												orderby: 'date',
												status: CertificateStatus.Publish,
												per_page: -1,
											}).then((data) => {
												callback(
													data.data.map((certificate) => ({
														value: certificate.id,
														label: certificate.name,
													})),
												);
											});
										}}
									/>
								)}
							/>
						</FormControlTwoCol>

						<FormControlTwoCol>
							<Stack direction="row">
								<FormLabel minW="160px">
									{__('Share Certificate', 'learning-management-system')}
									<Tooltip
										label={__(
											'Allow students to view/share certificate from single course page after course completion.',
											'learning-management-system',
										)}
										hasArrow
										fontSize="xs"
									>
										<Box as="span" sx={infoIconStyles}>
											<Icon as={BiInfoCircle} />
										</Box>
									</Tooltip>
								</FormLabel>
								<Switch
									{...register('certificate_single_course_enabled')}
									defaultChecked={
										courseData?.certificate?.single_course_enabled
									}
								/>
							</Stack>
						</FormControlTwoCol>
					</Stack>
				</Collapse>
			) : null}
			{isCertificateEnabled && certificatesQuery.isLoading ? (
				<Skeleton height="40px" />
			) : null}
		</Stack>
	);
};

export default CertificateCourseSettings;
