# Elite Dental Enterprise Theme

A modern WordPress theme with Elementor integration, featuring Barba.js page transitions, GSAP animations, Lenis smooth scrolling, and Lottie animations.

## 🚀 NPM Package Management with Vite 8 Beta

This theme now uses **NPM** as the JavaScript package manager and **Vite 8 Beta with Rolldown** for bundling and optimization.

### Prerequisites

- Node.js 18+ and npm
- WordPress installation
- Elementor plugin

### 📦 Installation

1. Navigate to the theme directory:
```bash
cd wp-content/themes/EliteEnterprise
```

2. Install dependencies:
```bash
npm install
```

### 🛠️ Development

#### Start Vite Dev Server
Run the development server with Hot Module Replacement (HMR):
```bash
npm run dev
```

The Vite dev server will start at `http://localhost:3000` and automatically:
- Enable Hot Module Replacement (HMR)
- Serve JavaScript modules
- Update changes instantly without page reload

**How it works in development:**
- When Vite dev server is running, the theme automatically detects it
- Scripts are loaded from `http://localhost:3000` with module support
- No need to rebuild after each change
- Check your browser console for "Elite Enterprise Theme - Main JS Loaded"

### 📦 Production Build

Build optimized assets for production:
```bash
npm run build
```

This will:
- Bundle all JavaScript into a single minified file
- Optimize and tree-shake dependencies
- Generate a `dist/` folder with production assets
- Create a manifest file for WordPress asset loading
- Remove console logs and debugger statements

**Production workflow:**
1. Run `npm run build`
2. A `dist/` folder is created with optimized assets
3. The theme automatically detects the `dist/` folder and loads production assets
4. Deploy your theme with the `dist/` folder included

### 🔄 Environment Detection

The theme automatically switches between development and production modes:

#### Development Mode (No `dist/` folder)
- Loads scripts from Vite dev server (if running)
- Falls back to individual script files
- Enables HMR for instant updates
- Full source maps for debugging

#### Production Mode (`dist/` folder exists)
- Loads bundled, minified assets from `dist/`
- Uses Vite manifest for cache-busting
- Optimized performance
- No development overhead

### 📁 Project Structure

```
EliteEnterprise/
├── assets/
│   ├── js/
│   │   ├── main.js              # Main entry point (imports all dependencies)
│   │   ├── barba.js             # Barba.js library (from CDN)
│   │   ├── gsap.js              # GSAP library (from CDN)
│   │   ├── lenis-lib.js         # Lenis library (from CDN)
│   │   ├── lenis-init.js        # Lenis initialization
│   │   ├── lottie.js            # Lottie library
│   │   ├── splitType.js         # SplitType library
│   │   ├── preloader.js         # Preloader animation
│   │   ├── page-functions.js    # Page-specific functions
│   │   ├── page-transitions.js  # Barba transitions
│   │   ├── scrollTrigger.js     # ScrollTrigger plugin
│   │   └── scrollTo.js          # ScrollTo plugin
│   └── css/
│       └── main.css             # Main stylesheet
├── dist/                        # Production build output (auto-generated)
│   ├── js/
│   │   └── main.min.js         # Bundled JavaScript
│   ├── css/
│   │   └── style.min.css       # Extracted CSS
│   └── .vite/
│       └── manifest.json        # Asset manifest
├── includes/
│   └── production-scripts.php   # Production asset loader
├── functions.php                # Theme functions with environment detection
├── script-imports.php           # Development scripts loader
├── package.json                 # NPM dependencies
├── vite.config.js              # Vite configuration
└── .gitignore                   # Git ignore rules
```

### 📚 Dependencies

#### Production Dependencies
- `@barba/core` (^2.10.3) - Page transitions
- `@studio-freight/lenis` (^1.0.42) - Smooth scrolling
- `gsap` (^3.12.7) - Animation library
- `lottie-web` (^5.12.2) - Lottie animations
- `split-type` (^0.3.4) - Text splitting

#### Development Dependencies
- `vite` (^6.0.0-beta.2) - Build tool with Rolldown
- `@rolldown/plugin-replace` (^0.1.0) - String replacement plugin

### 🔧 Vite Configuration

The `vite.config.js` is configured with:
- **Rolldown**: Experimental Rust-based bundler for faster builds
- **IIFE Format**: Compatible with WordPress environments
- **Terser Minification**: Removes console logs and debugger statements
- **Manifest Generation**: For WordPress asset management
- **CSS Extraction**: Separate CSS files for better caching

### 🎨 Scripts Overview

