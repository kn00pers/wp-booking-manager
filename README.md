# Resource Booking Manager (WordPress Plugin)

Resource Booking Manager is a WordPress plugin for booking any type of resource (for example: machines, rooms, vehicles, equipment). It provides a frontend booking form with live availability, a full admin workflow, email notifications, cancellation links, and resource-specific schedule management.

Current plugin version: **2.0.0**  
Text domain: **`crane-booking-manager`**

## Features

- Multi-resource booking (separate availability per resource).
- Frontend calendar with per-day status (`available`, `partial`, `fully_booked`, `disabled`, `past`).
- Time-slot booking with conflict detection and a minimum duration of **3 hours**.
- Configurable working hours per weekday.
- Optional rule: pending bookings can temporarily block slots.
- Optional minimum advance notice (hours).
- Admin panel with:
  - booking list (filters, sorting, soft delete/restore),
  - monthly calendar view,
  - manual add/edit booking form,
  - unavailability blocks (full-day or time-range),
  - resource management,
  - settings and email templates.
- Customer cancellation via secure tokenized link.
- Notification emails for booking lifecycle events.
- Daily reminder emails for approved bookings (optional).
- CSV export from admin list.
- Optional anti-spam protection with Cloudflare Turnstile.
- Localization support (includes Polish translation files).

## Requirements

- WordPress **6.0+**
- PHP **8.1+**

## Installation

1. Copy this project to:
   `wp-content/plugins/crane-booking-manager`
2. Activate the plugin in **WordPress Admin → Plugins**.
3. On activation, the plugin:
   - creates DB tables,
   - runs schema migrator,
   - creates default resource (`Resource #1`) when none exists,
   - grants `manage_cbm_bookings` capability to administrators,
   - creates role `cbm_operator`,
   - creates secret key (`cbm_secret`) for cancellation tokens.

## Quick Start

1. Go to **Bookings → Settings** and configure:
   - resource labels (singular/plural),
   - working hours,
   - slot duration (30 or 60 minutes),
   - minimum advance notice,
   - reminder and email settings,
   - optional Turnstile keys.
2. Go to **Bookings → Resources** and define your real resources.
3. Insert shortcode on a page:
   - `[crane_booking_form]`
   - `[resource_booking_form]`
   - Optional attribute: `resource_id`, for example:
     `[crane_booking_form resource_id="2"]`

## Frontend Booking Flow

1. User selects a day in the inline calendar.
2. Plugin loads available start times for that day/resource.
3. User picks start time; plugin computes valid end times with:
   - minimum 3-hour duration,
   - working-hours boundary,
   - blocked/busy ranges.
4. User submits contact and booking data.
5. Booking is created with status `pending`.

Validation and protection:

- Field validation (date/time format, required fields, email correctness).
- Nonce validation on booking API.
- Rate limiting: **5 requests / 10 minutes** per IP.
- Duplicate-submit protection via short-lived `submit_token`.
- Optional Turnstile server-side verification.

## Admin Area

Main menu: **Bookings** (requires `manage_cbm_bookings`)

Subpages:

- **Booking list**: filter by status/date/search, include deleted, approve/reject/delete/restore, CSV export.
- **Calendar**: monthly view with booking cards and Google Calendar quick link.
- **Add booking / Edit booking**: full booking form with status and change history.
- **Blocks**: manage unavailability ranges for resources.
- **Resources**: create/edit resources and active state.
- **Settings**: global behavior, mail templates, anti-spam, naming.

## Booking Status Lifecycle

Supported statuses:

- `pending`
- `approved`
- `rejected`
- `cancelled`

Soft deletion is separate (`deleted_at`) and can be restored.

## REST API

Namespace: **`cbm/v1`**

| Endpoint | Method | Auth | Description |
|---|---|---|---|
| `/availability` | GET | Public | Returns available/blocked slots and busy ranges for date + resource |
| `/blocked-dates` | GET | Public | Returns blocked dates for month + resource |
| `/month-status` | GET | Public | Returns status per day for month + resource |
| `/bookings` | POST | `X-WP-Nonce` required | Creates booking (with validation and anti-spam checks) |

## AJAX Actions (Admin)

- `cbm_approve_booking`
- `cbm_reject_booking`
- `cbm_delete_booking`
- `cbm_restore_booking`
- `cbm_export_csv`
- `cbm_save_unavailability`
- `cbm_delete_unavailability`
- `cbm_admin_save_booking`

## Emails

Built-in template groups:

- New booking → Admin
- New booking → Customer (pending confirmation)
- Approved → Customer
- Rejected → Customer
- Booking updated → Customer
- Cancelled by customer → Customer
- Cancelled by customer → Admin
- Reminder (day before) → Customer

Supported placeholders:

`{booking_id}`, `{customer_name}`, `{customer_phone}`, `{customer_email}`, `{customer_company}`, `{location}`, `{date}`, `{time_from}`, `{time_to}`, `{notes}`, `{resource_name}`, `{status}`, `{cancel_url}`, `{site_name}`, `{admin_url}`

On approval, customer email includes an **ICS calendar file** attachment.

## Scheduled Task

- Hook: `cbm_send_reminders`
- Frequency: daily
- First run planned for tomorrow at 08:00 (site timezone)
- Sends reminders for approved bookings scheduled for the next day

## Database Tables

The plugin creates:

- `{prefix}cbm_resources`
- `{prefix}cbm_bookings`
- `{prefix}cbm_unavailability`
- `{prefix}cbm_booking_logs`
- `{prefix}cbm_schema_version`

## Actions for Developers

The plugin fires these WordPress actions:

- `cbm_booking_created`
- `cbm_booking_approved`
- `cbm_booking_rejected`
- `cbm_booking_cancelled`
- `cbm_booking_deleted`
- `cbm_booking_updated`

## Uninstall Behavior

`uninstall.php` removes all plugin tables/options/capabilities **only** when:

```php
define( 'CBM_UNINSTALL_DATA', true );
```

If this constant is not set to `true`, uninstall keeps data.

## Project Structure

```text
assets/        Admin/frontend JS and CSS
languages/     Translation files
src/
  Admin/       WP admin pages
  Database/    Schema + migrator
  Frontend/    Shortcode, renderer, cancellation flow
  Helpers/     Date/time, labels, secure token
  Http/        REST and AJAX controllers
  Repository/  Data access layer
  Service/     Booking, availability, mail, rate limiter
  Validator/   Booking validation rules
views/         PHP templates (admin/frontend)
```
