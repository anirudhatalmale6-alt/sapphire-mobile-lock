# Sapphire Capitals — mobile layout hand-over note

Prepared by Anirudha Talmale. Diagnosis run against the live pages on 15 Aug 2026.

---

## 1. What is actually wrong

It is not caching, not a theme update, not rogue inline CSS, and not an Elementor
version conflict. It is a single mistake repeated 48 times.

**48 Elementor widgets across the four pages carry a hard-coded horizontal padding
of 100px–180px per side, and no tablet or mobile value was ever set for them.**

The per-page Elementor stylesheets contain **zero `@media` rules**. That is the
whole story in one fact — there is literally no mobile styling on these pages.
Every value in them is a desktop value that applies at every screen size.

```
Elementor CSS files and how many responsive rules each one contains:
  post-5037.css   24,228 bytes   0 @media rules
  post-5276.css   22,971 bytes   0 @media rules
  post-5453.css   22,926 bytes   0 @media rules
  post-5456.css   20,365 bytes   0 @media rules
```

### The arithmetic

A typical rule, from `post-5037.css`:

```css
.elementor-5037 .elementor-element.elementor-element-7209121 > .elementor-widget-container {
    padding: 0px 0175px 0px 0175px;
}
```

| Screen | Container width | minus 350px padding | Text column |
|---|---|---|---|
| Desktop 1280px | 1140px | 1140 − 350 | **790px** — looks fine |
| iPhone 390px | 370px | 370 − 350 | **20px** |

A 20px-wide text column at a 22px font size fits **one character per line**.
That is why the pages look the way they do on a phone.

### The consequence, measured

| Page | Desktop height | Height at 390px | Ratio |
|---|---|---|---|
| swing-trading-stock-seasonality-strategy | 18,056px | 231,839px | 12.8× |
| swing-trading-stock-price-action-strategy | 18,661px | 253,776px | 13.6× |
| swing-trading-volume-spike-stock-trading-strategy | 19,738px | 306,160px | 15.5× |
| day-trading-intraday-seasonality-trading | 15,120px | 217,071px | 14.4× |

At 320px the seasonality page is **305,954px tall**. A visitor would have to
scroll roughly 400 phone-screens to reach the footer.

### A detail worth knowing

Many of the values are typed with a leading zero — `0175px`, `0100px`, `010px`.
CSS accepts a leading zero as a valid number, so `0175px` is silently parsed as
**175px**. It is not ignored and it does not throw an error. This looks like it
was typed by hand into Elementor's padding fields rather than dragged with the
slider, which also explains why the values are irregular
(`0px 150px 0px 0175px` — 150 on the right, 175 on the left).

---

## 2. The three secondary problems

**a. Hero background images do not scale.** Three of the four heroes have a
background image with **no `background-size` and no `background-repeat`**. The
browser default is `auto` + `repeat`, so a 1920px-wide JPEG renders at its native
size and tiles down a 2,500px-tall mobile section. You can see the seam in the
"before" screenshots. The fourth page (`post-5456.css`) does have
`background-size: cover`, which is why that page has always looked slightly
different from the other three.

**b. Typography has no mobile value.** The hero headline is 63px, section
headings are 40px, button labels are 36px. Those sizes apply unchanged at 320px.

**c. Negative bottom margins.** The hero headline widgets carry
`margin: 0px -10px -100px -10px` (−93px to −100px depending on the page). On
desktop that pulls the next section up by a designed amount. On a phone, once the
headline reflows from 2 lines to 9, the −100px lands in a completely different
place and drags the next section over the text. This is the "sections jump
around" symptom.

---

## 3. Why it keeps coming back every few weeks

Because it was never fixed at the source.

The desktop-only padding values live in the WordPress database, in the Elementor
page data (`_elementor_data` in `wp_postmeta`). The files in
`/wp-content/uploads/elementor/css/` are **generated from that data**. Elementor
rebuilds them on save, on "Regenerate Files & Data", after an Elementor update,
and after some cache purges.