#### Main Entry Point (`main.js`)
Imports and initializes:
- Barba.js for page transitions
- GSAP with ScrollTrigger and ScrollToPlugin
- Lenis smooth scroll
- Lottie animations
- SplitType text effects

All libraries are exposed on the `window` object for compatibility with existing scripts.

### 🚀 Deployment Checklist

1. ✅ Run `npm install` to ensure all dependencies are installed
2. ✅ Run `npm run build` to create production assets
3. ✅ Verify `dist/` folder is created
4. ✅ Test the site - it should load from `dist/` automatically
5. ✅ Deploy theme including the `dist/` folder
6. ✅ Exclude `node_modules/` from deployment (already in `.gitignore`)

### 📝 Development Workflow

1. **Make changes** to JavaScript files in `assets/js/`
2. If Vite dev server is running, changes apply **instantly via HMR**
3. If not running dev server, refresh the page to see changes
4. When ready for production, run `npm run build`

### 🐛 Troubleshooting

**Vite dev server not detected:**
- Make sure you ran `npm run dev`
- Check if port 3000 is available
- Verify `http://localhost:3000` is accessible

**Scripts not loading:**
- Check browser console for errors
- Verify `dist/` folder exists for production mode
- Clear WordPress cache
- Check file permissions

**Build errors:**
- Delete `node_modules/` and run `npm install` again
- Delete `dist/` folder and rebuild
- Check for syntax errors in JavaScript files


### How It Works

The theme automatically detects your environment:

```
┌─────────────────────────────────────────┐
│  Is there a dist/ folder?              │
└────────────┬────────────────────────────┘
             │
     ┌───────┴────────┐
     │                │
    YES              NO
     │                │
     ▼                ▼
PRODUCTION    ┌──────────────────┐
   MODE       │ Vite dev server  │
     │        │    running?      │
     │        └────────┬─────────┘
     │                 │
     │        ┌────────┴────────┐
     │       YES               NO
     │        │                 │
     ▼        ▼                 ▼
Load from   HMR Mode    Individual Scripts
  dist/     (http://localhost:3000)
```


### 6. Testing

**Development:**
1. Run `npm run dev`
2. Open your WordPress site
3. Open browser console
4. Look for: "Elite Enterprise Theme - Main JS Loaded"
5. Make a change to `main.js`
6. Changes should appear instantly (HMR)

**Production:**
1. Run `npm run build`
2. Check that `dist/` folder was created
3. Reload your WordPress site
4. Verify scripts load from `dist/js/main.min.js`
5. Check browser console for console.log removal

### 7. Deployment

**Before deploying:**
- [ ] Run `npm run build`
- [ ] Verify `dist/` folder exists
- [ ] Test the site loads correctly
- [ ] Check for JavaScript errors in console

**Deploy these folders/files:**
- ✅ `dist/` (required for production)
- ✅ `assets/` (for fallback and other assets)
- ✅ `includes/`
- ✅ `functions.php`
- ✅ `style.css`
- ✅ All other theme files

**Do NOT deploy:**
- ❌ `node_modules/`
- ❌ `package-lock.json` (unless needed for CI/CD)
- ❌ `.gitignore` content

### 8. Troubleshooting

**Problem:** Vite dev server not working
**Solution:** 
- Check if port 3000 is available
- Try `npm run dev -- --port 3001`
- Verify firewall settings

**Problem:** Build fails
**Solution:**
- Delete `node_modules/`: `rm -rf node_modules`
- Delete `package-lock.json`: `rm package-lock.json`
- Reinstall: `npm install`
- Try again: `npm run build`

**Problem:** Scripts not loading
**Solution:**
- Clear browser cache
- Clear WordPress cache
- Check browser console for errors
- Verify file permissions (755 for directories, 644 for files)

**Problem:** Changes not showing
**Solution:**
- If using Vite dev server, it should be instant
- If not, clear cache and hard reload (Ctrl+Shift+R)
- Rebuild: `npm run build`

### 9. Next Steps

- Customize `vite.config.js` for your needs
- Add more entry points if needed
- Integrate with existing custom scripts
- Set up CI/CD pipeline for automatic builds

### Need Help?

Check the main README.md for detailed documentation.


### 📖 Additional Resources

- [Vite Documentation](https://vitejs.dev/)
- [GSAP Documentation](https://greensock.com/docs/)
- [Barba.js Documentation](https://barba.js.org/)
- [Lenis Documentation](https://github.com/studio-freight/lenis)

### 🤝 Contributing

This theme is maintained by Gustavo Gomez.

### 📄 License

MIT License

---

**Note**: The theme automatically handles development vs. production modes. You don't need to manually switch configurations - just run the dev server for development or build for production!

