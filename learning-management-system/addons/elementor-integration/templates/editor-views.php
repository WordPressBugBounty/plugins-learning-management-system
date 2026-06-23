<?php
/**
 * Views for the Elementor editor.
 *
 * @since 1.6.12
 * @version 1.0.0
 */

defined( 'ABSPATH' ) || exit; // Exit if accessed directly.

?>
<?php // Shared styles for both the single-course and archive layout pickers (printed once). ?>
<style id="masteriyo-layout-picker-styles">
	#masteriyo-layout-picker-inner {
		display: flex;
		gap: 28px;
		padding: 36px 40px;
		justify-content: center;
		flex-wrap: wrap;
		align-items: flex-start;
	}
	.masteriyo-layout-card {
		width: 300px;
		border: 2px solid #e0e0e0;
		border-radius: 10px;
		overflow: hidden;
		cursor: pointer;
		transition: border-color 0.2s, box-shadow 0.2s, transform 0.15s;
		background: #fff;
		box-shadow: 0 2px 8px rgba(0,0,0,0.06);
	}
	.masteriyo-layout-card:hover {
		border-color: #93003f;
		box-shadow: 0 6px 20px rgba(147,0,63,0.14);
		transform: translateY(-2px);
	}
	.masteriyo-layout-card.is-selected {
		border-color: #93003f;
		box-shadow: 0 0 0 4px rgba(147,0,63,0.18);
	}
	.masteriyo-layout-card__image-wrap {
		width: 100%;
		height: 240px;
		overflow: hidden;
		background: #f5f5f5;
	}
	.masteriyo-layout-card__image-wrap img {
		width: 100%;
		height: 100%;
		object-fit: cover;
		object-position: top;
		display: block;
	}
	.masteriyo-layout-card__footer {
		display: flex;
		align-items: center;
		justify-content: space-between;
		padding: 14px 16px;
		border-top: 1px solid #f0f0f0;
	}
	.masteriyo-layout-card__label {
		font-size: 14px;
		font-weight: 600;
		color: #222;
	}
	.masteriyo-layout-use-btn {
		font-size: 13px;
		padding: 7px 18px;
		cursor: pointer;
	}
	.masteriyo-layout-card__image-wrap--custom {
		display: flex;
		flex-direction: column;
		align-items: center;
		justify-content: center;
		background: #f9f9f9;
	}
	.masteriyo-layout-card--custom:hover .masteriyo-layout-card__image-wrap--custom svg {
		stroke: #93003f;
	}
	.masteriyo-layout-card--custom:hover .masteriyo-layout-card__image-wrap--custom p {
		color: #93003f;
	}
</style>
<script type="text/template" id="tmpl-masteriyo-layout-picker">
	<div id="masteriyo-layout-picker-inner">
		<div class="masteriyo-layout-card" data-layout="default">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/single-course-default-layout.png' ); ?>" alt="Simple" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Simple', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="default">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card" data-layout="layout1">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/single-course-layout1-layout.png' ); ?>" alt="Modern" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Modern', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="layout1">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card" data-layout="minimal">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/single-course-minimal-layout.png' ); ?>" alt="Minimal" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Minimal', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="minimal">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card masteriyo-layout-card--custom" data-layout="custom">
			<div class="masteriyo-layout-card__image-wrap masteriyo-layout-card__image-wrap--custom">
				<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
				<p style="margin:12px 0 0;color:#aaa;font-size:13px;"><?php esc_html_e( 'Start from scratch', 'learning-management-system' ); ?></p>
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Custom', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="custom">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-masteriyo-single-course-page-preview">
	<img src="<?php echo esc_url( plugin_dir_url( MASTERIYO_PLUGIN_FILE ) . 'addons/elementor-integration/img/single-course-page-preview.jpg' ); ?>" width="100%">
</script>

<script type="text/template" id="tmpl-masteriyo-course-archive-page-preview">
	<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/addons/elementor-integration/img/course-archive-page-preview.jpg' ); ?>" width="100%">
</script>

<script type="text/template" id="tmpl-masteriyo-archive-layout-picker">
	<div id="masteriyo-layout-picker-inner">
		<div class="masteriyo-layout-card" data-layout="default">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/course-default-layout.png' ); ?>" alt="<?php esc_attr_e( 'Default', 'learning-management-system' ); ?>" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Default', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="default">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card" data-layout="layout1">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/course-layout1-layout.png' ); ?>" alt="<?php esc_attr_e( 'Modern', 'learning-management-system' ); ?>" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Modern', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="layout1">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card" data-layout="layout2">
			<div class="masteriyo-layout-card__image-wrap">
				<img src="<?php echo esc_url( masteriyo_get_plugin_url() . '/assets/img/course-layout2-layout.png' ); ?>" alt="<?php esc_attr_e( 'Overlay', 'learning-management-system' ); ?>" />
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Overlay', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="layout2">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
		<div class="masteriyo-layout-card masteriyo-layout-card--custom" data-layout="custom">
			<div class="masteriyo-layout-card__image-wrap masteriyo-layout-card__image-wrap--custom">
				<svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#aaa" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="18" height="18" rx="2"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>
				<p style="margin:12px 0 0;color:#aaa;font-size:13px;"><?php esc_html_e( 'Start from scratch', 'learning-management-system' ); ?></p>
			</div>
			<div class="masteriyo-layout-card__footer">
				<span class="masteriyo-layout-card__label"><?php esc_html_e( 'Custom', 'learning-management-system' ); ?></span>
				<button class="masteriyo-layout-use-btn elementor-button e-primary" data-layout="custom">
					<?php esc_html_e( 'Use', 'learning-management-system' ); ?>
				</button>
			</div>
		</div>
	</div>
</script>

<script type="text/template" id="tmpl-masteriyo-template-library-header-actions">
	<a id="masteriyo-template-library-header-import" class="elementor-template-library-template-action elementor-button e-primary">
		<i class="eicon-file-download" aria-hidden="true"></i>
		<span class="elementor-button-title"><?php echo esc_html__( 'Import', 'learning-management-system' ); ?></span>
	</a>
</script>

<script type="text/template" id="tmpl-masteriyo-templates-modal__header__logo">
	<span class="elementor-templates-modal__header__logo__icon-wrapper">
		<div class="masteriyo-templates-button" tab-index="0">
			<?php masteriyo_get_svg( 'logo', true ); ?>
		</div>
		<style>
			.elementor-templates-modal__header__logo__icon-wrapper svg {
				width: 18px;
				height: 18px;
			}
		</style>
	</span>
	<span class="elementor-templates-modal__header__logo__title">{{{ title }}}</span>
</script>
<?php
