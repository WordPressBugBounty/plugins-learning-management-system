import {
	Box,
	Button,
	ButtonGroup,
	Flex,
	Icon,
	Link,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __, sprintf } from '@wordpress/i18n';
import React from 'react';
import { UserCertificate } from '../../../../../assets/js/back-end/constants/images';
import {
	getWordpressLocalTime,
	isEmpty,
} from '../../../../../assets/js/back-end/utils/utils';

interface Props {
	data: UserCertificate;
}

const UserCertificateItem: React.FC<Props> = (props) => {
	const { data } = props;
	const { course } = data;
	const courseTitle = course?.name;

	return (
		<Box
			p="6"
			borderWidth="1px"
			borderColor="icy-blue-gray"
			bg="white"
			className="mto-continue-course"
			w={'100%'}
			rounded={'xl'}
			role={'group'}
			_hover={{ bg: 'off-white' }}
		>
			<Stack
				direction={{ base: 'column', sm: 'column', md: 'row', lg: 'row' }}
				spacing="4"
				flex={1}
			>
				<Flex
					p={4}
					justifyContent={'center'}
					alignItems={'center'}
					bgColor={'off-white'}
					width={'50px'}
					height={'50px'}
					rounded={'lg'}
				>
					<Icon
						as={UserCertificate}
						color="primary.500"
						fontSize="2xl"
						fill={'currentColor'}
					/>
				</Flex>
				<Stack direction="column" gap={2.5} w="100%">
					<Link
						href={course?.permalink}
						sx={{
							textDecoration: 'none !important',
						}}
						color={'oxford-night'}
						fontSize={'md'}
						fontWeight={'semibold'}
						_groupHover={{ color: 'primary.500' }}
					>
						{courseTitle}
					</Link>

					{course.started_at && (
						<Text color="saint-blue" fontSize="sm" fontWeight={'medium'}>
							{sprintf(
								/* translators: %s: course start date */
								__('Started on %s', 'learning-management-system'),
								getWordpressLocalTime(course.started_at, 'm/d/Y'),
							)}
						</Text>
					)}
				</Stack>

				<ButtonGroup gap={3}>
					<Link
						href={data?.view_url}
						style={{ width: 'fit-content' }}
						mx={'auto'}
						isExternal
					>
						<Button
							title={
								isEmpty(data?.view_url)
									? __(
											'Certificate may not exist.',
											'learning-management-system',
										)
									: ''
							}
							isDisabled={isEmpty(data?.view_url)}
							variant="outline"
							colorScheme="button"
						>
							{__('Preview', 'learning-management-system')}
						</Button>
					</Link>
					<Link
						href={data?.download_url}
						isExternal
						style={{ width: 'fit-content' }}
						mx={'auto'}
					>
						<Button colorScheme="button">
							{__('Download Certificate', 'learning-management-system')}
						</Button>
					</Link>
				</ButtonGroup>
			</Stack>
		</Box>
	);
};

export default UserCertificateItem;
