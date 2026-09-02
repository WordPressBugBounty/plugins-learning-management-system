import localized from '../../../../../assets/js/back-end/utils/global';
import { registerDeviceTypeStore } from '../../../../../assets/js/blocks/helpers/registerDeviceTypeStore';
import { updateBlocksCategoryIcon } from '../../../../../assets/js/blocks/helpers/updateBlocksCategoryIcon';
import { registerCertificateBlock } from './certificate/block';
import { registerCoInstructorsNameBlock } from './co-instructors-name/block';
import { registerCourseCompletionDateBlock } from './course-completion-date/block';
import { registerCourseDurationBlock } from './course-duration/block';
import { registerCourseGradeResultBlock } from './course-grade-result/block';
import { registerCourseStartDateBlock } from './course-start-date/block';
import { registerCourseTitleBlock } from './course-title/block';
import { registerCertificateVerificationCodeBlock } from './course-verification-code/block';
import { registerCurrentDateBlock } from './current-date/block';
import { registerCurrentTimeBlock } from './current-time/block';
import { registerCurrentTimestampBlock } from './current-timestamp/block';
import { registerInstructorNameBlock } from './instructor-name/block';
import { registerQrcode } from './qr-code/block';
import { registerStudentNameBlock } from './student-name/block';

updateBlocksCategoryIcon();
registerDeviceTypeStore();
registerCertificateBlock();
registerCourseTitleBlock();
registerStudentNameBlock();
registerQrcode();
registerCourseCompletionDateBlock();
registerCertificateVerificationCodeBlock();
registerCourseStartDateBlock();
if (localized?.allowedBlockTypes?.includes('masteriyo/course-grade-result')) {
	registerCourseGradeResultBlock();
}
registerInstructorNameBlock();
if (localized?.allowedBlockTypes?.includes('masteriyo/co-instructors-name')) {
	registerCoInstructorsNameBlock();
}
registerCourseDurationBlock();
registerCurrentDateBlock();
registerCurrentTimeBlock();
registerCurrentTimestampBlock();
