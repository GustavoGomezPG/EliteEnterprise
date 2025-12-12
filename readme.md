# Elite Dental Enterprise Theme

A modern WordPress theme with Elementor integration, featuring Barba.js page transitions, GSAP animations, Lenis smooth scrolling, and Lottie animations.

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ and npm
- WordPress installation
- Elementor plugin

### Installation
1. Navigate to the theme directory:
```bash
cd wp-content/themes/EliteEnterprise
```

2. Install dependencies:
```bash
npm install
```

### Development
Start the Vite dev server with HMR:
```bash
npm run dev
```

### Production Build
Build optimized assets:
```bash
npm run build
```

### Environment Detection
The theme automatically switches between development and production:
- **Development** (no `dist/` folder): Loads from Vite dev server with HMR
- **Production** (`dist/` folder exists): Loads optimized bundles

## 🎨 Animation System

The theme includes a powerful class-based animation system. Simply add classes to your Elementor widgets to enable animations:

### Available Animations

#### 1. **Parallax Effect** (`.parallax`)
Creates smooth scrolling parallax effects on elements.

**Usage:**
```html
<div class="parallax" data-direction="vertical" data-start="0" data-end="300">
  Your content
</div>
```

**Attributes:**
- `data-direction`: `vertical` (default) or `horizontal`
- `data-start`: Starting position in pixels (default: 0)
- `data-end`: Ending position in pixels (default: 300)

---

#### 2. **Levitate Effect** (`.levitate`)
Adds a floating/levitating animation with subtle movement and rotation.

**Usage:**
```html
<div class="levitate">
  Your content
</div>
```

**Features:**
- Random X/Y movement
- Subtle rotation
- Smooth sine easing
- Automatic looping

---

#### 3. **Fade In** (`.fade-in`)
Fades elements in with staggered timing.

**Usage:**
```html
<div class="fade-in">
  Your content
</div>
```

**Features:**
- Opacity: 0 → 1
- 1 second duration
- 0.2s stagger for multiple elements
- Power3 easing

---

#### 4. **Split Text Animations** (`.split-text-*`)
Animates text by splitting it into words, lines, or characters.

**Usage:**

**Words:**
```html
<div class="split-text-words">
  <h2 class="elementor-heading-title">Your text here</h2>
</div>
```

**Lines:**
```html
<div class="split-text-lines">
  <h2 class="elementor-heading-title">Your text here</h2>
</div>
```

**Characters:**
```html
<div class="split-text">
  <h2 class="elementor-heading-title">Your text here</h2>
</div>
```

**Features:**
- ScrollTrigger-based animation
- Responsive (auto-adjusts on resize)
- Staggered reveal effect
- Requires `elementor-heading-title` class on text element

---

#### 5. **Glare Card Effect** (`.glow-card`)
Adds an interactive 3D card effect with shadow and glare on hover.

**Usage:**
```html
<div class="glow-card">
  <div>
    <img src="image.jpg" alt="">
  </div>
</div>
```

**Features:**
- 3D rotation on mouse move
- Dynamic shadow based on rotation
- Glare gradient effect
- Smooth scale animation (1 → 1.1)
- Auto-resets on mouse leave

---

### How Animations Work

All animations are automatically initialized when:
1. Page loads (initial)
2. Barba.js page transition completes
3. Elementor content is added dynamically

The `animator()` function in `page-functions.js` scans the page and applies all animations automatically.

### Combining Animations

You can combine multiple animation classes:
```html
<div class="parallax levitate fade-in" data-direction="vertical" data-start="0" data-end="200">
  Your content
</div>
```

---

## 📚 Dependencies

**Production:**
- `@barba/core` - Page transitions
- `gsap` - Animation library
- `lenis` - Smooth scrolling
- `lottie-web` - Lottie animations
- `split-type` - Text splitting
- `tailwindcss` - CSS framework

**Development:**
- `vite` - Build tool
- `@tailwindcss/vite` - Tailwind integration

## 🚀 Deployment

