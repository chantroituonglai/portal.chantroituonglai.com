# OpenClaw Gateway System Design

This document describes the visual system and interaction design language used by the OpenClaw Gateway admin surfaces, primarily implemented in [views/admin/pipeline.php](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules/openclaw_gateway/views/admin/pipeline.php).

## Purpose

The OpenClaw Gateway UI is an operational workspace, not a marketing surface and not a generic admin card stack. Its design has one goal: let operators understand bridge health, queue pressure, scope posture, and request failures within seconds, then move directly into action.

The interface is therefore built around:

- fast scanability
- clear hierarchy
- strong operational affordances
- low visual noise
- a premium but restrained control-room tone

## Design Thesis

### Visual thesis

Calm infrastructure UI with editorial spacing, soft atmospheric surfaces, and a single deep-green control accent that signals confidence rather than urgency.

### Content thesis

Every screen should answer three questions immediately:

1. What area of the gateway am I in?
2. What is the current system posture?
3. What action or drill-down should I take next?

### Interaction thesis

Movement and emphasis should sharpen orientation, not decorate the page. Hover lift, active-tab tinting, row emphasis, and inspector transitions are used to make state changes obvious without turning the module into a motion-heavy dashboard.

## Core Principles

### 1. One control surface, not many disconnected cards

The module should feel like one coherent workspace. Panels may exist for containment, but the user should perceive a single shell with clear zones: hero, state summary, navigation, working surface, and detail layer.

### 2. Status first, settings second, raw data third

Operators should see posture and failures before form fields or JSON. The design intentionally promotes summary and direction ahead of raw payloads.

### 3. Typography carries hierarchy

Large titles, compact metadata, and muted explanatory copy do more work than borders or decorative chrome. If a section needs extra boxes to explain itself, the hierarchy is probably weak.

### 4. Color is semantic, not ornamental

Green is the primary system accent, warm rust is reserved for alert tension, and muted slate supports secondary information. Additional bright accents should be avoided unless they represent a true operational state.

### 5. Logs are operational instruments

Tables, badges, filters, and detail drawers must be optimized for scanning, comparing, and narrowing, not for dense visual novelty.

## Visual Language

### Palette

The module uses a restrained palette defined in the shell layer:

- `--ocg-ink`: main text and headings
- `--ocg-muted`: supporting copy and secondary labels
- `--ocg-line`: structural separators and soft borders
- `--ocg-surface`: primary panel background
- `--ocg-soft`: warm atmospheric background tone
- `--ocg-tint`: cool control tint used in active or summary areas
- `--ocg-accent`: primary action and state accent
- `--ocg-accent-strong`: high-contrast accent for strong emphasis
- `--ocg-alert`: restrained warning/failure accent

Color usage rules:

- default surfaces remain light and breathable
- accent green signals actionability, active state, and healthy control
- alert color appears only where failure, retry, or degraded state matters
- muted text should always be readable, never washed out

### Shape

The system avoids sharp industrial edges. Rounded geometry communicates a modern, contained control layer:

- major shells: large radius
- stat tiles: medium-large radius
- tabs and filters: pill or soft capsule forms
- badges: compact rounded units

Rounded corners should support calmness, not softness for its own sake. Excessively playful geometry is out of scope.

### Shadow and depth

Depth is subtle and structural:

- primary panels use a soft long shadow
- interactive hover states lift slightly
- active surfaces rely more on tint and border emphasis than dramatic shadows

If shadows are removed, the interface should still feel premium through spacing, proportion, and typography alone.

## Layout System

## Overall shell

The layout is organized as a vertical progression:

1. Hero band
2. Stat band
3. Tab rail
4. Active workspace
5. Inspector or modal detail

This sequence is fixed because it mirrors the operator workflow:

- orient
- assess
- navigate
- operate
- inspect

### Hero band

The hero is not promotional. It is a contextual orientation block with a strong title, short descriptive line, and soft atmospheric background. Its job is to tell the operator where they are and what that area controls.

Hero rules:

- always contains the page title and operational description
- may include a small uppercase kicker for section identity
- should never include marketing copy, vanity metrics, or decorative clutter
- should feel like an opening frame for the workspace

### Stat band

The stat band compresses 24-hour or relevant window signals into immediate-readable tiles. It exists to make system posture visible before the operator starts filtering or editing.

Stat tile rules:

- one metric per tile
- one short label
- one dominant numeric value
- one short line of interpretive copy

Do not overload a tile with multiple metrics or nested mini-charts unless the shell is redesigned intentionally.

### Tab rail

Tabs are designed as route-aware pills with metadata, not plain text links. Each tab tells the operator both destination and live context.

Each tab includes:

- icon
- title
- short meta count or status
- active-state tint when selected

This turns navigation into a posture map, not a passive menu.

## Page Archetypes

The module currently operates across three page families.

### 1. Overview

The overview is the command deck. It summarizes pipeline health, bridge state, queue posture, and gateway activity. It should present the broadest system read with the least amount of form interaction.

### 2. Control pages

Bridge Settings and Scope Control are configuration pages. Their layout should keep forms readable and grouped by system responsibility, not by database storage shape.

