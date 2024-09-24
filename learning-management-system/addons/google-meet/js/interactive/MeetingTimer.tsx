import {
	Center,
	chakra,
	CircularProgress,
	CircularProgressLabel,
	shouldForwardProp,
	Stack,
	Text,
} from '@chakra-ui/react';
import { __ } from '@wordpress/i18n';
import { isValidMotionProp, motion } from 'framer-motion';
import React, { useMemo } from 'react';
import { useTimer } from 'react-timer-hook';
import { prefixZero } from '../utils/time';

interface Props {
	duration: number;
	startAt: any;
	onTimeout?: () => void;
}

const MeetingTimer: React.FC<Props> = (props) => {
	const { startAt, onTimeout } = props;

	const expiryTimeInMs = useMemo(() => {
		return new Date(startAt).getTime();
	}, [startAt]);

	const circularProgressMax = useMemo(() => {
		return (expiryTimeInMs - new Date().getTime()) / 1000;
	}, [expiryTimeInMs]);

	const { hours, seconds, minutes, days } = useTimer({
		expiryTimestamp: new Date(expiryTimeInMs),
		onExpire: onTimeout,
	});

	const currentTimerInSeconds =
		days * 24 * 60 * 60 + hours * 60 * 60 + minutes * 60 + seconds;
	const isTimingOut = currentTimerInSeconds <= 30;

	const CircularBox = chakra(motion.div, {
		shouldForwardProp: (prop) =>
			isValidMotionProp(prop) || shouldForwardProp(prop),
	});

	return (
		<CircularBox
			animate={
				isTimingOut && {
					scale: [1, 1.1, 1, 1.5],
				}
			}
			// @ts-ignore
			transition={{
				duration: 3,
				ease: 'easeInOut',
				repeat: Infinity,
				repeatType: 'loop',
			}}
			position="fixed"
			right="40px"
			top="140px"
		>
			<Center bg="white" shadow="box" rounded="full" w="110px" h="110px">
				<CircularProgress
					value={currentTimerInSeconds}
					max={circularProgressMax}
					capIsRound
					color={isTimingOut ? 'red.500' : 'primary.500'}
					size="140px"
					trackColor="transparent"
					thickness="5px"
				>
					<CircularProgressLabel fontSize="lg">
						<Stack spacing="0">
							<Text fontSize="sm" color="gray.500">
								{__('Starts In', 'learning-management-system')}
							</Text>
							<Text fontSize="lg" fontWeight="bold" color="gray.700">
								{days > 0 ? `${prefixZero(days)}:` : null}
								{hours > 0 ? `${prefixZero(hours)}:` : null}
								{prefixZero(minutes)}:{prefixZero(seconds)}
							</Text>
						</Stack>
					</CircularProgressLabel>
				</CircularProgress>
			</Center>
		</CircularBox>
	);
};

export default MeetingTimer;
