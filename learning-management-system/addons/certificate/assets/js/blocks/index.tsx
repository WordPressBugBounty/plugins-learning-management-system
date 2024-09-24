import { registerDeviceTypeStore } from '../../../../../assets/js/blocks/helpers/registerDeviceTypeStore';
import { updateBlocksCategoryIcon } from '../../../../../assets/js/blocks/helpers/updateBlocksCategoryIcon';
import { registerCertificateBlock } from './certificate/block';
import { registerCourseCompletionDateBlock } from './course-completion-date/block';
import { registerCourseTitleBlock } from './course-title/block';
import { registerStudentNameBlock } from './student-name/block';

updateBlocksCategoryIcon();
registerDeviceTypeStore();
registerCertificateBlock();
registerCourseTitleBlock();
registerStudentNameBlock();
registerCourseCompletionDateBlock();