So any fix applied to the generated CSS file, or to a cache layer, or anything
that gets flushed, is wiped and regenerated from the same broken source data. It
comes back looking identical because it *is* identical — regenerated from the
same 175px values that were never changed.

This is the part that matters for your question about preventing it in future:
**the recurrence is not caused by the updates. The updates just re-expose a
problem that was always there.**

---

## 4. What I changed

Nothing in Astra. Nothing in Elementor. No files edited, no core touched, no
paid add-on, no theme swap.

The fix is delivered as a small standalone plugin, `sapphire-mobile-lock`, which
loads one stylesheet at `wp_enqueue_scripts` priority **999** — after
`elementor-post-{id}.css` — so the cascade resolves in its favour.

A plugin, not a child theme, deliberately: a child theme is bound to Astra and
switching the active theme can disturb Customizer and widget assignments. A
plugin is independent of both the theme and Elementor, so a theme update, an
Elementor update, and an Elementor CSS regeneration all leave it untouched.

### The stylesheet, block by block

| Block | What it does | Selector shape |
|---|---|---|
| 1 | Zeroes the horizontal padding on the 48 offending widgets below 1025px | `.elementor-element-{id} > .elementor-widget-container` |
| 2 | Safety net — catches any *new* text/heading widget added later with the same mistake, on these four pages only | `.elementor-page-{5037,5276,5453,5456} .elementor-widget-text-editor > .elementor-widget-container` |
| 3 | Fluid typography via `clamp()` | `.elementor-widget-heading .elementor-heading-title`, `.elementor-button`, and the four hero headline widget IDs |
| 4 | Forces `cover` / `center` / `no-repeat` on section backgrounds below 1025px | `.elementor-section[class*="elementor-element-"]` |
| 5 | Neutralises the negative margins on mobile | the 9 affected widget IDs |
| 6 | Guards — `max-width: 100%` on sections/columns/widgets, `height: auto` on images | generic |
| 7 | Caps button padding at 16px below 768px (button padding sits on the `<a>`, not the widget container, so block 1 does not reach it) | `.elementor-button` |

Every rule is inside `@media (max-width: 1024px)` or `(max-width: 767px)`.
**Nothing applies at desktop width.** That is verified below.

### The `clamp()` values

`clamp(min, preferred, max)` — the max is set to the existing desktop value, so
desktop rendering is byte-identical.

```
hero headline    clamp(26px, 7.4vw, 63px)     was 63px flat
section headings clamp(22px, 5.6vw, 40px)     was 40px flat
body copy        clamp(16px, 4.4vw, 22px)     was 22px flat
buttons          clamp(15px, 4vw,   22px)     was 36px flat
```

---

## 5. Verification

Measured in a real browser engine (Chromium) by reading
`document.documentElement.scrollHeight` and the bounding box of every element on
the page, at nine widths, on all four pages. Overflow is measured as
`max(element.right) − viewport width`, ignoring `position: fixed` elements.

Screenshots cannot prove this — a headless browser hides scrollbars, so a page
that scrolls sideways still screenshots clean. These are geometry measurements,
not pictures.

```
page            width |  height before   height after | narrow paras B/A | overflow B/A
seasonality       320 |       305,954         18,538  |      1 / 0       |    63 / 0
seasonality       360 |       296,473         17,357  |      2 / 0       |    23 / 0
seasonality       390 |       231,839         17,699  |     38 / 0       |     0 / 0
seasonality       412 |       135,242         18,157  |     38 / 0       |     0 / 0
seasonality       430 |       107,899         19,009  |     38 / 0       |     0 / 0
seasonality       540 |        39,472         20,296  |      0 / 0       |     0 / 0
seasonality       767 |        22,158         18,969  |      0 / 0       |     0 / 0
seasonality      1024 |        19,305         17,905  |      0 / 0       |     0 / 0
seasonality      1280 |        18,056         18,056  |      0 / 0       |     0 / 0   <-- unchanged
price-action     1280 |        18,661         18,661  |      0 / 0       |     0 / 0   <-- unchanged
volume-spike     1280 |        19,738         19,738  |      0 / 0       |     0 / 0   <-- unchanged
intraday         1280 |        15,120         15,120  |      0 / 0       |     0 / 0   <-- unchanged
```

