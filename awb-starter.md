# AWB Starter – AI Implementation Specification

This document is a **clean, structured, AI-optimized implementation guide** for extending the AWB Starter WordPress plugin.

It is designed for use with coding agents (e.g., Codex, Claude Code) and must be followed **exactly in order**.

---

# 0. Core Principles (READ FIRST)

The AI must follow these rules at all times:

- DO NOT break existing functionality
- ALWAYS follow WordPress coding standards
- ALWAYS sanitize, escape, and validate data
- ALWAYS use nonces for AJAX
- ALWAYS check user capabilities
- KEEP performance a top priority (no unnecessary queries or assets)
- WRITE modular, reusable, class-based code
- DO NOT duplicate logic—reuse existing systems where possible
- ENSURE backward compatibility

---

# 1. Feature Implementation Order (STRICT)

Implement features **exactly in this order**:

## Phase 1 – Core UX & Pattern Workflow (Highest Impact)

1. Pattern Export / Import
2. Pattern Duplication / Cloning
3. Enhanced Pattern Previews (iframe)
4. Pattern Favorites & Collections
5. Pattern Usage Analytics

## Phase 2 – Design System Power

6. Token Import/Export (JSON/CSS)
7. Custom Token Groups
8. Token Presets

## Phase 3 – AI System Expansion

9. Multi-step Conversational AI
10. Prompt Library
11. Full-page AI Generation
12. Image Generation Placeholders

## Phase 4 – Performance Engine

13. Conditional Asset Loading per Template
14. CSS/JS Combination & Minification
15. Pattern Dependency Management
16. Critical CSS (optional)

## Phase 5 – Developer Experience

17. WP-CLI Commands
18. REST API Endpoints
19. CodeMirror Integration
20. Block Inserter Enhancements

## Phase 6 – Platform Expansion

21. Full Scaffolding Implementation
22. Multisite Support
23. White-label Mode

## Phase 7 – Advanced Features

24. Pattern Collections UI Improvements
25. Font Manager Improvements
26. Pattern Usage Analytics Dashboard
27. AI Workflow Presets (NEW)
28. Pattern Versioning (NEW)

---

# 2. File Structure Expectations

All new code MUST follow this structure:

```
awb-starter/
├── .gitignore
├── admin/
│   ├── admin-library.php
│   └── admin-settings.php
├── assets/
│   ├── css/
│   │   ├── admin.css
│   │   ├── footers/
│   │   │   └── footers.css
│   │   ├── frontend.css
│   │   ├── general/
│   │   │   ├── hoe-het-werkt.css
│   │   │   ├── plaats-uw-klus.css
│   │   │   ├── vakman-landing.css
│   │   │   └── vakman-worden.css
│   │   └── headers/
│   │       ├── header-transparent.css
│   │       └── vakman-header.css
│   └── js/
│       ├── admin.js
│       ├── ai-admin.js
│       ├── frontend.js
│       ├── general/
│       │   ├── hoe-het-werkt.js
│       │   ├── plaats-uw-klus.js
│       │   ├── vakman-landing.js
│       │   └── vakman-worden.js
│       └── headers/
│           ├── header-transparent.js
│           └── vakman-header.js
├── awb-starter.php
├── awb-starter.pot
├── block-templates/
│   ├── about-split.html
│   ├── cta-banner.html
│   ├── hero-cta.html
│   ├── services-3col.html
│   └── testimonials-3col.html
├── includes/
│   ├── class-ai-generator.php
│   ├── class-ajax-handler.php
│   ├── class-asset-loader.php
│   ├── class-block-categories.php
│   ├── class-pattern-loader.php
│   └── class-settings.php
└── patterns/
    ├── footer/
    │   └── vakman-footer.php
    ├── general/
    │   ├── hoe-het-werkt.php
    │   ├── plaats-uw-klus.php
    │   ├── vakman-landing.php
    │   └── vakman-worden.php
    └── header/
        ├── header-centered.php
        ├── header-dark.php
        ├── header-default.php
        ├── header-logoright.php
        ├── header-minimal.php
        ├── header-multibar.php
        ├── header-split.php
        ├── header-transparent.php
        └── vakman-header.php
```

---

# 3. Core Infrastructure

