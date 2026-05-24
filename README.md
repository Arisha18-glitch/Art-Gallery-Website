# Art Gallery Website

A dynamic web-based art gallery for showcasing calligraphy artworks with a secure admin panel for content management.

## Features

- Browse and view calligraphy artworks without any login
- Filter artworks by style, size, medium, and price
- Home page with featured banners and highlights
- Artworks page with full gallery display
- About page
- Contact Us page with email support
- Secure admin panel (username + password required)
- Admin can add, edit, delete, and manage artworks
- Image upload support for artwork entries

## Tech Stack

- **Frontend:** HTML, CSS, JavaScript
- **Backend:** PHP
- **Database:** MySQL
- **3D/Visual:** Three.js
- **Email:** PHP Mailer

## Project Structure
calligraphy_website/
├── admin/          # Admin panel (protected)
├── api/            # Backend API endpoints
├── uploads/        # Uploaded artwork images
├── index.html      # Home page
├── contact.php     # Contact form handler
└── ...
## Setup

1. Clone the repository
2. Copy `db_config.sample.php` to `db_config.php` and fill in your database credentials
3. Run `setup_db.php` once to initialize the database tables
4. Upload to your server or run locally with XAMPP/WAMP

## Admin Access

Admin panel is located at `/admin`. Credentials are stored in the database.  
To create the first admin user, run `setup_db.php` after configuring your database.

## Live Project

https://www.linkedin.com/feed/update/urn:li:activity:7428871715050418176/