### Checklist
- [ ] Run `npm run build`
- [ ] Verify `dist/` folder exists
- [ ] Test the site
- [ ] Deploy with `dist/` folder included
- [ ] Exclude `node_modules/`

### Deploy These Files
- ✅ `dist/` (required)
- ✅ `assets/`
- ✅ `includes/`
- ✅ `functions.php`
- ✅ All theme files

### Do NOT Deploy
- ❌ `node_modules/`
- ❌ `package-lock.json`

---

## 🐛 Troubleshooting

**Dev server not working:**
- Check port 3000 availability
- Verify `http://localhost:3000` is accessible

**Build fails:**
```bash
rm -rf node_modules package-lock.json
npm install
npm run build
```

**Scripts not loading:**
- Clear browser and WordPress cache
- Check console for errors
- Verify file permissions (755/644)

**Changes not showing:**
- With dev server: Should be instant (HMR)
- Without: Clear cache and hard reload (Ctrl+Shift+R)

---

# Member Role System Documentation

## Overview
This system adds a custom "Member" user role to WordPress with restricted access and custom fields managed through Advanced Custom Fields (ACF).

## Components

### 1. Member Class (`includes/Member.php`)
The main class that handles all member functionality:

- **Role Registration**: Creates the "member" role with limited capabilities
- **Access Control**: Blocks members from accessing WordPress admin
- **Login Redirect**: Redirects members to their dashboard after login
- **Custom Fields**: Manages ACF field integration

#### Key Methods:
```php
// Check if a user has member role
Member::is_user_member($user_id);

// Get member dashboard URL
Member::get_dashboard_url();

// Get custom field value
Member::get_field('field_name', $user_id);

// Update custom field value
Member::update_field('field_name', $value, $user_id);
```

### 2. ACF Custom Fields (`acf-json/group_member_fields.json`)
Declarative JSON configuration for member custom fields. The following fields are included:

- **member_phone**: Phone number (text)
- **member_address**: Mailing address (textarea)
- **member_date_of_birth**: Date of birth (date picker)
- **member_company**: Company/organization (text)
- **member_job_title**: Job title/position (text)
- **member_bio**: Biography (WYSIWYG editor)
- **member_profile_image**: Profile photo (image upload)
- **member_status**: Membership status (select: active/pending/suspended/expired)
- **member_join_date**: Join date (date picker)
- **member_notes**: Admin-only notes (textarea)

### 3. Member Dashboard (`page-member-dashboard.php`)
Custom page template for the member dashboard interface.

## Setup Instructions

### Step 1: Create Dashboard Page
1. Go to WordPress Admin → Pages → Add New
2. Title: "Member Dashboard"
3. Set the slug to: `dashboard`
4. In Page Attributes, select Template: "Member Dashboard"
5. Publish the page

### Step 2: Create Members Parent Page
1. Go to WordPress Admin → Pages → Add New
2. Title: "Members"
3. Set the slug to: `members`
4. Add the dashboard page as a child by editing it and setting Parent to "Members"
5. The final URL should be: `yoursite.com/members/dashboard`

### Step 3: Install Advanced Custom Fields Plugin
1. Install and activate the ACF plugin (free or pro version)
2. The custom fields will automatically load from `acf-json/group_member_fields.json`

### Step 4: Activate Member Role
The member role will be registered automatically when:
- The theme is activated for the first time
- On every page load (it checks if the role exists)

**If the member role is not showing up:**
1. Simply refresh any WordPress admin page
2. Go to Users → Add New and check the Role dropdown
3. The "Member" role should now appear

## Troubleshooting

### Member Role Not Appearing in User Creation
If the "Member" role doesn't appear in the Role dropdown:
1. Make sure the theme is active
2. Refresh the WordPress admin page
3. Check that `includes/Member.php` is loaded in `functions.php`
4. Visit any admin page - the role is registered on every `init` hook

### Role Disappeared After Theme Update
The role registration has been updated to persist across theme updates. Simply refresh any admin page to re-register the role.

## Adding/Removing Custom Fields