"narrow paras" counts `<p>` elements rendering under 120px wide — the
one-character-per-line failure. It reaches zero on every page at every width.

Horizontal overflow is **0px at every width on every page**, including 320px.

Desktop height at 1280px is identical to the pixel before and after on all four
pages, which is the check that matters for "did the fix break the desktop view" —
it did not.

---

## 6. Your acceptance criteria

| Criterion | Status |
|---|---|
| No layout shifting or cut-off images at 320px and up | Met. 0px horizontal overflow at 320/360/390/412/430/540/767/1024/1280 on all four pages. |
| Google Mobile-Friendly Test passes each URL | Expected to pass once deployed — the failure mode was tap-target spacing and content-wider-than-screen, both now resolved. Cannot be run until the plugin is live on a public URL; I will run it per URL after deployment and send you the results. |
| Survives one full theme + plugin update cycle on staging | The fix lives in a plugin, not in Elementor data, not in the theme, and not in generated files. Astra updates, Elementor updates and Elementor CSS regeneration cannot reach it. Please still run the cycle on staging — I will re-run the measurements afterwards. |

---

## 7. What to check if it ever looks wrong again

In order, takes about two minutes:

1. **Is the plugin still active?** Plugins → "Sapphire Mobile Lock". If a
   migration or a restore deactivated it, that alone explains a full relapse.
2. **View source on the page and search for `sapphire-mobile-lock.css`.** It must
   appear *after* `uploads/elementor/css/post-XXXX.css`. If it appears before,
   something changed the enqueue order and the cascade is being lost.
3. **Was a new section built?** If the new widget is a text or heading widget on
   one of the four pages, block 2 already covers it. Any other widget type with
   large horizontal padding needs its ID adding to block 1 — or, better, set its
   mobile padding properly in Elementor (see section 8).
4. **Was a page duplicated to a new URL?** The new page has a new ID, so it is
   not in the list. Add it to `sapphire_mobile_lock_page_ids()` in the plugin
   file.

The one thing that will *not* be the cause: an Astra or Elementor update. Those
regenerate `post-XXXX.css`, which this fix deliberately does not depend on.

---

## 8. The permanent version of this fix

What I have delivered is a CSS layer that holds the layout in place regardless of
what the page data says. It is robust and it survives updates, but it is a layer
over the problem, not a removal of it.

The root-level fix is to open each of the 48 widgets in Elementor, switch to the
mobile and tablet device views, and set the horizontal padding to 0 there — which
writes proper responsive values into `_elementor_data` and makes the generated
CSS correct at source. Elementor's free version fully supports this; it needs no
Pro licence. It is roughly 45–60 minutes of clicking per page.

My recommendation: **keep the plugin permanently regardless.** Even after the
widgets are corrected, it costs one small HTTP request and it means the next
person who types `0175px` into a padding field cannot break the page for your
mobile visitors. Do the source-level correction as well if you want the Elementor
editor's own mobile preview to look right — right now the editor preview is as
broken as the live page, because it reads the same data.

---

## 9. Install

1. Download the repository as a ZIP.
2. WordPress admin → Plugins → Add New → Upload Plugin → select the ZIP → Install.
3. Activate "Sapphire Mobile Lock".
4. Purge any page cache.

No settings screen, no configuration, nothing to fill in.

To bring another page under the same protection, add its ID to the array in
`sapphire_mobile_lock_page_ids()`. To apply the stylesheet site-wide, empty the
array.

## 10. Removal

Deactivate the plugin. The pages return to exactly their current state — nothing
is written to the database and nothing in Astra, Elementor or your page content
is modified at any point.
