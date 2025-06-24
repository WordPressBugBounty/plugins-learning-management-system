import registerBlocks from './blocks';
import './editor.scss';
import { initCSSGenerators } from './helpers/initCSSGenerators';
import { registerDeviceTypeStore } from './helpers/registerDeviceTypeStore';
import { updateBlocksCategoryIcon } from './helpers/updateBlocksCategoryIcon';
import './style.scss';

// Register the blocks
registerBlocks();
initCSSGenerators();
updateBlocksCategoryIcon();
registerDeviceTypeStore();