## 3.1 Database Tables

Create tables via activation hook.

Tables required:

- `awb_pattern_usage`
- `awb_user_favorites`
- `awb_token_presets`
- `awb_prompt_library`
- `awb_pattern_versions` (NEW)

AI MUST:

- Use `dbDelta`
- Use proper indexing
- Avoid duplicate queries

---

## 3.2 Settings Additions

Add the following options:

- awb_white_label
- awb_white_label_name
- awb_enable_analytics
- awb_combine_assets
- awb_critical_css

---

# 4. Pattern System Enhancements

## 4.1 Pattern Export / Import

### Requirements:

- Export pattern as ZIP
- Include:
  - PHP/HTML file
  - CSS
  - JS
  - metadata.json

### Import:

- Upload ZIP
- Validate structure
- Prevent overwrite unless confirmed

---

## 4.2 Pattern Duplication

- Clone pattern file
- Generate new slug
- Update headers automatically
- Ensure no conflicts

---

## 4.3 Pattern Preview (iframe)

- Render pattern in isolated iframe
- Load ONLY required assets
- No admin styles leakage

---

## 4.4 Favorites & Collections

- Users can:
  - Favorite patterns
  - Create collections
  - Assign patterns to collections

---

## 4.5 Pattern Analytics

Track:

- Views
- Insertions
- Page usage

Store efficiently. Avoid heavy queries.

---

# 5. Design Token System

## 5.1 Token Import/Export

Support:

- JSON
- CSS (:root parser)

---

## 5.2 Custom Token Groups

Allow user-defined groups:

- Colors
- Typography
- Spacing
- Custom

---

## 5.3 Token Presets

- Save full token sets
- Apply instantly
- Allow global presets

---

# 6. AI System

## 6.1 Multi-step AI

- Maintain conversation history
- Store per user

---

## 6.2 Prompt Library

- Save prompts
- Categorize
- Reuse

---

## 6.3 Full-page Generation

- Generate entire pages
- Use patterns + tokens

---

## 6.4 Image Generation

- Generate placeholder images
- Upload to Media Library

---

## 6.5 AI Workflow Presets (NEW)

Predefined workflows:

- Landing page
- Service site
- Blog

---

# 7. Performance System

## 7.1 Conditional Assets

Load assets ONLY if:

- Pattern exists in content
- Template matches

---

## 7.2 Asset Combination

- Merge CSS
- Merge JS
- Cache output files

---

## 7.3 Dependency Management

Patterns can declare:

```
Requires: hero-banner
```

Load recursively.

---

## 7.4 Critical CSS

- Inline above-the-fold CSS
- Store in option

---

# 8. Developer Tools

## 8.1 WP-CLI

Commands:

- create pattern
- export tokens
- import tokens

---

## 8.2 REST API

Endpoints:

- /patterns
- /tokens
- /generate

---

## 8.3 CodeMirror

- Enable for CSS/JS editors
- Syntax highlighting

---

# 9. Scaffolding System

Must:

- Create pages
- Assign templates
- Build menus
- Assign patterns

---

# 10. Platform Features

## 10.1 Multisite

- Support network activation
- Optional shared tokens/patterns

---

## 10.2 White Label

- Replace branding dynamically

---

# 11. Advanced Features

## 11.1 Pattern Versioning (NEW)

- Save revisions
- Rollback support

---

## 11.2 Analytics Dashboard

- Charts
- Usage trends

---

## 11.3 Font Manager

- Multi-weight support
- Preview text

---

# 12. UI/UX Requirements

- Clean admin UI
- Fast interactions
- No page reloads where possible
- Accessible (ARIA compliant)

---

# 13. Testing Requirements

AI MUST verify:

- No PHP errors
- AJAX secured
- Works in multisite
- Performance improved
- No duplicate assets

---

# 14. Definition of Done

A feature is complete when:

- Code is functional
- UI works
- No regressions
- Performance maintained
- Code is clean and modular

---

# 15. Final Instruction to AI

When implementing:

1. Analyze existing code first
2. Extend, do NOT rewrite unnecessarily
3. Implement ONE feature at a time
4. Test before moving to next
5. Keep output clean and production-ready

---

END OF SPEC