To add or remove custom fields, edit the JSON file at:
```
acf-json/group_member_fields.json
```

### Adding a New Field
Add a new field object to the `fields` array:

```json
{
    "key": "field_member_custom",
    "label": "Custom Field",
    "name": "member_custom",
    "type": "text",
    "instructions": "Your field instructions",
    "required": 0,
    "conditional_logic": 0,
    "wrapper": {
        "width": "",
        "class": "",
        "id": ""
    }
}
```

### Removing a Field
Simply remove the field object from the `fields` array in the JSON file.

## Security Features (CRITICAL)

### 1. Admin Access Blocking
Members are **completely blocked** from accessing:
- WordPress admin dashboard (`/wp-admin/`)
- Any admin pages
- Admin AJAX requests (except where needed)

### 2. Admin Bar Removal
The WordPress admin bar is hidden for all members on the front-end.

### 3. Login Redirect
Members are **always** redirected to `/members/dashboard` after login, never to admin.

### 4. Forced Redirect
If a member tries to access any admin URL directly, they are immediately redirected back to their dashboard.

## Usage Examples

### Creating a Member User
1. Go to Users → Add New in WordPress Admin
2. Fill in user details
3. In "Role" dropdown, select "Member"
4. The custom fields will appear automatically

### Accessing Member Data in Templates
```php
// Check if user is a member
if (Member::is_user_member()) {
    // Get profile image
    $profile_image = Member::get_field('member_profile_image');
    
    // Get phone number
    $phone = Member::get_field('member_phone');
    
    // Get company
    $company = Member::get_field('member_company');
}
```

### Updating Member Fields Programmatically
```php
// Update member's phone number
Member::update_field('member_phone', '(555) 123-4567', $user_id);

// Update membership status
Member::update_field('member_status', 'active', $user_id);
```

## File Structure
```
EliteEnterprise/
├── includes/
│   └── Member.php                    # Main Member class
├── acf-json/
│   └── group_member_fields.json      # ACF field definitions
├── page-member-dashboard.php         # Dashboard template
└── functions.php                     # Loads Member class
```

## Customization

### Changing Dashboard URL
Edit the `DASHBOARD_SLUG` constant in `Member.php`:
```php
private const DASHBOARD_SLUG = 'members/dashboard';
```

### Modifying Member Capabilities
Edit the `$capabilities` array in the `register_member_role()` method:
```php
$capabilities = [
    'read' => true,
    'edit_posts' => false,  // Change to true to allow editing posts
    'delete_posts' => false,
    'publish_posts' => false,
    'upload_files' => false,
];
```

### Customizing Dashboard Layout
Edit `page-member-dashboard.php` to modify the dashboard interface using Tailwind CSS classes.

## Notes

- The member role is automatically created on theme activation
- ACF fields are loaded from JSON automatically
- Members cannot access any WordPress admin functionality
- The system uses WordPress hooks for maximum compatibility
- All redirects use WordPress core functions for security

## Support

For issues or questions about the member system, refer to:
- WordPress User Roles & Capabilities documentation
- Advanced Custom Fields documentation
- Theme's `includes/Member.php` file for implementation details

# Dynamic Menu Widget

An Elementor widget that displays different WordPress menus based on user login status and role, with full Barba.js support for smooth transitions.

## Features

- **Login Status Detection**: Show different menus for logged-in vs logged-out users
- **Role-Based Menus**: Configure specific menus for each user role (Administrator, Member, etc.)
- **Barba.js Integration**: Menus update automatically via AJAX when login status changes
- **Flexible Configuration**: Fallback system ensures appropriate menu is always displayed
- **Customizable Display**: Choose container element and CSS classes

## Usage

### Basic Setup

1. **Add the Widget**: In Elementor, search for "Dynamic Menu" in the widgets panel
2. **Configure Logged Out Menu**: Select which menu to show to non-logged-in users
3. **Configure Logged In Menu**: Select default menu for logged-in users

### Advanced Configuration

#### Enable Role-Specific Menus

