# wp-therapist-directory
Wordpress Plugin to manage a directory of health professional with specialty and location. Can be used for a geolocation search.

## Plugin structure

therapist-directory/
├── therapist-directory.php              # Fichier principal, bootstrap
├── includes/
│   ├── class-td-activator.php           # Activation (création tables)
│   ├── class-td-deactivator.php         # Désactivation
│   ├── class-td-post-type.php           # Custom Post Type "therapeute"
│   ├── class-td-taxonomy.php            # Taxonomie hiérarchique
│   ├── class-td-meta-boxes.php          # Meta boxes admin
│   ├── class-td-address-db.php          # CRUD table adresses
│   ├── class-td-geocoder.php            # Géocodage Nominatim
│   └── class-td-ajax.php               # Endpoints AJAX
├── admin/
│   ├── css/td-admin.css                 # Styles admin modernes
│   └── js/td-admin.js                   # JS admin (adresses dynamiques)
├── public/
│   ├── css/td-public.css                # Styles frontend
│   ├── js/td-public.js                  # JS frontend (carte, recherche)
│   └── class-td-shortcodes.php          # Shortcodes frontend
└── languages/                           # Traductions

## Data Model
Custom Message Type: Therapist
Meta post for simple champions:
_td_title (female|male)
_td_last_name
_td_first_name
_td_title
_td_email
_td_main_phone
_td_adeli
_td_website
_td_information
_td_notes
_td_photo (Attachment ID)
_td_business_phone_1
_td_business_phone_2
Hierarchical Taxonomy: Therapist_category
Parent/Child Category Support
Custom Table: {prefix}_td_addresses
A dedicated table for addresses (1:N relationship):
identifier (PK, auto-incrementing)
therapist_id (FK → post ID)
address (number + street)
postal_code
city
payroll
latitude (decimal) 10.8)
longitude (decimal 11.8)
is_primary (boolean)
created_at, updated_at

## Geocoding
Using the Nominatim API (OpenStreetMap) — free, no API key required.

Automatic geocoding when saving an address
"Geolocate" button to force recalculation
Proximity search via Haversine formula in SQL

## Modern admin design
Clean palette: white, light gray, soft blue accents
Maps with subtle shadows and rounded corners
Clean typography, ample spacing
Dynamic address form (add/delete via JS without reloading)
Tabs to organize fields (Identity / Contact / Addresses / Notes)
No dependency on a heavy JS framework — vanilla JS + custom CSS

## Implementation steps
### 1. Plugin Bootstrap
Main file with WordPress headers
Automatic class loading
Activation/deactivation hooks
Creation of the wp_td_addresses table upon activation
### 2. Custom post type and taxonomy
Saving the therapeutic CPT (without a classic editor, supports: title, thumbnail)
Hierarchical taxonomy therapist_category with user interface administrator
Custom columns in the admin list
### 3. Metabox and editing form
"Identity" metabox: title, last name, first name, title
"Contact" metabox: email, main phone, work phone 1/2, website, ADELI
"Information" metabox: information, notes, photo
"Addresses" metabox: dynamic form with address addition/deletion
Server-side validation of required fields
Tabbed interface in the editing screen
### 4. Address management
CRUD via AJAX (add, modify, delete addresses)
Automatic geocoding via Nominatim on save
Display of calculated latitude/longitude coordinates, manually editable
### 5. Modern administrative styles
Custom CSS for metaboxes
Clean design cards, tabs, and forms
Responsive in the admin area
### 6. Frontend (code (shortcodes)
All shortcodes accept a category parameter (taxonomy slug) to filter by specialty. Each specialty can thus have its own page.

[therapist_directorycategory="osteopath"]: List of therapists in the category, with filters (city, name). Without parameters, the category displays all therapists.

[therapist_searchcategory="osteopath"]: Search by proximity (postal code + radius) limited to the category.

[therapist_map Category="osteopath"]: Leaflet/OpenStreetMap map with category markers.
Therapist detail pages