Control-page rules:

- group fields by intent
- explain side effects briefly
- keep form actions obvious and sparse
- preserve whitespace around dense configuration groups

### 3. Log pages

Pipeline Logs, Bridge Queue, and Gateway Logs are investigation pages. Their design emphasizes filter-first workflows, scannable tables, and clean drill-down into row detail.

Log-page rules:

- filters are above data
- the table is the main workspace
- row detail should step into an inspector experience, not a raw dump wall

## Component System

### Panel shell

The base panel shell uses light surface color, soft line treatment, rounded geometry, and restrained shadow. It acts as containment, not decoration.

Use the panel shell for:

- hero container
- tab container
- log workspace
- settings groups

Avoid stacking many nested shells unless they represent real information boundaries.

### Section heading block

A standard heading block includes:

- one title
- one short explanatory sentence

The explanatory line should clarify behavior or scope, not restate the title with synonyms.

### Filter row

The filter layer is designed for operational speed.

Filter rules:

- inputs must be wide enough for realistic data
- primary filters appear first
- date bounds stay visually paired
- apply and reset actions remain visible and plain

Button styling should feel like tooling, not a consumer app CTA set.

### Data table styling

Tables should feel dense but not cramped.

Row design goals:

- emphasize identifiers and action labels
- reduce visual weight of repetitive metadata
- convert key statuses into badges
- preserve alignment for rapid column scanning

Important fields such as `request_id`, `event`, `action`, `status`, `direction`, and HTTP outcomes should receive stronger visual treatment than timestamps or duplicated labels.

### Badge system

Badges are used to compress meaning without adding column width.

Badge behavior:

- status badges express success, warning, failure, queued, or pending states
- direction badges distinguish inbound vs outbound traffic
- transport badges can represent HTTP or execution outcome

Badges must remain restrained. Too many badge colors weakens the system.

### Inspector modal

The detail modal should function like an investigation inspector:

- summary strip first
- high-signal overview fields second
- raw payload and JSON after context

The modal is not only for storage dumps. It should help the operator decide quickly whether deeper payload inspection is necessary.

## Motion and State Changes

The module uses minimal but intentional motion:

- tab hover lift
- subtle panel elevation on hover-capable elements
- active-state tint transitions
- modal appearance with clear visual focus

Motion rules:

- short duration
- low amplitude
- consistent easing
- no ornamental bounce or delayed theatrics

The UI should feel responsive and controlled, not animated for spectacle.

## Content Style

Copy inside this module should remain operational and utility-focused.

Good copy patterns:

- “Inspect queued bridge deliveries, retry attempts, HTTP responses, and delivery failures.”
- “Use filters below to narrow results before opening row details.”

Avoid:

- aspirational slogans
- marketing tone
- abstract product claims
- repetitive filler like “manage your workflow efficiently”

If a sentence does not help an operator orient, decide, or act, it should be cut.

## Responsive Behavior

The design should remain intact across desktop and smaller viewports.

Responsive priorities:

- stat band collapses gracefully without losing metric emphasis
- tab rail remains readable and tappable
- filter groups stack cleanly
- tables preserve hierarchy even when horizontally constrained
- modal detail remains usable on smaller screens

On mobile or narrow widths, hierarchy matters more than simultaneous visibility. Compression should keep meaning, not every original column width.

## Accessibility and Readability

The visual system should stay premium without sacrificing usability.

Requirements:

- strong contrast for text over tinted or atmospheric backgrounds
- obvious active states
- focus-visible behavior for interactive elements
- readable placeholder and muted text
- badges and color states should not be the only indicator of meaning

Operational tools fail quickly when subtle styling reduces clarity. Readability always outranks style flourishes.

## Guardrails

The following changes should be avoided unless the module is intentionally redesigned end to end:

- reverting to generic stacked white cards
- replacing the tab rail with plain text underlines
- adding multiple unrelated accent colors
- introducing dense dashboard widgets that compete with the log workspace
- using heavy gradients behind ordinary form fields
- turning headings into long explanatory paragraphs
- making log detail start with raw JSON instead of summary context

## Extension Rules

When adding new tabs or pages to this module:

1. Map the page into an existing archetype: overview, control, or investigation.
2. Add a route-aware tab with useful metadata, not just a label.
3. Preserve the hero, stat, navigation, and workspace rhythm unless there is a strong reason not to.
4. Reuse the badge and inspector language for operational data.
5. Favor one dominant workspace per page instead of many equal-weight regions.

## Implementation Reference

The current system is expressed mainly in:

- [views/admin/pipeline.php](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules/openclaw_gateway/views/admin/pipeline.php)
- [controllers/Openclaw_gateway_admin.php](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/public_html/modules/openclaw_gateway/controllers/Openclaw_gateway_admin.php)
- [scripts/test_openclaw_gateway_admin_view.php](/Applications/XAMPP/xamppfiles/htdocs/portal.chantroituonglai.com/scripts/test_openclaw_gateway_admin_view.php)

This document should be treated as the styling contract for future changes to OpenClaw Gateway admin surfaces.
