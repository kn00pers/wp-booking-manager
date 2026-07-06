<?php
defined( 'ABSPATH' ) || exit;

use CBM\Helpers\Labels;

$status_labels = Labels::statuses();

// Monday-first weekday abbreviations from the WP locale (keys 0-6 = Sun-Sat).
global $wp_locale;
$day_names = [];
for ( $i = 1; $i <= 7; $i++ ) {
	$weekday     = $wp_locale->get_weekday( $i % 7 );
	$day_names[] = $wp_locale->get_weekday_abbrev( $weekday );
}
$today = current_time( 'Y-m-d' );
?>
<div class="wrap cbm-cal-wrap">


	<div class="cbm-cal-header">
		<h1 class="cbm-cal-header__title"><?php esc_html_e( 'Booking calendar', 'crane-booking-manager' ); ?></h1>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-bookings' ) ); ?>" class="button"><?php esc_html_e( 'List', 'crane-booking-manager' ); ?></a>
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=cbm-add-booking' ) ); ?>" class="button button-primary">+ <?php esc_html_e( 'Add', 'crane-booking-manager' ); ?></a>
	</div>


	<div class="cbm-cal-nav">
		<a href="<?php echo esc_url( $page->prev_url() ); ?>" class="cbm-cal-nav__arrow" aria-label="<?php esc_attr_e( 'Previous month', 'crane-booking-manager' ); ?>">&#8592;</a>
		<span class="cbm-cal-nav__label"><?php echo esc_html( $page->month_label() ); ?></span>
		<a href="<?php echo esc_url( $page->next_url() ); ?>" class="cbm-cal-nav__arrow" aria-label="<?php esc_attr_e( 'Next month', 'crane-booking-manager' ); ?>">&#8594;</a>
	</div>


	<div class="cbm-cal-legend">
		<?php foreach ( $status_labels as $key => $lbl ) : ?>
			<span class="cbm-cal-legend__item cbm-cal-legend__item--<?php echo esc_attr( $key ); ?>">
				<span class="cbm-cal-legend__dot"></span><?php echo esc_html( $lbl ); ?>
			</span>
		<?php endforeach; ?>
	</div>


	<div class="cbm-cal-grid">


		<?php foreach ( $day_names as $dn ) : ?>
			<div class="cbm-cal-grid__head"><?php echo esc_html( $dn ); ?></div>
		<?php endforeach; ?>


		<?php
		$offset = $page->first_weekday - 1;
		for ( $i = 0; $i < $offset; $i++ ) :
		?>
			<div class="cbm-cal-grid__cell cbm-cal-grid__cell--filler"></div>
		<?php endfor; ?>


		<?php for ( $day = 1; $day <= $page->days_in_month; $day++ ) :
			$date     = sprintf( '%04d-%02d-%02d', $page->year, $page->month, $day );
			$bookings = $page->by_date[ $date ] ?? [];
			$is_today = ( $date === $today );
			$extra    = $is_today ? ' cbm-cal-grid__cell--today' : '';
			$count    = count( $bookings );
		?>
		<div class="cbm-cal-grid__cell<?php echo esc_attr( $extra ); ?>">

			<div class="cbm-cal-grid__day">
				<span class="cbm-cal-grid__day-num"><?php echo $day; ?></span>
				<?php if ( $count > 0 ) : ?>
					<span class="cbm-cal-grid__day-count"><?php
					/* translators: %d: number of bookings on the day */
					echo esc_html( sprintf( _n( '%d bkg.', '%d bkgs.', $count, 'crane-booking-manager' ), $count ) );
					?></span>
				<?php endif; ?>
			</div>

			<?php foreach ( $bookings as $b ) :
				$edit_url   = admin_url( 'admin.php?page=cbm-add-booking&booking_id=' . absint( $b->id ) );
				$gcal_url   = $page->google_calendar_url( $b );
				$status_key = esc_attr( $b->status );
				$time_range = esc_html( substr( $b->time_from, 0, 5 ) . '-' . substr( $b->time_to, 0, 5 ) );
				$name_short = esc_html( mb_strimwidth( $b->customer_name, 0, 18, '…' ) );
				$status_lbl = esc_html( $status_labels[ $b->status ] ?? $b->status );
			?>
			<div class="cbm-cal-booking cbm-cal-booking--<?php echo $status_key; ?>">
				<a href="<?php echo esc_url( $edit_url ); ?>" class="cbm-cal-booking__body" title="<?php echo esc_attr( $b->customer_name . ' · ' . strip_tags( $time_range ) ); ?>">
					<span class="cbm-cal-booking__name"><?php echo $name_short; ?></span>
					<span class="cbm-cal-booking__time"><?php echo $time_range; ?></span>
				</a>
				<a href="<?php echo esc_url( $gcal_url ); ?>"
				   target="_blank"
				   rel="noopener noreferrer"
				   class="cbm-cal-booking__gcal"
				   title="<?php esc_attr_e( 'Add to Google Calendar', 'crane-booking-manager' ); ?>">
					<svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="4" width="18" height="18" rx="2"/><line x1="16" y1="2" x2="16" y2="6"/><line x1="8" y1="2" x2="8" y2="6"/><line x1="3" y1="10" x2="21" y2="10"/></svg>
				</a>
			</div>
			<?php endforeach; ?>

		</div>
		<?php endfor; ?>


		<?php
		$total_cells = $offset + $page->days_in_month;
		$trailing    = ( 7 - ( $total_cells % 7 ) ) % 7;
		for ( $i = 0; $i < $trailing; $i++ ) :
		?>
			<div class="cbm-cal-grid__cell cbm-cal-grid__cell--filler"></div>
		<?php endfor; ?>

	</div>
</div>
