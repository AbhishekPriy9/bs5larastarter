# Bs5-Larastarter

A simple, modern admin panel starter kit for Laravel 11+ and 12+, built with [Bootstrap 5](https://getbootstrap.com/) and powered by the [Sneat Bootstrap 5 Admin Template](https://themeselection.com/products/sneat-bootstrap-html-admin-template/). This package provides a ready-to-use admin panel with authentication, user profile management, settings, and more.

## Minimum Requirements

- PHP >= 8.2
- Laravel 11 or 12
- Node.js >= 18.x
- Composer
- NPM

## Admin Panel Screenshot

![Admin Panel Screenshot](https://raw.githubusercontent.com/AbhishekPriy9/bs5larastarter/main/docs/admin-screenshot.png)

*Example: Sneat Bootstrap 5 Admin Dashboard*


## Features

- Laravel 11/12 support
- Bootstrap 5 (Sneat theme) UI
- Admin authentication (login/logout)
- Role-based access (admin only)
- User profile management
- Site settings management
- Responsive dashboard
- Vite asset bundling
- Clean code structure for easy customization

## Credits

- **Sneat Bootstrap 5 Admin Template** by [ThemeSelection](https://themeselection.com/products/sneat-bootstrap-html-admin-template/)

## Installation

> **Warning:** This package will wipe your database and create a new admin user. Use only on a fresh Laravel project.

1. **Require the package via Composer:**
   ```sh
   composer require abhishekpriy9/bs5larastarter
   ```

2. **Run the installation command:**
   ```sh
   php artisan bs5:install
   ```
   - You will be prompted to confirm database wipe and admin user creation.
   - Follow the prompts to set admin name, email, and password.

3. **Install Node.js dependencies:**
   ```sh
   npm install
   ```

4. **Start the Vite development server:**
   ```sh
   npm run dev
   ```
   - For production, use:
     ```sh
     npm run build
     ```

5. **Access the admin panel:**
   - Visit [http://your-app-url/admin/login](http://your-app-url/admin/login)

## Usage

- **Admin Login:** `/admin/login`
- **Dashboard:** `/admin/dashboard`
- **Profile:** `/admin/profile/edit`
- **Settings:** `/admin/settings`

## File Structure

See the [stubs/](stubs/) directory for published controllers, views, assets, and routes.

## Customization

- **Views:** Edit files in `resources/views/admin/` for dashboard, login, profile, and settings pages.
- **Assets:** Customize CSS/JS in `resources/admin/assets/`.
- **Settings:** Extend `app/Models/Setting.php` and `config/settings.php` for site configuration.
- **Middleware:** Modify `app/Http/Middleware/AdminMiddleware.php` for access control.

## Troubleshooting

### Watchers Limit Exceeded Error

If you see an error like `ENOSPC: System limit for number of file watchers reached` when running `npm run dev`, you need to increase the watchers limit on your system.

**To fix this on Linux, run:**

```bash
echo fs.inotify.max_user_watches=524288 | sudo tee -a /etc/sysctl.conf
sudo sysctl -p
```

This increases the number of files your system can watch, which is required for Vite and Laravel Mix to work properly.

## FAQ

### Will this package overwrite my existing database?
**Yes.** The install command runs `migrate:fresh`, which wipes all tables and data. Use only on a fresh Laravel project.

### Can I use this with an existing Laravel app?
It’s designed for new projects. Using it on an existing app may cause data loss and conflicts.

### How do I change the admin panel URL?
Edit the published `routes/admin.php` file and update route prefixes as needed.

### How do I add more admin users?
Register new users via the database or extend the admin registration logic in your controllers.

### How do I customize the theme?
Edit assets in `resources/admin/assets/` and views in `resources/views/admin/`.


## Sneat Theme License

This package uses the [Sneat Bootstrap 5 Admin Template](https://themeselection.com/products/sneat-bootstrap-html-admin-template/) (free version). For commercial use or advanced features, please purchase a license from ThemeSelection.

## Contributing

Pull requests and issues are welcome!

## License

MIT

---

**Developed by AbhishekPriy9**