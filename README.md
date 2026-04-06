# WP Lead Manager Pro


## Quick Start

1. Copy to `wp-content/plugins/wordpress-custom-plugin/`
2. Activate in WordPress admin
3. Configure in Settings

A full-featured CRM and lead management plugin for WordPress. Captures leads from contact forms, manages them through a pipeline with custom statuses, assigns them to team members, tracks deal value, and exposes a complete REST API.

## Features

- **Custom Post Type**: Leads with full meta — email, phone, company, website, deal value, priority, close date, assigned user
- **Pipeline Management**: Custom taxonomies for status (New, Contacted, Qualified, Proposal, Won, Lost) and source (Website, Referral, etc.)
- **REST API**: Full CRUD at `/wp-json/wplm/v1/leads` — create, read, update, delete, notes, stats
- **Contact Form Shortcode**: `[wplm_contact_form]` — drop a lead capture form anywhere
- **Admin Dashboard**: Pipeline overview with total leads, total pipeline value, recent leads table
- **Notes System**: Internal notes per lead stored in a custom DB table
- **Email Notifications**: Configurable notifications to admin on new lead submission
- **CSV Export/Import**: Export all leads to CSV; import leads from CSV
- **Settings Page**: Notification toggle, notification email, custom status configuration
- **Dashboard Widget**: Lead count in the WordPress at-a-glance widget

## Installation

1. Upload the `wp-lead-manager` folder to `/wp-content/plugins/`
2. Activate through the Plugins menu
3. Navigate to **Lead Manager** in the admin sidebar

## Shortcode Usage

```
[wplm_contact_form source="website" redirect="/thank-you" button_label="Get In Touch"]
```

| Attribute | Default | Description |
|-----------|---------|-------------|
| `source` | `website` | Lead source taxonomy term |
| `redirect` | — | URL to redirect after successful submission |
| `button_label` | `Send Message` | Submit button text |

## REST API

All endpoints require authentication (`edit_posts` capability). Use Application Passwords or cookie auth.

```
GET    /wp-json/wplm/v1/leads              # List leads (supports page, per_page, status, orderby)
POST   /wp-json/wplm/v1/leads              # Create lead
GET    /wp-json/wplm/v1/leads/{id}         # Get single lead
PATCH  /wp-json/wplm/v1/leads/{id}         # Update lead
DELETE /wp-json/wplm/v1/leads/{id}         # Delete lead
GET    /wp-json/wplm/v1/leads/{id}/notes   # Get notes
POST   /wp-json/wplm/v1/leads/{id}/notes   # Add note
GET    /wp-json/wplm/v1/stats              # Pipeline stats
```

## File Structure

```
wp-lead-manager/
├── wp-lead-manager.php          # Plugin bootstrap, activation, DB table creation
├── includes/
│   ├── class-post-types.php     # CPT + taxonomies registration
│   ├── class-meta-boxes.php     # Admin meta boxes (contact details, lead details, notes)
│   ├── class-rest-api.php       # WP REST API endpoints
│   ├── class-email-notifications.php
│   ├── class-csv-handler.php    # Export and import
│   ├── class-shortcodes.php     # Contact form shortcode + Ajax handlers
│   └── class-ajax-handlers.php
├── admin/
│   ├── class-admin.php          # Admin menu, dashboard, settings
│   └── views/
│       ├── dashboard.php
│       └── settings.php
└── assets/
    ├── css/admin.css
    ├── css/public.css
    ├── js/admin.js
    └── js/public.js
```

## Requirements

- WordPress 6.0+
- PHP 8.0+
