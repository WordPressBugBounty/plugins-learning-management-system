<?php
/**
 * The Template for displaying instructors list item.
 *
 * This template can be overridden by copying it to yourtheme/masteriyo/shortcodes/instructors-list/list-item.php
 *
 * HOWEVER, on occasion Masteriyo will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @package Masteriyo\Templates
 * @version 1.7.0
 */

defined('ABSPATH') || exit;
?>

<div class="masteriyo-col">
    <div class="masteriyo-instructor-card">
        <a href="<?php echo esc_url($instructor['url']); ?>" class="masteriyo-instructor-card__image-wrapper">
            <div class="masteriyo-instructor-card__image">
                <img src="<?php echo esc_url($instructor['profile_image_url']); ?>" 
                     alt="<?php echo esc_attr($instructor['full_name']); ?>" />
            </div>
        </a>
        <div class="masteriyo-instructor-card__detail">
            <h2 class="masteriyo-instructor-card__title">
                <?php echo esc_html($instructor['username']); ?>
            </h2>
            <div class="about">
                <div class="numbers books">
                    <?php masteriyo_get_svg('book-open', true); ?>
                    <p class="counter">
                        <?php echo esc_html($instructor['courses_count']); ?>
                    </p>
                </div>
                <div class="numbers users">
                    <?php masteriyo_get_svg('users', true); ?>
                    <p class="counter">
                        <?php echo esc_html($instructor['students_count']); ?>
                    </p>
                </div>
            </div>
        </div>
        <div class="info">
            <!-- 
            <div class="numbers rating">
                <?php masteriyo_get_svg('star', true); ?>
                <p>4.5 <?php esc_html_e('Instructor Rating', 'learning-management-system'); ?></p>
            </div> 
            -->
            <div class="numbers mail">
                <?php masteriyo_get_svg('mail', true); ?>
                <p><?php echo esc_html($instructor['email']); ?></p>
            </div>
            <div class="numbers date">
                <?php masteriyo_get_svg('calender', true); ?>
                <p><?php echo esc_html($instructor['date_created']); ?></p>
            </div>
        </div>
    </div>
</div>