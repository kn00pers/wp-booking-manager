<?php
defined( 'ABSPATH' ) || exit;

?>
<div class="cbm-booking-wrapper" id="cbm-booking-wrapper" data-resource-id="<?php echo absint( $resource_id ); ?>">

	<div class="cbm-calendar-block">
		<div id="cbm-calendar-inline" class="cbm-calendar-inline">
			<input type="text" id="cbm-booking-date" name="booking_date" autocomplete="off" readonly hidden>
		</div>
		<div class="cbm-calendar-legend" aria-hidden="true">
			<span class="cbm-calendar-legend__item">
				<span class="cbm-calendar-legend__dot cbm-calendar-legend__dot--available"></span><?php esc_html_e( 'Available', 'crane-booking-manager' ); ?>
			</span>
			<span class="cbm-calendar-legend__item">
				<span class="cbm-calendar-legend__dot cbm-calendar-legend__dot--partial"></span><?php esc_html_e( 'Partially booked', 'crane-booking-manager' ); ?>
			</span>
			<span class="cbm-calendar-legend__item">
				<span class="cbm-calendar-legend__dot cbm-calendar-legend__dot--booked"></span><?php esc_html_e( 'Unavailable', 'crane-booking-manager' ); ?>
			</span>
		</div>
	</div>

	<div id="cbm-modal-overlay" class="cbm-modal-overlay" style="display:none;">
		<div id="cbm-modal" class="cbm-modal-box" role="dialog" aria-modal="true" aria-labelledby="cbm-modal-date">
			<button type="button" class="cbm-modal-box__close" id="cbm-modal-close" aria-label="<?php esc_attr_e( 'Close', 'crane-booking-manager' ); ?>">&times;</button>
			<h2 class="cbm-modal-box__title" id="cbm-modal-date"><?php esc_html_e( 'Booking', 'crane-booking-manager' ); ?></h2>

			<div id="cbm-form-message" class="cbm-message" style="display:none;" aria-live="polite"></div>

			<form id="cbm-booking-form" class="cbm-form" novalidate>
				<input type="hidden" name="resource_id" value="<?php echo absint( $resource_id ); ?>">
				<input type="hidden" name="submit_token" id="cbm-submit-token" value="">

				<div class="cbm-form__section">
					<h3 class="cbm-form__section-title"><?php esc_html_e( 'Contact details', 'crane-booking-manager' ); ?></h3>

					<div class="cbm-field">
						<label for="cbm-customer-name"><?php esc_html_e( 'Full name / Company name', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<input type="text" id="cbm-customer-name" name="customer_name" autocomplete="name" required>
						<span class="cbm-field__error" id="cbm-error-customer_name"></span>
					</div>

					<div class="cbm-field">
						<label for="cbm-customer-phone"><?php esc_html_e( 'Phone', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<input type="tel" id="cbm-customer-phone" name="customer_phone" autocomplete="tel" required>
						<span class="cbm-field__error" id="cbm-error-customer_phone"></span>
					</div>

					<div class="cbm-field">
						<label for="cbm-customer-email"><?php esc_html_e( 'E-mail address', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<input type="email" id="cbm-customer-email" name="customer_email" autocomplete="email" required>
						<span class="cbm-field__error" id="cbm-error-customer_email"></span>
					</div>

					<div class="cbm-field">
						<label for="cbm-customer-company"><?php esc_html_e( 'Tax ID', 'crane-booking-manager' ); ?></label>
						<input type="text" id="cbm-customer-company" name="customer_company" autocomplete="organization">
					</div>
				</div>

				<div class="cbm-form__section">
					<h3 class="cbm-form__section-title"><?php esc_html_e( 'Booking details', 'crane-booking-manager' ); ?></h3>

					<div class="cbm-field">
						<label for="cbm-location"><?php esc_html_e( 'Location', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<textarea id="cbm-location" name="location" rows="2" required></textarea>
						<span class="cbm-field__error" id="cbm-error-location"></span>
					</div>

					<div class="cbm-field" id="cbm-slots-wrapper" style="display:none;">
						<label for="cbm-time-from"><?php esc_html_e( 'Time from', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<select id="cbm-time-from" name="time_from" required disabled>
							<option value=""><?php esc_html_e( 'Loading...', 'crane-booking-manager' ); ?></option>
						</select>
						<span class="cbm-field__error" id="cbm-error-time_from"></span>
					</div>

					<div class="cbm-field" id="cbm-time-to-wrapper" style="display:none;">
						<label for="cbm-time-to"><?php esc_html_e( 'Time to', 'crane-booking-manager' ); ?> <span aria-hidden="true">*</span></label>
						<select id="cbm-time-to" name="time_to" required disabled>
							<option value=""><?php esc_html_e( '- Select start time -', 'crane-booking-manager' ); ?></option>
						</select>
						<span class="cbm-field__error" id="cbm-error-time_to"></span>
					</div>

					<div class="cbm-field">
						<label for="cbm-notes"><?php esc_html_e( 'Notes / additional information', 'crane-booking-manager' ); ?></label>
						<textarea id="cbm-notes" name="notes" rows="3"></textarea>
					</div>
				</div>

				<?php
				$turnstile_site_key = \CBM\Admin\SettingsPage::get( 'turnstile_site_key' );
				if ( ! empty( $turnstile_site_key ) ) :
				?>
				<div class="cbm-form__section cbm-form__submit" style="margin-bottom: 15px;">
					<div class="cf-turnstile" data-sitekey="<?php echo esc_attr( $turnstile_site_key ); ?>"></div>
				</div>
				<script src="https://challenges.cloudflare.com/turnstile/v0/api.js" async defer></script>
				<?php endif; ?>

				<div class="cbm-form__submit">
					<button type="submit" id="cbm-submit" class="cbm-btn cbm-btn--primary">
						<?php esc_html_e( 'Send request', 'crane-booking-manager' ); ?>
					</button>
				</div>
			</form>

			<div id="cbm-success-message" class="cbm-success" style="display:none;" role="alert">
				<p id="cbm-success-text"></p>
			</div>
		</div>
	</div>

</div>
