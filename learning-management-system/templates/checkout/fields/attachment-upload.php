<?php

/**
 * The Template for displaying checkout form field.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/checkout/fields/attachment-upload.php.
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.12.2
 */

?>
<div class="masteriyo-checkout---attachment-upload-wrapper">
	<div class="masteriyo-checkout----attachment-upload">
		<label for="masteriyo-attachment-upload" class="masteriyo-label">
			<?php esc_html_e( 'Upload Attachment', 'learning-management-system' ); ?>
		</label>
		<input type="file" id="masteriyo-checkout-attachment-upload" class="masteriyo-input" name="attachment_upload" />
		<div id="masteriyo-checkout-attachment-uploaded-file-info" style="display: none;background: #f4f4f4;padding: 10px 16px;border-radius: 5px;width: max-content;">
			<span id="masteriyo-checkout-attachment-upload-file-name"></span>
			<div type="button" id="masteriyo-checkout-attachment-uploaded-delete-file" class="masteriyo-checkout-attachment-uploaded-file-info" style="display:flex;width:15px;cursor:pointer;">
				<svg fill="red" viewBox="0 0 24 24">
					<path fill-rule="evenodd" d="M9 4c0-.175.097-.433.332-.668C9.567 3.097 9.825 3 10 3h4c.175 0 .433.097.668.332.234.235.332.493.332.668v1H9V4ZM7 5V4c0-.825.403-1.567.918-2.082C8.433 1.403 9.175 1 10 1h4c.825 0 1.567.403 2.082.918C16.597 2.433 17 3.175 17 4v1h4a1 1 0 1 1 0 2h-1v13c0 .825-.402 1.567-.918 2.082-.515.515-1.257.918-2.082.918H7c-.825 0-1.567-.402-2.082-.918C4.403 21.567 4 20.825 4 20V7H3a1 1 0 0 1 0-2h4ZM6 7v13c0 .175.097.433.332.668.235.235.493.332.668.332h10c.175 0 .433-.098.668-.332.235-.235.332-.493.332-.668V7H6Zm4.707 3.293A1 1 0 0 0 9 11v6a1 1 0 1 0 2 0v-6a1 1 0 0 0-.293-.707Zm4 0A1 1 0 0 0 13 11v6a1 1 0 0 0 2 0v-6a1 1 0 0 0-.293-.707Z" clip-rule="evenodd" />
				</svg>
			</div>
		</div>
	</div>
</div>
<?php
