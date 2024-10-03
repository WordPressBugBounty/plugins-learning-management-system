import {
	Box,
	chakra,
	HStack,
	shouldForwardProp,
	Text,
	useColorMode,
	VStack,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { isValidMotionProp, motion } from 'framer-motion';
import React, { useMemo } from 'react';
import { useTimer } from 'react-timer-hook';
import { COLORS_BASED_ON_SCREEN_COLOR_MODE } from '../../../../assets/js/interactive/constants/general';
import { useCourseContext } from '../../../../assets/js/interactive/context/CourseContext';

interface Props {
	duration: number;
	startAt: any;
	onTimeout?: () => void;
}

const MeetingTimer: React.FC<Props> = (props) => {
	const { startAt, onTimeout } = props;
	const { colorMode } = useColorMode();
	const { isHeaderOpen } = useCourseContext();

	const expiryTimeInMs = useMemo(() => {
		return new Date(startAt).getTime();
	}, [startAt]);

	const { hours, seconds, minutes, days } = useTimer({
		expiryTimestamp: new Date(expiryTimeInMs),
		onExpire: onTimeout,
	});

	const LinearBox = chakra(motion.div, {
		shouldForwardProp: (prop) =>
			isValidMotionProp(prop) || shouldForwardProp(prop),
	});

	const timeBoxStyles = {
		bg: 'primary.500',
		color: 'white',
		fontSize: { sm: 'lg', md: 'xl' },
		fontWeight: 'semibold',
		p: 1,
		borderRadius: 'sm',
		textAlign: 'center' as 'center',
		width: '1.5rem',
	};

	return (
		<LinearBox
			boxShadow={'xl'}
			width="100%"
			pt={2}
			pb={4}
			my={3}
			position="sticky"
			top={isHeaderOpen ? '101px' : { base: 12, md: 8 }}
			zIndex={10}
			bgColor={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.timerBgColor}
		>
			<VStack spacing="4" w="100%">
				<HStack spacing={{ base: '2', md: '6' }} alignItems="center">
					{days > 0 && (
						<>
							<VStack>
								<HStack spacing={1}>
									<Box {...timeBoxStyles}>
										{days.toString().padStart(2, '0')[0]}
									</Box>
									<Box {...timeBoxStyles}>
										{days.toString().padStart(2, '0')[1]}
									</Box>
								</HStack>
								<Text
									fontSize={{ base: 'x-small', md: 'sm' }}
									fontWeight={'semibold'}
									color={
										COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.timerText
									}
								>
									{__('Days', 'learning-management-system')}
								</Text>
							</VStack>
							<Text
								fontSize={{ sm: 'xl', md: '2xl' }}
								fontWeight="bold"
								color={'primary.500'}
								mb="5"
							>
								:
							</Text>
						</>
					)}
					<VStack>
						<HStack spacing={1}>
							<Box {...timeBoxStyles}>
								{hours.toString().padStart(2, '0')[0]}
							</Box>
							<Box {...timeBoxStyles}>
								{hours.toString().padStart(2, '0')[1]}
							</Box>
						</HStack>
						<Text
							fontSize={{ base: 'x-small', md: 'sm' }}
							fontWeight={'semibold'}
							color={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.timerText}
						>
							{__('Hours', 'learning-management-system')}
						</Text>
					</VStack>
					<Text
						fontSize={{ sm: 'xl', md: '2xl' }}
						fontWeight="bold"
						color={'primary.500'}
						mb="5"
					>
						:
					</Text>
					<VStack>
						<HStack spacing={1}>
							<Box {...timeBoxStyles}>
								{minutes.toString().padStart(2, '0')[0]}
							</Box>
							<Box {...timeBoxStyles}>
								{minutes.toString().padStart(2, '0')[1]}
							</Box>
						</HStack>
						<Text
							fontSize={{ base: 'x-small', md: 'sm' }}
							fontWeight={'semibold'}
							color={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.timerText}
						>
							{__('Minutes', 'learning-management-system')}
						</Text>
					</VStack>
					<Text
						fontSize={{ sm: 'xl', md: '2xl' }}
						fontWeight="bold"
						color={'primary.500'}
						mb="5"
					>
						:
					</Text>
					<VStack>
						<HStack spacing={1}>
							<Box {...timeBoxStyles}>
								{seconds.toString().padStart(2, '0')[0]}
							</Box>
							<Box {...timeBoxStyles}>
								{seconds.toString().padStart(2, '0')[1]}
							</Box>
						</HStack>
						<Text
							fontSize={{ base: 'x-small', md: 'sm' }}
							fontWeight={'semibold'}
							color={COLORS_BASED_ON_SCREEN_COLOR_MODE[colorMode]?.timerText}
						>
							{__('Seconds', 'learning-management-system')}
						</Text>
					</VStack>
				</HStack>
			</VStack>
		</LinearBox>
	);
};

export default MeetingTimer;
