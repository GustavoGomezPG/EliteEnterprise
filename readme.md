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

## 📖 Resources

- [Vite Documentation](https://vitejs.dev/)
- [GSAP Documentation](https://greensock.com/docs/)
- [Barba.js Documentation](https://barba.js.org/)
- [Tailwind CSS Documentation](https://tailwindcss.com/)

---

**Maintained by Gustavo Gomez** | MIT License

