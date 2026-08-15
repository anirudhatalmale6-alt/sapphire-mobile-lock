# Sapphire Mobile Lock

A single-purpose WordPress plugin that locks the mobile and tablet layout of five
Elementor landing pages on sapphirecapitals.com.

**Read [HANDOVER.md](HANDOVER.md)** — it contains the full diagnosis, the measurements,
what was changed, and what to check if the pages ever look wrong again.

## The short version

64 Elementor elements across the five pages have a hard-coded horizontal padding (or,
on one page, margin) of 72–180px per side with no tablet or mobile value. The per-page
Elementor stylesheets contain zero `@media` rules. On a 390px phone that leaves a 20px
text column, so body copy renders one character per line and the pages become
217,000–306,000px tall instead of 15,000–20,000px.

## Result

| Page | Height at 390px before | after |
|---|---|---|
| swing-trading-stock-seasonality-strategy | 231,839px | 17,699px |
| swing-trading-stock-price-action-strategy | 253,776px | 18,043px |
| swing-trading-volume-spike-stock-trading-strategy | 306,160px | 19,313px |
| day-trading-intraday-seasonality-trading | 217,071px | 15,870px |
| sapphire-capitals-analytic-suite | 16,024px | 10,080px |

Horizontal overflow is 0px at 320/360/390/412/430/540/767/1024/1280 on all five pages.
Desktop rendering at 1280px is identical to the pixel, before and after.

## Install

Plugins → Add New → Upload Plugin → select the ZIP → Activate. No configuration.

## Requirements

WordPress 5.8+, PHP 7.0+. No Elementor Pro, no paid add-on, no theme change.