1. Toggle "Enable Role-Specific Menus"
2. Select menus for specific roles:
   - **Administrator**: Menu for admin users
   - **Member**: Menu for member users
   - **Editor, Author, Contributor, Subscriber**: Menus for other roles

#### Menu Display Settings

- **Menu Container**: Choose wrapper element (div, nav, ul, or none)
- **Menu CSS Class**: Add custom CSS classes for styling

## How It Works

### Menu Selection Priority

The widget uses the following priority when selecting which menu to display:

1. **Logged Out**: Uses `logged_out_menu`
2. **Logged In with Role-Specific Menus**:
   - First checks for role-specific menu (e.g., `menu_member`)
   - Falls back to `logged_in_menu` if no role-specific menu is set
3. **Logged In without Role Menus**: Uses `logged_in_menu`

### Barba.js Integration

The widget automatically updates when:

- User logs in (menu changes from logged-out to logged-in version)
- User logs out (menu changes from logged-in to logged-out version)
- Page transitions occur via Barba.js

#### Technical Implementation

1. Widget stores settings in `data-widget-settings` attribute
2. JavaScript monitors `logged-in` class on `body` element
3. When login status changes, AJAX request fetches new menu
4. Menu HTML is updated without page reload
5. Integrates with `updateLoginLogoutWidgets()` global function

## Example Use Cases

### Simple Public/Member Menu

```
Logged Out Menu: "Public Navigation"
Logged In Menu: "Member Navigation"
```

### Multi-Role Setup

```
Logged Out Menu: "Public Navigation"
Logged In Menu: "Default Member Navigation" (fallback)
Enable Role-Specific Menus: Yes
  - Administrator: "Admin Dashboard Menu"
  - Member: "Member Portal Menu"
  - Subscriber: "Subscriber Menu"
```

## WordPress Menu Setup

Before using this widget, create your menus in **Appearance → Menus**:

1. Create separate menus for each user state/role
2. Add appropriate menu items to each menu
3. Note menu names for configuration in widget

## Styling

The widget outputs menus with the CSS class you specify (default: `dynamic-menu`). Style your menus using CSS:

```css
.dynamic-menu {
  /* Your menu styles */
}

.dynamic-menu ul {
  /* Submenu styles */
}

.dynamic-menu li a {
  /* Menu item link styles */
}
```

## JavaScript API

### Global Functions

The widget provides and uses these global functions:

- `window.updateDynamicMenus()` - Manually trigger menu update
- `window.updateLoginLogoutWidgets()` - Update login/logout button widgets

### Events

The widget updates on these Barba.js hooks:

- `barba.hooks.after()` - After page transition completes

## Troubleshooting

### Menu Not Updating

1. Check browser console for errors
2. Verify `window.wpData.ajaxUrl` is available
3. Ensure menus are properly configured in WordPress
4. Check that `logged-in` class is being added/removed from `body`

### Menu Not Displaying

1. Verify menu is selected in widget settings
2. Check that menu exists in WordPress (Appearance → Menus)
3. Ensure menu has items added to it
4. Check for JavaScript errors in console

## Development

### AJAX Endpoint

The widget uses the `get_dynamic_menu` AJAX action:

```php
// Handler location: includes/Member.php
public function ajax_get_dynamic_menu()
```

### Data Flow

1. Widget renders with initial menu based on server-side user status
2. On login/logout, JavaScript detects `logged-in` class change
3. AJAX request sent to `get_dynamic_menu` with widget settings
4. Server determines appropriate menu for current user
5. Menu HTML returned and widget content updated
6. Barba.js transition proceeds with updated menu

## Security

- AJAX endpoint validates widget settings
- User authentication checked server-side
- Menu permissions respect WordPress roles/capabilities
- No sensitive data exposed in AJAX requests



## 📖 Resources

- [Vite Documentation](https://vitejs.dev/)
- [GSAP Documentation](https://greensock.com/docs/)
- [Barba.js Documentation](https://barba.js.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)

---

**Maintained by Gustavo Gomez** | MIT License

