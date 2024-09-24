<?php

/**
 * The template for displaying the course coming soon section in single course page.
 */

use Masteriyo\Enums\CourseProgressStatus;
?>
<div class="masteriyo-single-course--course-coming-soon" id="masteriyo_coming_soon">
	<div class="masteriyo-single-course--course-coming-soon-msg">
		<div class="masteriyo-single-course--course-coming-soon-timer">
			<span id="masteriyo_countdown"></span>
		</div>
		<?php
		$utc_ending_date = gmdate( 'F j, Y H:i:s', strtotime( $ending_date ) );
		if ( strtotime( $utc_ending_date ) > strtotime( gmdate( 'F j, Y H:i:s' ) ) ) :
			{
			if ( ! $hide_date_text ) :
				?>
					<div class="masteriyo-single-course--course-coming-soon-text">
						<span>
						<?php echo esc_html( apply_filters( 'masteriyo_course_coming_soon_message', esc_html_e( 'This course will be available on', 'learning-management-system' ) ) ); ?>
						</span>
						<span id="masteriyo_ending_date"></span>
					</div>
				<?php
				endif;
			};
		endif;
		?>
	</div>
</div>

<script>
	var countdownInterval;

	function calculateCountdown() {
		var endDate = new Date("<?php echo esc_html( $utc_ending_date ); ?> UTC");
		var check = <?php echo esc_html( $hide_date_text ? $hide_date_text : 0 ); ?>;
		var now = new Date();
		var nowUTC = new Date(now.toISOString().slice(0, -1) + 'Z');
		var difference = endDate - nowUTC;

		var oneHour = 1000 * 60 * 60;

		// var check_date = new Date("<?php echo esc_html( $utc_ending_date ); ?> UTC");
		// check_date.setMinutes(check_date.getMinutes() + 1);
		// var current_date = new Date();

		if (difference > 0 ) {
			var days = Math.floor(difference / (1000 * 60 * 60 * 24));
			var hours = Math.floor((difference % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
			var minutes = Math.floor((difference % (1000 * 60 * 60)) / (1000 * 60));
			var seconds = Math.floor((difference % (1000 * 60)) / 1000);

			var countdownText = `
			<div style="display: flex; flex-wrap: wrap; justify-content: center;">
				<div class="masteriyo-countdown-segment">
					<span> ${days < 10 ? '0' + days : days}</span><br>
					<span style="font-size: 13px;"><?php echo esc_html__( 'Days', 'learning-management-system' ); ?></span>
				</div>
				<div class="masteriyo-countdown-separator">
					<span>:</span>
				</div>
				<div class="masteriyo-countdown-segment">
					<span>${hours < 10 ? '0' + hours : hours}</span><br>
					<span style="font-size: 13px;"><?php echo esc_html__( 'Hours', 'learning-management-system' ); ?></span>
				</div>
				<div class="masteriyo-countdown-separator">
					<span>:</span>
				</div>
				<div class="masteriyo-countdown-segment">
					<span>${minutes < 10 ? '0' + minutes : minutes}</span><br>
					<span style="font-size: 13px;"><?php echo esc_html__( 'Minutes', 'learning-management-system' ); ?></span>
				</div>
				<div class="masteriyo-countdown-separator">
					<span>:</span>
				</div>
				<div class="masteriyo-countdown-segment">
					<span>${seconds < 10 ? '0' + seconds : seconds}</span><br>
					<span style="font-size: 13px;"><?php echo esc_html__( 'Seconds', 'learning-management-system' ); ?></span>
				</div>
			</div>`;

			document.getElementById("masteriyo_countdown").innerHTML = countdownText;
			if(!check){
			document.getElementById("masteriyo_ending_date").innerHTML = endDate.toLocaleString('en-US', { month: 'long', day: '2-digit', year: 'numeric' });
			}
		} else {
			document.getElementById("masteriyo_coming_soon").remove();
			clearInterval(countdownInterval);
		}
	}

	calculateCountdown();

	countdownInterval = setInterval(calculateCountdown, 1000);
</script>
