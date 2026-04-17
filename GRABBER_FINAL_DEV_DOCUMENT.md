# GRABBER — FINAL COMPLETE DEVELOPMENT DOCUMENT
## Single Source of Truth · Build-Ready · VSCode / Claude Code

> **Company**: Grabber Mobility Solutions Pvt Ltd  
> **Website**: grabber.lk | **API**: api.grabber.lk  
> **Apps**: Grabber · Grabber Provider · Grabber Admin  
> **Stack**: Laravel 11 · PostgreSQL 16 · Supabase · Next.js 15 App Router · Flutter 3.x  
> **Verticals**: Stays · Vehicles · Taxi · Events · Experiences · Properties · Social · SME · Flash Deals  
> **Payments**: Cash (to Provider/Grabber) · Bank Transfer (to Grabber only) · Card (WebxPay)

---

## CRITICAL: PAYMENT STRUCTURE (READ FIRST)

```
╔══════════════════════════════════════════════════════════════════════╗
║                  GRABBER PAYMENT MODEL — FINAL                       ║
╠══════════════════════════════════════════════════════════════════════╣
║                                                                      ║
║  METHOD 1 — CARD (WebxPay Gateway)                                   ║
║    Customer pays → WebxPay → Grabber receives → commission deducted  ║
║    → Provider wallet credited                                         ║
║    3% optional handling fee (admin toggle)                            ║
║                                                                      ║
║  METHOD 2 — BANK TRANSFER (to Grabber ONLY)                          ║
║    Customer transfers to GRABBER bank account (never provider)        ║
║    Uses booking_ref as transfer description                           ║
║    Accounting dept confirms receipt in admin → booking activated      ║
║    Commission deducted → Provider wallet credited                     ║
║    48-hour transfer window or booking auto-cancels                    ║
║                                                                      ║
║  METHOD 3A — CASH → PROVIDER DIRECTLY                                ║
║    Taxi: customer pays driver at ride end → driver marks "cash paid"  ║
║    Stays/Experiences (if provider accepts): customer pays at property  ║
║    Provider marks booking "cash received" in Grabber Provider app     ║
║    Platform invoices provider for commission monthly via WebxPay      ║
║    Provider must have commission float held (security deposit)        ║
║                                                                      ║
║  METHOD 3B — CASH → GRABBER OFFICE/AGENT                             ║
║    Customer goes to Grabber office or authorised agent                ║
║    Pays cash → agent issues numbered receipt                          ║
║    Agent enters receipt in admin system → booking activated           ║
║    Commission deducted → Provider wallet credited                     ║
║                                                                      ║
║  PROVIDER WALLET → BANK (Provider Withdrawal)                         ║
║    Provider requests payout → Admin processes → Bank transfer         ║
║    LKR 50 flat fee per withdrawal · Min LKR 5,000                    ║
║                                                                      ║
║  CUSTOMER WALLET: DOES NOT EXIST                                      ║
║  PEARL POINTS: Discount at checkout only (not cash)                   ║
╚══════════════════════════════════════════════════════════════════════╝
```

---

## TABLE OF CONTENTS

**PART A — PLATFORM SPECIFICATION**
1. [Platform Overview & Revenue Model](#part-a1)
2. [All 9 Verticals — Complete Logic](#part-a2)
3. [Payment Flows — All Scenarios](#part-a3)
4. [Pearl Points System](#part-a4)
5. [Provider Wallet & Payouts](#part-a5)
6. [Admin Departments](#part-a6)

**PART B — TECHNICAL SPECIFICATION**
7. [Architecture](#part-b7)
8. [Database Schema — Complete](#part-b8)
9. [Laravel API — All Routes & Services](#part-b9)
10. [Next.js Web App](#part-b10)
11. [Flutter Apps](#part-b11)
12. [Supabase Edge Functions](#part-b12)
13. [Google Maps Integration](#part-b13)
14. [AI Concierge & Chat](#part-b14)
15. [Security & Compliance](#part-b15)
16. [Notifications](#part-b16)

**PART C — IMPLEMENTATION PLAN**
17. [Development Environment Setup](#part-c17)
18. [Project Initialization — Commands](#part-c18)
19. [Sprint-by-Sprint Build Plan](#part-c19)
20. [Claude Code Prompts — Per Sprint](#part-c20)
21. [Testing Plan](#part-c21)
22. [Deployment Pipeline](#part-c22)
23. [Environment Variables — Complete](#part-c23)
24. [Admin Config Reference — Complete](#part-c24)
25. [Go-Live Checklist](#part-c25)

---

# PART A — PLATFORM SPECIFICATION

## A1. PLATFORM OVERVIEW & REVENUE MODEL

### Company & Apps
| Item | Value |
|------|-------|
| Company | Grabber Mobility Solutions Pvt Ltd |
| Website | grabber.lk |
| Customer App | Grabber |
| Provider App | Grabber Provider |
| Admin App | Grabber Admin |
| Support Email | support@grabber.lk |
| Finance Email | finance@grabber.lk |
| KYC Email | kyc@grabber.lk |

### Revenue Matrix
| Vertical | Commission | Monthly Fee | Notes |
|----------|-----------|-------------|-------|
| Stays | 12% | — | On booking excl. taxes |
| Vehicles | 10% | LKR 9,500/vehicle | Monthly per vehicle |
| Taxi | 15% | — | Per ride; cash rides billed monthly |
| Events | 8% | — | Per ticket sold |
| Experiences | 10% | LKR 29,500/month | Admin-adjustable per provider |
| Properties (sale) | 2% of sale | LKR 15,000 listing | Seller pays; 0.5% to buyer as Pearl Pts |
| Properties (rent) | 5% monthly | LKR 15,000 listing | Per booking |
| Social Premium | — | LKR 3,300/month | Chat + video + analytics |
| SME Premium | — | LKR 6,500/month | Products + features |
| Flash Deals | 12% per redemption | LKR 1,500/deal | Per deal posted |
| SME Appointments | 10% | — | When appointment system used |
| Bundles | 8% | — | Lower rate incentivises bundles |

---

## A2. ALL 9 VERTICALS — COMPLETE LOGIC

### STAYS
```
LISTING REQUIREMENTS:
  Provider role: provider_stays
  No monthly fee — commission only
  Listing types: Hotel, Villa, Guesthouse, Boutique, Eco Lodge, Apartment, Treehouse, Camping
  
PROVIDER DASHBOARD TABS (most feature-rich vertical):
  1. Property Setup — name, type, address, GPS pin, description (EN+SI+TA optional), Google Maps
  2. Rooms & Units — multiple room types, capacity, per-room pricing and amenities
  3. Pricing & Calendar — base price, date overrides, seasonal, early bird, last-minute, weekly/monthly discounts
  4. Transport Packages — airport transfer, city shuttle, bicycle hire, guide links (cross-vertical)
  5. Tax & Legal — VAT 18%, Service Charge 10%, TDL 1%, Tourism Board Reg No., Hotel classification
  6. Booking Settings — Instant Book vs Request to Book, cancellation policy (Flexible/Moderate/Strict/Non-Refundable)
  7. Earnings & Payouts — RevPAR, ADR, occupancy rate, commission breakdown, tax report PDF
  8. Guest CRM — guest history, repeat guest detection, blacklist, custom discount, review management
  9. Photos & Media — 50 photos max, cover selection, room tagging, virtual tour embed, video (premium)
  10. Promotions — Flash Deal posting, Featured Listing, provider coupon generation

SRI LANKA TAX CALCULATION:
  base_amount = room_rate × nights
  + service_charge (10%, if enabled)
  + VAT 18% (on base + service_charge)
  + TDL 1% (on base)
  = grand_total (customer pays via WebxPay/Bank/Cash)
  Commission taken on: base_amount ONLY (not taxes)
  Tax invoice PDF auto-generated on every confirmed booking

BOOKING TYPES:
  Instant Book: payment → confirmation immediate
  Request to Book: payment held → provider has 24h to accept/decline → if decline → full refund

CANCELLATION FEES:
  Flexible: Cancel 24h+ before → 100% refund
  Moderate: Cancel 5+ days → 100%; 1–4 days → 50%; <24h → 0%
  Strict: Cancel 7+ days → 50%; <7 days → 0%
  Non-Refundable: 0% refund at any time
  Fee goes: % stays with provider; remainder refunded via original payment method

ADDITIONAL FEATURES:
  Channel manager ready (iCal sync — future)
  Late checkout / early check-in add-ons (with optional charge)
  Extra person charges (per person per night above base capacity)
  Pet fee (per pet per night)
  Pre-arrival inspection checklist (provider marks room ready — timestamped)
  Guest review incentive: 50 Pearl Points awarded on review submission
  Seasonal price templates (admin sets national peak periods)
```

### VEHICLES (RENTALS)
```
SUBSCRIPTION: LKR 9,500/month PER VEHICLE via WebxPay recurring
  Vehicle goes live only after: subscription active + insurance uploaded + admin approved
  Lapse → all vehicles suspended (auto); provider notified

CATEGORIES: Economy, Comfort, SUV/4WD, Luxury, Van/Minibus, Tuk-tuk, Motorbike, Bicycle, EV, Campervan

DRIVER OPTIONS: Self-drive / With Driver / With Driver-Guide (links to Experiences)

DEPOSIT FLOW (revised for cash/bank):
  Card: total (rental + deposit) charged via WebxPay in one transaction
  Bank Transfer: customer transfers total (rental + deposit) to Grabber account
  Cash: customer pays full amount (rental + deposit) to provider at pickup
        Provider signs rental agreement and takes deposit in cash, holds it
        On return: provider refunds deposit cash directly if no damage
        If damage: provider notifies Grabber; Grabber invoices customer for damage amount
  Deposit escrow: platform tracks in escrow_holdings table regardless of method
  Auto-release: 48h after return (if card/bank — via refund; if cash — provider refunds)
  Damage claim window: 24h from return
  Platform holds: 1.5% deposit hold fee (non-refundable) on card/bank; 0% on cash

FLEET MANAGEMENT:
  Up to 500 vehicles per provider from one dashboard
  Bulk CSV import, availability calendar (all vehicles in one view)
  Maintenance alerts (service due, insurance expiry)
  GPS fleet tracking integration-ready (webhook from GPS device)
  Driver pool management (assign drivers to vehicles)
  Revenue analytics per vehicle + fleet total

RENTAL AGREEMENT: Auto-generated PDF on booking → both parties e-sign in app

CASH COMMISSION BILLING:
  Provider who accepts cash payments: monthly invoice from Grabber
  Invoice = sum of cash booking commissions for the month
  Provider pays invoice via WebxPay or bank transfer to Grabber
  If unpaid in 14 days → vehicles suspended
```

### TAXI
```
RIDE STATES:
  scheduled → searching → accepted → driver_arrived → in_transit → completing → completed
  [any] → cancelled | [any] → sos

PAYMENT METHODS FOR TAXI:
  Card: via WebxPay, customer pays at booking or ride end
  Cash: customer pays driver directly at ride end
        Driver marks ride as "cash paid" in Grabber Provider app
        Platform commission tracked → weekly/monthly driver commission invoice
        Driver must pay commission invoice via WebxPay or bank transfer to Grabber
        If unpaid → driver account suspended
  Bank Transfer: NOT available for taxi (real-time rides need instant payment)

DRIVER COMMISSION ON CASH RIDES:
  Platform generates weekly statement: [driver_name], [total cash rides], [commission owed = 15%]
  Sent to driver every Monday
  Driver pays within 7 days via WebxPay or bank transfer to Grabber
  Admin can view: cash commission outstanding per driver

FARE CALCULATION:
  base_fare + (km × per_km_rate) + (min × per_min_rate) × surge_multiplier
  Minimum fare: LKR 300 (platform_config)
  Surge: 1.0× – 3.0× (auto-algorithm + manual admin zones)

DRIVER SCORING (weekly recalculation):
  Score = rating(40%) + acceptance_rate(20%) + completion_rate(20%) + response_speed(10%) + hours(10%)
  Tiers: Bronze / Silver (+LKR2/km) / Gold (+LKR5/km, priority) / Diamond (+LKR8/km, priority, bonus pool)
  Admin can manually override tier with logged reason

DRIVER QUESTS (admin-created):
  Daily / Weekly / Peak-hour / Streak / Referral quest types
  Reward credited to driver wallet
  Examples: "Complete 8 rides → earn LKR 500", "5-day streak → LKR 200/day"

CORPORATE TAXI:
  Company account → employee rides billed to company
  Monthly WebxPay invoice to company
  5% corporate discount (admin-configurable per company)

ADDITIONAL: Scheduled rides (7 days ahead), Multi-stop (3 stops), Fare split, Lost property, Tipping, SOS, Accessibility mode, Baby seat option

CASH AGENT PAYMENTS:
  Grabber can designate "cash collection agents" (petrol stations, convenience stores)
  Provider or driver can remit cash commission at agent
  Agent records receipt in admin → commission cleared
```

### EVENTS
```
TICKET TYPES: General, Reserved Seat, VIP, Early Bird, Group (table of N), Free (RSVP), Donation-based
PAYMENT: All 3 methods available (card/bank/cash); cash only via Grabber office or agent
CANCELLATION: Full refund if event cancelled by organiser (automatic); customer cancellation per policy
PLATFORM COMMISSION: 8% of ticket revenue
QR SCANNER FEE: LKR 2,500/event (optional — for full QR entry/exit system)

QR SYSTEM:
  Signed JWT QR per ticket → PDF email + in-app view
  Offline-first Flutter gate scanner (validates JWT signature locally)
  Online: calls Supabase Edge Function for DB entry record
  Scan types: entry / exit
  Live attendance counter on organiser dashboard
  Export attendance CSV

WAITING LIST:
  When sold out → list opens automatically
  10 Pearl Points for joining (goodwill)
  Slot opens (cancellation) → first in list gets 4-hour window to purchase

EVENT TYPES: In-person / Virtual (stream URL) / Hybrid
BUNDLE: Link nearby Grabber stays to event (both earn commission on booking)
INSURANCE OPTION: At checkout (third-party integration)
RECURRING: Weekly/monthly event series support
```

### EXPERIENCES
```
SUBSCRIPTION: LKR 29,500/month (admin-adjustable per provider)
  Requires SLTDA licence upload + admin verification before going live
  Each listing needs individual admin approval

CATEGORIES: Day Tour, Multi-Day Tour, Adventure, Cultural, Wellness, Wildlife, Water Sports, Transfer, Photography, Food Tour, Workshop

PRICING: Per person / Per group (private) / Child price / Private tour rate
GROUP SIZE: min and max per booking slot
SCHEDULE: Multiple time slots per day, seasonal availability (months on/off)
WEATHER-DEPENDENT: Provider can cancel with 6-hour notice → auto full refund (no commission)
ACCESSIBILITY: Wheelchair, min age, max weight, fitness level — shown on listing and searchable
CERTIFICATE: Post-experience digital certificate (cooking class, diving, etc.)
PACKAGE: Provider bundles multiple experiences into multi-day package (8% commission vs 10%)
CROSS-LINK: Experiences linked from Stays transport packages for seamless booking
```

### PROPERTIES
```
LISTING FEE: LKR 15,000 one-time (via WebxPay only — no cash for listing fees)
  Paid before listing goes live; non-refundable

OWNER REGISTRATION:
  Upload: Deed title photo + NIC front/back + Selfie with NIC
  All in Supabase private bucket (RLS: owner + admin only)
  Admin verifies → listing approved

BROKER REGISTRATION:
  For each property: deed photo OR bilateral signed consent PDF
  Consent PDF: downloadable template from Grabber → both parties sign → upload
  Admin KYC dept reviews → approves each listing individually
  Broker pays LKR 15,000 per property listing fee

SALE PROMO CODE FLOW:
  1. Owner/broker generates promo code (valid 30 days, one active at a time)
  2. Shares with buyer: code format GRAB-[6CHR]-[6CHR]
  3. Buyer enters code + agreed price on grabber.lk/properties/confirm-sale
  4. Platform notifies seller (push + email + WhatsApp): "Buyer confirming at LKR X. Confirm?"
  5. Seller has 48 hours to confirm
  6. On confirm: 2% commission invoice sent to seller via WebxPay
  7. On commission paid: buyer awarded 0.5% of sale price in Pearl Points; property marked SOLD

PROPERTY PAYMENT RULES:
  Listing fee: Card (WebxPay) or Bank Transfer to Grabber — no cash
  Sale commission (2%): Card (WebxPay) or Bank Transfer to Grabber — no cash
  Monthly rental commission (5%): Card (WebxPay) or Bank Transfer to Grabber

AI VALUATION: Provider gets estimated price range before setting their listing price
MORTGAGE CALCULATOR: Customer-facing on sale listings
ENQUIRY FORM: Customer sends enquiry → opens chat with provider
NEIGHBOURHOOD INSIGHTS: Google Places (if enabled) shows nearby schools/hospitals/transport
VIRTUAL TOUR: Matterport/YouTube 360° embed
```

### SOCIAL
```
STANDARD (Free): Text posts, photo posts (10 max), stories (24h photos), hashtags, location (district/city), likes, comments, shares, follow/unfollow, explore, report/block, community groups (up to 5)
  Earn Pearl Points: 5pts/post, 2pts/like received, max 80pts/day from social

PREMIUM (LKR 3,300/month — WebxPay card/bank transfer only):
  Short Videos (Reels): up to 60 seconds, basic editing, royalty-free music
  In-App & Web Chat: initiate chats (standard users can only receive), voice notes (60s), read receipts, message reactions, delete for everyone
  Chat AI Translation: translate any message to preferred language (DeepL/OpenAI)
  Voice Transcription: transcribe voice notes to text (OpenAI Whisper)
  Post Analytics: impressions, reach, engagement, best time to post
  Priority Explore: premium posts shown higher
  Story Enhancements: 3-min video stories, link sticker, poll sticker
  Social Commerce: tag Grabber listings in posts → viewer books → poster earns 2% of booking value as Pearl Points

MODERATION:
  AI scan on all uploads (OpenAI Vision)
  Keyword filter (admin-configurable word list)
  5 reports → auto-hide pending admin review
  Admin: warn / remove / suspend 7 days / ban
```

### SME DIRECTORY
```
STANDARD (Free): Business info, category, map pin, phone/WhatsApp/email, opening hours, 5 photos, social links, basic view count, customer reviews

PREMIUM (LKR 6,500/month — WebxPay card/bank):
  Product Catalogue: unlimited products, categories, 10 photos/product, variants, stock status, SKU, WhatsApp enquiry, bulk CSV import
  Enhanced Profile: 20 photos, 1 showcase video (2 min), custom banner, team section, certifications
  Promotions: 1 active promotion banner with CTA
  Flash Deals Integration: post deals from SME dashboard
  Analytics: views, WhatsApp clicks, call clicks, map requests, product views, source tracking
  Verified Business Badge: upload BR certificate → admin verifies → blue ✓ badge
  Appointment System: services with duration/price, availability calendar, customer booking, WebxPay payment, 10% commission
  Loyalty Stamp Card: digital stamp card (e.g., collect 10 → free coffee), QR-scan stamps
  Menu Upload (restaurants): photo menu with categories and dietary flags
  Table Reservation (restaurants): date/time/party size booking
  Halal/dietary certification: upload → admin verifies → filter badge
```

### FLASH DEALS (9th Vertical)
```
CONCEPT: Any approved provider posts a time-limited deal; LKR 1,500 listing fee per deal; 12% commission per redemption

DURATION: 2h / 6h / 12h / 24h / 48h (provider selects at posting)

POSTING:
  Provider selects: linked listing, deal type (% off / fixed / free add-on), discount value, max redemptions (up to admin ceiling), terms
  Pays LKR 1,500 via WebxPay at time of posting
  Goes live immediately (auto fraud check: same provider max 3 simultaneous deals)
  Admin can: pull any deal, mark as "Grabber Pick" (editorial boost), set max redemptions ceiling

CUSTOMER CLAIMS:
  Unique voucher code generated (one per customer per deal)
  4-hour voucher validity after claiming
  Customer uses code at checkout → discount applied → WebxPay charges deal price

GRABBER SPONSORED DEALS:
  Marketing dept creates deals on providers' behalf (with consent)
  Platform funds discount from marketing budget
  Used for: launch promotions, vertical campaigns
```

---

## A3. PAYMENT FLOWS — ALL SCENARIOS

### Flow 1: Card Payment (Standard Booking)
```
Customer checks out
↓
Checkout calculation:
  Subtotal:              LKR 10,000
  - Pearl Pts Discount:  LKR    500  (500 pts × LKR 1)
  + Card Handling 3%:    LKR    285  (if enabled)
  ═══════════════════════════════════
  WebxPay charges:       LKR  9,785

WebxPay payment confirmed → webhook → Laravel job:
  Payment record created
  Commission: LKR 10,000 × 12% = LKR 1,200 (on full booking value, pre-discount)
  Provider earns: LKR 10,000 - LKR 1,200 = LKR 8,800
  Platform funds pts discount: LKR 400 (0.80 × 500)
  Provider wallet credited after payout_hold_days (default 3)
  Pearl Points deducted from customer: -500 pts
  Pearl Points earned: 100 pts (1pt per LKR 100, on full value, not discount)
```

### Flow 2: Bank Transfer (to Grabber ONLY)
```
Customer selects Bank Transfer at checkout
↓
Platform shows:
  Grabber Bank Account: [Bank Name]
  Account Number: [GRABBER ACCOUNT NUMBER]
  Account Name: Grabber Mobility Solutions Pvt Ltd
  Branch: [Branch]
  Reference: GRAB-2026-ABCD  ← booking_ref (MANDATORY in transfer description)
  Transfer within: 48 hours or booking auto-cancels

Customer transfers to GRABBER account (never to provider)
↓
Accounting dept sees "Pending Bank Transfers" queue in admin dashboard
Matches transfer using booking_ref as reference
Accounting agent clicks "Confirm Receipt" + enters bank transaction reference
↓
System triggers same flow as card payment:
  Booking status → paid/confirmed
  Commission deducted
  Provider wallet credited (after hold period)
  Customer notified: booking confirmed
↓
If no transfer in 48 hours: booking auto-cancelled, listing availability restored
```

### Flow 3A: Cash — Provider Direct (Taxi, Stays, Experiences)
```
TAXI CASH FLOW:
  Ride completed
  Driver marks: "Payment method: Cash"
  Customer pays driver cash (no platform involvement at this moment)
  Ride marked: completed, cash_paid=true
  Platform records: commission owed by driver = LKR fare × 15%
  Weekly statement generated (every Monday):
    [Driver Name] — Cash rides: 47 — Total fares: LKR 35,000 — Commission owed: LKR 5,250
  Driver pays commission within 7 days (WebxPay or bank transfer to Grabber)
  If unpaid by day 8: driver account suspended until commission cleared
  Driver wallet: only digital rides credited; cash commissions tracked separately

STAYS/EXPERIENCES CASH FLOW (provider opted-in to accept cash):
  Provider must have "cash security deposit" paid to Grabber (equivalent to 2 weeks avg earnings)
  Customer books → selects cash → status: "confirmed_cash_pending"
  Customer arrives and pays provider in person at check-in / service start
  Provider taps "Mark Cash Received" in Grabber Provider app
  → Booking status: completed (if end of stay) or in_progress (start of multi-day)
  Provider earns: full booking value credited to wallet (gross)
  Platform deducts commission from next digital payment OR monthly commission invoice
  Monthly commission invoice sent on 1st of each month
  Provider pays within 14 days → if unpaid → listings suspended

CASH SECURITY DEPOSIT (from cash-accepting providers):
  Required before enabling cash acceptance
  Amount: 2 weeks estimated commission (admin sets)
  Paid to Grabber via WebxPay or bank transfer
  Held in escrow; returned when provider closes account (if no outstanding commission)
  Used to cover: non-payment of commission invoices
```

### Flow 3B: Cash — Grabber Office/Agent
```
Customer selects "Cash" at checkout
→ Status: awaiting_cash_payment
→ Customer gets: Agent location list, booking reference, amount, deadline (24h)

Customer goes to Grabber office or authorised cash agent
Pays cash → agent issues numbered receipt with booking_ref
Agent opens Grabber Admin → Support Tickets or Cash Payments module
Enters: booking_ref, cash amount received, receipt number
Clicks "Confirm Cash Receipt"
↓
System activates booking (same as card/bank confirmation)
Booking status: paid/confirmed
Commission deducted → Provider wallet credited
Customer notified: booking confirmed

GRABBER AUTHORISED CASH AGENTS:
  Registered in admin dashboard (name, address, contact, active/inactive)
  Agent has limited admin access (only: confirm cash receipt, view pending cash bookings)
  Role: cash_agent
  Daily cash reconciliation report: agent submits cash collected to Grabber office/bank
  Admin: cash_agent_settlements table tracks what each agent collects
```

### Flow 4: Vehicle Deposit (All Payment Methods)
```
Card: rental_fee + deposit charged in one WebxPay transaction
  Rental fee: → commission → provider wallet (after hold)
  Deposit: → escrow_holdings table (NOT provider wallet)
  Hold fee (1.5% of deposit): → platform revenue (non-refundable)

Bank Transfer: customer transfers rental + deposit to Grabber account
  Accounting team confirms and splits internally

Cash → Provider: customer pays rental + deposit to provider at pickup
  Provider signs rental agreement, holds deposit cash
  Damage claim: provider notifies Grabber, Grabber mediates
  Return without damage: provider refunds deposit cash directly
  Platform records: only rental commission is owed

AUTO-RELEASE (card/bank deposits):
  48h after booking_end_date → WebxPay API refund to customer
  If damage claim filed within 24h: manual admin review
  Admin approves damage claim → portion refunded to customer, remainder → provider wallet
  Admin rejects claim → full deposit refunded to customer
```

### Flow 5: Property Sale Commission
```
2% commission ONLY via WebxPay or Bank Transfer to Grabber
  Cash explicitly NOT allowed for property commission (risk management)

WebxPay: seller receives invoice link → pays online
Bank Transfer: seller transfers 2% to Grabber account → accounting confirms
On payment confirmed:
  1.5% → Grabber revenue
  0.5% → buyer Pearl Points (0.5% of sale price ÷ 1.0 = pts awarded)
  (e.g., LKR 5M sale → 0.5% = LKR 25,000 → 25,000 Pearl Points to buyer)
Property marked: SOLD
Both parties: PDF sale confirmation sent
```

### Flow 6: Refunds
```
Card payment refund:
  WebxPay API: partial or full refund to original card (3–5 business days)
  Card handling fee: NOT refunded (processing cost absorbed)
  Pearl Points used: restored to customer balance

Bank transfer refund:
  Accounting team initiates: bank transfer from Grabber account to customer's bank
  Requires: customer's bank account number (customer submits in refund request)
  Processing: 1–3 business days
  
Cash refund:
  Customer paid provider directly → provider refunds cash directly
  If provider disputes: Grabber mediates via Issue Resolution dept
  Grabber can penalise provider wallet (deduct from next digital earnings)
  Cash refund to Grabber office: customer visits office → Grabber issues cash refund

Provider wallet clawback on refund:
  Booking earnings not yet paid out → deducted from wallet balance
  Earnings already paid out → platform absorbs or deducts from next earnings
```

---

## A4. PEARL POINTS SYSTEM

```
EARNING:
  Booking completed:         1pt per LKR 100 spent (max 5,000 pts per booking)
  Review submitted:          50 pts (max 1 per booking, within 14 days)
  Referral (both parties):   500 pts each on referred user's first completed booking
  Social post:               5 pts (max 80 pts/day from social total)
  Received like on post:     2 pts (included in 80/day cap)
  Join waiting list:         10 pts (goodwill)
  Property sale (buyer):     0.5% of sale price as pts (no LKR cash — pts only)
  Admin bulk grant:          Admin can award pts to any user or segment with reason

REDEEMING (only at checkout — no cash withdrawal):
  Customer ticks "Use Pearl Points" at checkout
  Min to use: 100 pts · Max: 30% of booking subtotal value
  1pt = LKR 1.00 discount (face value to customer)
  Actual cost to platform: LKR 0.80 per pt (20% margin)
  WebxPay charges: subtotal - (pts × 1.00) + handling_fee (if card)
  Platform funds the LKR 0.80/pt from platform margin pool
  Points deducted immediately on payment initiation
  If payment fails: points fully restored
  If booking cancelled: points restored per cancellation policy

TIERS (based on CURRENT balance, not lifetime):
  Standard:  0–499 pts     — base earn rate
  Verified:  500–1,999 pts — +10% earn bonus
  Pro:       2,000–9,999  — +20% earn bonus + priority support badge
  Elite:     10,000+ pts  — +30% earn bonus + exclusive deals access

EXPIRY:
  12 months from date of each earning (FIFO — oldest pts spent first)
  Reminders: 30 days before + 7 days before expiry batch
  Admin can extend expiry for any user (logged to audit)
```

---

## A5. PROVIDER WALLET & PAYOUTS

```
WALLET CREDITS (automatic after payout_hold_days):
  Booking commission earned (net of platform commission)
  Driver quest rewards
  Admin adjustments (logged)

WALLET DEBITS:
  Refund clawback (if customer cancels)
  Admin penalty (after dispute resolution against provider)
  Commission invoice settlement (for cash-payment providers)

PAYOUT REQUEST FLOW:
  1. Provider: Wallet screen → Request Payout
  2. Enter amount (min LKR 5,000)
  3. Select method: Bank Transfer (only option — no cash payout from platform)
  4. Confirm bank details (pre-filled from profile)
  5. System: checks available balance ≥ requested; creates payout record (pending)
  6. Admin (Accounting dept): sees payout queue → transfers from Grabber account to provider's bank
  7. Admin: enters bank transaction reference → marks completed
  8. Provider: notified → wallet debited (amount + LKR 50 fee)

PAYOUT HOLD PERIOD:
  Default: 3 days after booking completion date
  Admin can set per provider: 0–30 days
  Admin can freeze wallet (during investigation): no payouts allowed

CASH COMMISSION INVOICING (for cash-accepting providers):
  Auto-generated on 1st of each month
  Sent via email + in-app notification + push
  Provider pays within 14 days: WebxPay or bank transfer to Grabber
  After 14 days unpaid: listings suspended (automatic)
  Admin can extend payment deadline (with reason, logged)
```

---

## A6. ADMIN DEPARTMENTS

### 6 Departments with Roles

| Dept | Role | Key Permissions |
|------|------|----------------|
| **Accounting** | `finance_admin` | All finance, payouts, reconciliation, cash management, escrow |
| **Marketing** | `marketing_admin` | Coupons, flash deals editorial, notifications, featured listings, analytics |
| **HR** | `hr_admin` | Staff management, roles, permissions, audit (admin actions only) |
| **Customer Service** | `customer_service_agent` | Tickets, basic refunds (≤LKR 25K), user lookup, booking lookup |
| **Provider Approval & KYC** | `kyc_officer` | Document review queues, approve/reject, signed URL access |
| **Issue Resolution** | `issue_resolver` | Disputes, fraud, full data access, override bookings, large refunds |

### Cash Management (Accounting Dept)
```
NEW IN v4 — CASH HANDLING ADMIN:

Bank Transfer Queue:
  All bookings with status: awaiting_bank_transfer
  Shows: booking_ref, amount, customer name, transfer deadline, time remaining
  Action: [Confirm Receipt] → enter bank transaction ref → booking activates
  Action: [Extend Deadline] → give customer more time (with note)
  Action: [Cancel] → auto-cancel, listing restored

Cash Agent Management:
  Register/deactivate cash agents (name, location, contact)
  View pending cash bookings at each agent location
  Daily reconciliation: agent submits cash collected
  Cash agent settlement tracking

Cash Commission Invoicing:
  View all pending cash commissions per provider (taxi + stays + experiences)
  Generate and send monthly invoices (1st of month, auto)
  Track payment status per invoice
  Flag overdue invoices (>14 days) → trigger auto-suspension
  Manual: extend deadline / waive commission (with reason, super_admin only)

Cash Booking Confirmations:
  Grabber office cash receipts entered here
  Receipt number, booking_ref, amount, agent who processed
  Daily cash balance report (total cash received at office)
```

---

# PART B — TECHNICAL SPECIFICATION

## B7. ARCHITECTURE

```
┌──────────────────────────────────────────────────────────────────────┐
│                         CLIENTS                                       │
│  ┌────────────────┐  ┌───────────────┐  ┌──────────────────────────┐ │
│  │  Next.js 15    │  │    Grabber    │  │  Grabber Provider        │ │
│  │  grabber.lk    │  │  Flutter App  │  │  Grabber Admin           │ │
│  │  (Vercel)      │  │  (iOS+Android)│  │  Flutter Apps            │ │
│  └───────┬────────┘  └──────┬────────┘  └────────────┬─────────────┘ │
└──────────┼─────────────────┼────────────────────────┼───────────────┘
           │                 │                        │
     ┌─────▼─────────────────▼────────────────────────▼─────┐
     │              SUPABASE REALTIME                         │
     │  Channels: taxi-ride · chat · booking · gods-view      │
     └────────────────────────────────────────────────────────┘
           │
     ┌─────▼──────────────────────────────────────────┐
     │           LARAVEL 11 API                        │
     │  api.grabber.lk · Railway.app                   │
     │  Sanctum Auth · Horizon Queues · Octane         │
     └──────┬────────────────────────┬─────────────────┘
            │                        │
   ┌────────▼───────┐  ┌─────────────▼───────────────────┐
   │ PostgreSQL 16  │  │   Supabase Edge Functions (10)   │
   │ (Supabase Pro) │  │   webxpay-webhook                │
   │ PostGIS        │  │   validate-ticket-qr             │
   │ pgvector       │  │   exchange-rate-sync             │
   │ pg_cron        │  │   notify-driver                  │
   └────────────────┘  │   chat-relay                     │
                       │   image-optimizer                │
   ┌────────────────┐  │   content-moderator              │
   │     Redis      │  │   generate-sitemap               │
   │  (Upstash TLS) │  │   otp-dispatcher                 │
   │ Cache·Queues   │  │   subscription-reminder          │
   └────────────────┘  └──────────────────────────────────┘
   
   ┌────────────────┐  ┌────────────────┐  ┌──────────────┐
   │ Supabase       │  │ Meilisearch    │  │External APIs │
   │ Storage        │  │ Full-text      │  │WebxPay·FCM   │
   │ media(public)  │  │ Search         │  │OpenAI·DeepL  │
   │ private-docs   │  │                │  │Twilio·Google │
   │ invoices       │  └────────────────┘  └──────────────┘
   └────────────────┘
```

---

## B8. DATABASE SCHEMA — COMPLETE

```sql
-- ═══════════════════════════════════════════════════════════════
-- EXTENSIONS
-- ═══════════════════════════════════════════════════════════════
CREATE EXTENSION IF NOT EXISTS postgis;
CREATE EXTENSION IF NOT EXISTS vector;
CREATE EXTENSION IF NOT EXISTS pg_cron;
CREATE EXTENSION IF NOT EXISTS pgcrypto;

-- ═══════════════════════════════════════════════════════════════
-- USERS & PROFILES
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE users (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  email            VARCHAR(255) UNIQUE,
  phone            VARCHAR(20) UNIQUE,
  password         VARCHAR(255) NOT NULL,
  role             VARCHAR(40) NOT NULL DEFAULT 'customer'
                     CHECK (role IN ('customer','provider_stays','provider_vehicles',
                                     'provider_events','provider_experiences',
                                     'provider_properties','property_broker',
                                     'provider_sme','driver','cash_agent',
                                     'admin','super_admin')),
  email_verified_at  TIMESTAMPTZ,
  phone_verified_at  TIMESTAMPTZ,
  two_factor_secret  VARCHAR(255),
  two_factor_confirmed_at TIMESTAMPTZ,
  last_login_at    TIMESTAMPTZ,
  created_at       TIMESTAMPTZ DEFAULT now(),
  updated_at       TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE profiles (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id           UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
  full_name         VARCHAR(255),
  avatar_url        TEXT,
  bio               TEXT,
  nic_number        VARCHAR(20),
  date_of_birth     DATE,
  gender            VARCHAR(10),
  address           TEXT,
  city              VARCHAR(100),
  district          VARCHAR(100),
  country           VARCHAR(100) DEFAULT 'LK',
  preferred_lang    VARCHAR(5) DEFAULT 'en',
  preferred_currency VARCHAR(3) DEFAULT 'LKR',
  bank_name         VARCHAR(100),
  bank_account_number VARCHAR(50),
  bank_account_name VARCHAR(255),
  bank_branch_code  VARCHAR(20),
  mobile_money_number VARCHAR(20),
  provider_tier     VARCHAR(20) DEFAULT 'standard'
                      CHECK (provider_tier IN ('standard','verified','pro','elite')),
  is_online         BOOLEAN DEFAULT false,
  last_lat          DOUBLE PRECISION,
  last_lng          DOUBLE PRECISION,
  last_seen_at      TIMESTAMPTZ,
  account_status    VARCHAR(20) DEFAULT 'active'
                      CHECK (account_status IN ('active','suspended','banned')),
  social_tier       VARCHAR(20) DEFAULT 'standard'
                      CHECK (social_tier IN ('standard','premium')),
  referral_code     VARCHAR(20) UNIQUE,
  accepts_cash      BOOLEAN DEFAULT false,   -- Provider opted into cash payments
  cash_security_deposit_paid BOOLEAN DEFAULT false,
  created_at        TIMESTAMPTZ DEFAULT now(),
  updated_at        TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- PROVIDER WALLET (PROVIDERS ONLY)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE provider_wallets (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id       UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
  balance           NUMERIC(14,2) DEFAULT 0.00,
  on_hold           NUMERIC(14,2) DEFAULT 0.00,
  lifetime_earnings NUMERIC(14,2) DEFAULT 0.00,
  lifetime_payouts  NUMERIC(14,2) DEFAULT 0.00,
  cash_commission_outstanding NUMERIC(12,2) DEFAULT 0.00,
  currency          VARCHAR(3) DEFAULT 'LKR',
  is_frozen         BOOLEAN DEFAULT false,
  payout_hold_days  INT DEFAULT 3,
  updated_at        TIMESTAMPTZ DEFAULT now(),
  CONSTRAINT balance_non_negative CHECK (balance >= 0)
);

CREATE TABLE provider_wallet_transactions (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id  UUID NOT NULL REFERENCES users(id),
  type         VARCHAR(40) NOT NULL
                 CHECK (type IN ('booking_credit','refund_debit','cancellation_credit',
                                  'cancellation_debit','penalty_debit','quest_credit',
                                  'adjustment_credit','adjustment_debit','payout_debit',
                                  'commission_invoice_debit')),
  amount       NUMERIC(14,2) NOT NULL,
  balance_after NUMERIC(14,2) NOT NULL,
  booking_id   UUID,
  payout_id    UUID,
  description  TEXT,
  admin_note   TEXT,
  created_by_admin UUID REFERENCES users(id),
  created_at   TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- PEARL POINTS (CUSTOMERS — NO WALLET)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE pearl_points_balances (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id        UUID NOT NULL REFERENCES users(id) ON DELETE CASCADE UNIQUE,
  balance        INT DEFAULT 0,
  lifetime_earned INT DEFAULT 0,
  lifetime_spent INT DEFAULT 0,
  tier           VARCHAR(20) DEFAULT 'standard',
  updated_at     TIMESTAMPTZ DEFAULT now(),
  CONSTRAINT pts_non_negative CHECK (balance >= 0)
);

CREATE TABLE pearl_points_transactions (
  id           UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id      UUID NOT NULL REFERENCES users(id),
  type         VARCHAR(30) NOT NULL
                 CHECK (type IN ('earn_booking','earn_review','earn_referral',
                                  'earn_social','earn_property_cashback',
                                  'earn_waiting_list','earn_admin_grant',
                                  'redeem_booking','redeem_cancelled',
                                  'expire','adjust')),
  points       INT NOT NULL,
  balance_after INT NOT NULL,
  booking_id   UUID,
  description  TEXT,
  expires_at   TIMESTAMPTZ,
  created_at   TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- PAYMENTS (ALL CUSTOMER TRANSACTIONS)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE payments (
  id                     UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id             UUID,
  property_sale_id       UUID,
  subscription_id        UUID,
  flash_deal_id          UUID,
  payer_id               UUID NOT NULL REFERENCES users(id),
  payment_method         VARCHAR(20) NOT NULL
                           CHECK (payment_method IN ('card','bank_transfer',
                                                      'cash_agent','cash_provider')),
  gateway                VARCHAR(20) DEFAULT 'webxpay',
  gateway_ref            VARCHAR(255) UNIQUE,
  gateway_payload        JSONB,
  amount                 NUMERIC(12,2) NOT NULL,
  handling_fee           NUMERIC(10,2) DEFAULT 0,
  handling_fee_rate      NUMERIC(5,4) DEFAULT 0,
  currency               VARCHAR(3) DEFAULT 'LKR',
  type                   VARCHAR(30)
                           CHECK (type IN ('booking','deposit','property_commission',
                                           'subscription','flash_deal_listing',
                                           'refund','commission_invoice',
                                           'cash_security_deposit')),
  status                 VARCHAR(30) DEFAULT 'pending'
                           CHECK (status IN ('pending','awaiting_bank_transfer',
                                             'awaiting_cash','completed','failed',
                                             'refunded','partially_refunded')),
  refunded_amount        NUMERIC(12,2) DEFAULT 0,
  bank_transfer_ref      VARCHAR(100),
  bank_transfer_deadline TIMESTAMPTZ,
  cash_agent_id          UUID REFERENCES users(id),
  cash_receipt_number    VARCHAR(50),
  cash_deadline          TIMESTAMPTZ,
  confirmed_by           UUID REFERENCES users(id),
  confirmed_at           TIMESTAMPTZ,
  processed_at           TIMESTAMPTZ,
  created_at             TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- ESCROW HOLDINGS (DEPOSITS)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE escrow_holdings (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id       UUID,
  payment_id       UUID NOT NULL REFERENCES payments(id),
  payer_id         UUID NOT NULL REFERENCES users(id),
  beneficiary_id   UUID REFERENCES users(id),
  amount           NUMERIC(12,2) NOT NULL,
  hold_fee         NUMERIC(10,2) DEFAULT 0,
  currency         VARCHAR(3) DEFAULT 'LKR',
  type             VARCHAR(30)
                     CHECK (type IN ('vehicle_deposit','property_commission')),
  cash_held        BOOLEAN DEFAULT false,  -- True if cash (provider holds physically)
  status           VARCHAR(30) DEFAULT 'held'
                     CHECK (status IN ('held','releasing','released_refund',
                                       'released_partial','dispute','forfeited')),
  auto_release_at  TIMESTAMPTZ NOT NULL,
  released_at      TIMESTAMPTZ,
  release_amount   NUMERIC(12,2),
  forfeit_amount   NUMERIC(12,2),
  admin_id         UUID REFERENCES users(id),
  admin_note       TEXT,
  created_at       TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- PAYOUTS (PROVIDER → BANK)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE payouts (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id    UUID NOT NULL REFERENCES users(id),
  amount         NUMERIC(12,2) NOT NULL,
  fee            NUMERIC(8,2) DEFAULT 50.00,
  net_amount     NUMERIC(12,2) NOT NULL,
  currency       VARCHAR(3) DEFAULT 'LKR',
  method         VARCHAR(20) DEFAULT 'bank_transfer',
  bank_name      VARCHAR(100),
  account_number VARCHAR(50),
  account_name   VARCHAR(255),
  branch_code    VARCHAR(20),
  status         VARCHAR(20) DEFAULT 'pending'
                   CHECK (status IN ('pending','processing','completed','failed','cancelled')),
  admin_ref      VARCHAR(255),
  processed_by   UUID REFERENCES users(id),
  processed_at   TIMESTAMPTZ,
  failure_reason TEXT,
  created_at     TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- CASH COMMISSION INVOICES (for cash-accepting providers)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE cash_commission_invoices (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  invoice_ref     VARCHAR(20) UNIQUE NOT NULL,
  provider_id     UUID NOT NULL REFERENCES users(id),
  period_start    DATE NOT NULL,
  period_end      DATE NOT NULL,
  total_cash_fares NUMERIC(12,2) NOT NULL,
  commission_rate  NUMERIC(5,4) NOT NULL,
  commission_due   NUMERIC(12,2) NOT NULL,
  status          VARCHAR(20) DEFAULT 'pending'
                    CHECK (status IN ('pending','paid','overdue','waived','disputed')),
  due_date        DATE NOT NULL,
  payment_ref     VARCHAR(255),
  paid_at         TIMESTAMPTZ,
  suspension_triggered BOOLEAN DEFAULT false,
  created_at      TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- BOOKINGS (UNIFIED)
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE bookings (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_ref          VARCHAR(20) UNIQUE NOT NULL,  -- GRAB-2026-XXXX
  customer_id          UUID NOT NULL REFERENCES users(id),
  provider_id          UUID NOT NULL REFERENCES users(id),
  vertical             VARCHAR(20) NOT NULL
                         CHECK (vertical IN ('stays','vehicles','taxi','events',
                                             'experiences','properties','sme',
                                             'flash_deal','bundle')),
  listing_id           UUID NOT NULL,
  listing_snapshot     JSONB NOT NULL,
  bundle_id            UUID,
  check_in             TIMESTAMPTZ,
  check_out            TIMESTAMPTZ,
  quantity             INT DEFAULT 1,
  subtotal             NUMERIC(12,2) NOT NULL,
  deposit_amount       NUMERIC(12,2) DEFAULT 0,
  tax_breakdown        JSONB DEFAULT '{}',
  total_taxes          NUMERIC(12,2) DEFAULT 0,
  coupon_id            UUID,
  coupon_discount      NUMERIC(12,2) DEFAULT 0,
  pearl_points_used    INT DEFAULT 0,
  pearl_points_discount NUMERIC(12,2) DEFAULT 0,
  total_charge         NUMERIC(12,2) NOT NULL,
  handling_fee         NUMERIC(10,2) DEFAULT 0,
  platform_commission  NUMERIC(12,2) NOT NULL,
  commission_rate      NUMERIC(5,4) NOT NULL,
  provider_earns       NUMERIC(12,2) NOT NULL,
  pearl_points_earned  INT DEFAULT 0,
  payment_method       VARCHAR(30) NOT NULL
                         CHECK (payment_method IN ('card','bank_transfer',
                                                    'cash_provider','cash_agent',
                                                    'card_with_points','card_with_coupon',
                                                    'corporate_account')),
  payment_status       VARCHAR(30) DEFAULT 'pending'
                         CHECK (payment_status IN ('pending','awaiting_bank_transfer',
                                                    'awaiting_cash','paid','failed',
                                                    'refunded','partially_refunded')),
  payment_ref          VARCHAR(255) UNIQUE,
  booking_type         VARCHAR(20) DEFAULT 'instant'
                         CHECK (booking_type IN ('instant','request')),
  status               VARCHAR(30) DEFAULT 'pending'
                         CHECK (status IN ('pending','awaiting_payment',
                                           'awaiting_approval','confirmed',
                                           'cash_pending','in_progress',
                                           'completed','provider_cancelled',
                                           'customer_cancelled','disputed',
                                           'refunded','no_show')),
  cancellation_policy  VARCHAR(20),
  cancellation_reason  TEXT,
  cancelled_by         VARCHAR(20),
  cancelled_at         TIMESTAMPTZ,
  payout_hold_until    TIMESTAMPTZ,
  provider_credited_at TIMESTAMPTZ,
  special_requests     TEXT,
  modification_count   INT DEFAULT 0,
  is_cash_booking      BOOLEAN DEFAULT false,
  cash_commission_invoice_id UUID REFERENCES cash_commission_invoices(id),
  admin_notes          TEXT,
  created_at           TIMESTAMPTZ DEFAULT now(),
  updated_at           TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- ALL VERTICAL TABLES
-- ═══════════════════════════════════════════════════════════════

CREATE TABLE stays (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id       UUID NOT NULL REFERENCES users(id),
  title             VARCHAR(255) NOT NULL,
  slug              VARCHAR(255) UNIQUE NOT NULL,
  description       TEXT,
  type              VARCHAR(30),
  address           TEXT,
  city              VARCHAR(100),
  district          VARCHAR(100),
  lat               DOUBLE PRECISION,
  lng               DOUBLE PRECISION,
  google_maps_url   TEXT,
  google_place_id   VARCHAR(255),
  google_maps_embed TEXT,
  google_place_data JSONB,
  price_per_night   NUMERIC(10,2) NOT NULL,
  currency          VARCHAR(3) DEFAULT 'LKR',
  max_guests        INT DEFAULT 2,
  bedrooms          INT DEFAULT 1,
  bathrooms         INT DEFAULT 1,
  amenities         TEXT[] DEFAULT '{}',
  images            TEXT[] DEFAULT '{}',
  thumbnail_url     TEXT,
  min_stay_nights   INT DEFAULT 1,
  cancellation_policy VARCHAR(20) DEFAULT 'flexible',
  booking_type      VARCHAR(20) DEFAULT 'instant',
  tax_config        JSONB DEFAULT '{"vat_enabled":false,"service_charge_enabled":false,"tdl_enabled":false}',
  tourism_board_reg VARCHAR(100),
  vat_reg_number    VARCHAR(100),
  hotel_classification VARCHAR(10),
  transport_packages JSONB DEFAULT '[]',
  is_featured       BOOLEAN DEFAULT false,
  is_pearlhub_pick  BOOLEAN DEFAULT false,
  status            VARCHAR(20) DEFAULT 'pending',
  avg_rating        NUMERIC(3,2) DEFAULT 0.00,
  total_reviews     INT DEFAULT 0,
  view_count        INT DEFAULT 0,
  search_vector     TSVECTOR GENERATED ALWAYS AS (
                      to_tsvector('english', coalesce(title,'') || ' ' ||
                        coalesce(description,'') || ' ' || coalesce(city,''))
                    ) STORED,
  created_at        TIMESTAMPTZ DEFAULT now(),
  updated_at        TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE stay_availability (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  stay_id        UUID NOT NULL REFERENCES stays(id) ON DELETE CASCADE,
  date           DATE NOT NULL,
  is_blocked     BOOLEAN DEFAULT false,
  price_override NUMERIC(10,2),
  UNIQUE(stay_id, date)
);

CREATE TABLE vehicles (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id     UUID NOT NULL REFERENCES users(id),
  fleet_id        UUID,
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) UNIQUE NOT NULL,
  description     TEXT,
  make            VARCHAR(100),
  model           VARCHAR(100),
  year            SMALLINT,
  type            VARCHAR(30),
  transmission    VARCHAR(20),
  fuel_type       VARCHAR(20),
  seats           INT DEFAULT 4,
  price_per_day   NUMERIC(10,2) NOT NULL,
  currency        VARCHAR(3) DEFAULT 'LKR',
  deposit_amount  NUMERIC(10,2) DEFAULT 0,
  deposit_refundable BOOLEAN DEFAULT true,
  fuel_policy     VARCHAR(20) DEFAULT 'full_to_full'
                    CHECK (fuel_policy IN ('full_to_full','empty_to_empty','full_to_empty')),
  km_limit_per_day INT,
  km_excess_rate  NUMERIC(8,2),
  with_driver     BOOLEAN DEFAULT false,
  driver_name     VARCHAR(255),
  driver_phone    VARCHAR(20),
  address         TEXT,
  city            VARCHAR(100),
  lat             DOUBLE PRECISION,
  lng             DOUBLE PRECISION,
  google_maps_url TEXT,
  images          TEXT[] DEFAULT '{}',
  thumbnail_url   TEXT,
  features        TEXT[] DEFAULT '{}',
  insurance_expiry DATE,
  insurance_doc_url TEXT,
  license_plate   VARCHAR(20),
  accepts_cash    BOOLEAN DEFAULT false,
  status          VARCHAR(20) DEFAULT 'pending',
  avg_rating      NUMERIC(3,2) DEFAULT 0.00,
  search_vector   TSVECTOR GENERATED ALWAYS AS (
                    to_tsvector('english', coalesce(title,'') || ' ' ||
                      coalesce(make,'') || ' ' || coalesce(model,''))
                  ) STORED,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE vehicle_fleets (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id   UUID NOT NULL REFERENCES users(id),
  fleet_name    VARCHAR(255) NOT NULL,
  total_vehicles INT DEFAULT 0,
  created_at    TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE taxi_categories (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  name          VARCHAR(100) NOT NULL,
  icon_url      TEXT,
  base_fare     NUMERIC(8,2) NOT NULL,
  per_km_rate   NUMERIC(6,2) NOT NULL,
  per_min_rate  NUMERIC(6,2) NOT NULL,
  min_fare      NUMERIC(8,2) NOT NULL,
  max_capacity  INT DEFAULT 4,
  surge_enabled BOOLEAN DEFAULT true,
  active        BOOLEAN DEFAULT true,
  created_at    TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE taxi_rides (
  id                      UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  customer_id             UUID NOT NULL REFERENCES users(id),
  driver_id               UUID REFERENCES users(id),
  category_id             UUID REFERENCES taxi_categories(id),
  pickup_address          TEXT NOT NULL,
  pickup_lat              DOUBLE PRECISION NOT NULL,
  pickup_lng              DOUBLE PRECISION NOT NULL,
  dropoff_address         TEXT NOT NULL,
  dropoff_lat             DOUBLE PRECISION NOT NULL,
  dropoff_lng             DOUBLE PRECISION NOT NULL,
  current_lat             DOUBLE PRECISION,
  current_lng             DOUBLE PRECISION,
  stops                   JSONB DEFAULT '[]',
  estimated_distance_km   NUMERIC(8,2),
  estimated_duration_min  INT,
  estimated_fare          NUMERIC(10,2),
  final_fare              NUMERIC(10,2),
  surge_multiplier        NUMERIC(4,2) DEFAULT 1.0,
  status                  VARCHAR(30) NOT NULL DEFAULT 'searching',
  payment_method          VARCHAR(20) DEFAULT 'card'
                            CHECK (payment_method IN ('card','cash')),
  cash_paid               BOOLEAN DEFAULT false,
  cash_commission_amount  NUMERIC(10,2),
  payment_ref             VARCHAR(255),
  driver_rating           SMALLINT CHECK (driver_rating BETWEEN 1 AND 5),
  customer_rating         SMALLINT CHECK (customer_rating BETWEEN 1 AND 5),
  tip_amount              NUMERIC(8,2) DEFAULT 0,
  tip_payment_ref         VARCHAR(255),
  is_scheduled            BOOLEAN DEFAULT false,
  scheduled_at            TIMESTAMPTZ,
  accessibility_required  BOOLEAN DEFAULT false,
  baby_seat_required      BOOLEAN DEFAULT false,
  accepted_at             TIMESTAMPTZ,
  arrived_at              TIMESTAMPTZ,
  started_at              TIMESTAMPTZ,
  completed_at            TIMESTAMPTZ,
  sos_triggered_at        TIMESTAMPTZ,
  corporate_account_id    UUID,
  created_at              TIMESTAMPTZ DEFAULT now(),
  updated_at              TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE events (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id     UUID NOT NULL REFERENCES users(id),
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) UNIQUE NOT NULL,
  description     TEXT,
  category        VARCHAR(50),
  venue_name      VARCHAR(255),
  address         TEXT,
  city            VARCHAR(100),
  lat             DOUBLE PRECISION,
  lng             DOUBLE PRECISION,
  google_maps_url TEXT,
  google_maps_embed TEXT,
  event_type      VARCHAR(20) DEFAULT 'in_person'
                    CHECK (event_type IN ('in_person','virtual','hybrid')),
  stream_url      TEXT,
  starts_at       TIMESTAMPTZ NOT NULL,
  ends_at         TIMESTAMPTZ,
  images          TEXT[] DEFAULT '{}',
  thumbnail_url   TEXT,
  has_seat_map    BOOLEAN DEFAULT false,
  qr_system_enabled BOOLEAN DEFAULT false,
  total_capacity  INT,
  available_seats INT,
  status          VARCHAR(20) DEFAULT 'pending',
  avg_rating      NUMERIC(3,2) DEFAULT 0.00,
  search_vector   TSVECTOR GENERATED ALWAYS AS (
                    to_tsvector('english', coalesce(title,'') || ' ' ||
                      coalesce(description,'') || ' ' || coalesce(venue_name,''))
                  ) STORED,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE event_ticket_tiers (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_id        UUID NOT NULL REFERENCES events(id) ON DELETE CASCADE,
  name            VARCHAR(100) NOT NULL,
  price           NUMERIC(10,2) NOT NULL,
  currency        VARCHAR(3) DEFAULT 'LKR',
  total_seats     INT NOT NULL,
  available_seats INT NOT NULL,
  sale_ends_at    TIMESTAMPTZ,
  created_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE event_tickets (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  booking_id     UUID NOT NULL REFERENCES bookings(id),
  tier_id        UUID NOT NULL REFERENCES event_ticket_tiers(id),
  attendee_id    UUID NOT NULL REFERENCES users(id),
  qr_payload     TEXT NOT NULL,
  pdf_url        TEXT,
  is_void        BOOLEAN DEFAULT false,
  created_at     TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE ticket_scans (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  ticket_id   UUID NOT NULL REFERENCES event_tickets(id),
  event_id    UUID NOT NULL REFERENCES events(id),
  scan_type   VARCHAR(10) CHECK (scan_type IN ('entry','exit')),
  staff_id    UUID REFERENCES users(id),
  gate_id     VARCHAR(50),
  is_valid    BOOLEAN NOT NULL,
  rejection_reason TEXT,
  scanned_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE event_staff (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  event_id    UUID NOT NULL REFERENCES events(id),
  user_id     UUID REFERENCES users(id),
  staff_name  VARCHAR(255),
  staff_pin   VARCHAR(255) NOT NULL,
  role        VARCHAR(30) DEFAULT 'gate_scanner',
  gates       TEXT[] DEFAULT '{}',
  active      BOOLEAN DEFAULT true,
  expires_at  TIMESTAMPTZ,
  created_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE seat_holds (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  tier_id     UUID NOT NULL REFERENCES event_ticket_tiers(id),
  user_id     UUID NOT NULL REFERENCES users(id),
  quantity    INT DEFAULT 1,
  session_id  VARCHAR(255) NOT NULL,
  expires_at  TIMESTAMPTZ DEFAULT (now() + INTERVAL '15 minutes'),
  created_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE experiences (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id         UUID NOT NULL REFERENCES users(id),
  title               VARCHAR(255) NOT NULL,
  slug                VARCHAR(255) UNIQUE NOT NULL,
  description         TEXT,
  category            VARCHAR(50) NOT NULL,
  duration_hours      NUMERIC(5,2),
  duration_days       INT DEFAULT 0,
  min_group_size      INT DEFAULT 1,
  max_group_size      INT DEFAULT 20,
  difficulty          VARCHAR(20),
  languages           TEXT[] DEFAULT '{en}',
  includes            TEXT[] DEFAULT '{}',
  excludes            TEXT[] DEFAULT '{}',
  meeting_point       TEXT,
  meeting_lat         DOUBLE PRECISION,
  meeting_lng         DOUBLE PRECISION,
  google_maps_url     TEXT,
  price_per_person    NUMERIC(10,2) NOT NULL,
  price_child         NUMERIC(10,2),
  price_private       NUMERIC(10,2),
  currency            VARCHAR(3) DEFAULT 'LKR',
  sltda_licence_url   TEXT,
  weather_dependent   BOOLEAN DEFAULT false,
  min_age             INT,
  max_weight_kg       INT,
  wheelchair_accessible BOOLEAN DEFAULT false,
  min_notice_hours    INT DEFAULT 0,
  seasonal_start_month INT,
  seasonal_end_month  INT,
  safety_info         TEXT,
  cancellation_policy VARCHAR(20) DEFAULT 'moderate',
  images              TEXT[] DEFAULT '{}',
  thumbnail_url       TEXT,
  is_featured         BOOLEAN DEFAULT false,
  status              VARCHAR(20) DEFAULT 'pending',
  avg_rating          NUMERIC(3,2) DEFAULT 0.00,
  total_reviews       INT DEFAULT 0,
  search_vector       TSVECTOR GENERATED ALWAYS AS (
                        to_tsvector('english', coalesce(title,'') || ' ' ||
                          coalesce(description,'') || ' ' || coalesce(category,''))
                      ) STORED,
  created_at          TIMESTAMPTZ DEFAULT now(),
  updated_at          TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE experience_schedules (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  experience_id   UUID NOT NULL REFERENCES experiences(id) ON DELETE CASCADE,
  date            DATE NOT NULL,
  start_time      TIME NOT NULL,
  available_slots INT NOT NULL,
  booked_slots    INT DEFAULT 0,
  is_blocked      BOOLEAN DEFAULT false,
  price_override  NUMERIC(10,2),
  created_at      TIMESTAMPTZ DEFAULT now(),
  UNIQUE(experience_id, date, start_time)
);

CREATE TABLE properties (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id     UUID NOT NULL REFERENCES users(id),
  broker_id       UUID REFERENCES users(id),
  title           VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) UNIQUE NOT NULL,
  description     TEXT,
  listing_type    VARCHAR(10) CHECK (listing_type IN ('sale','rent','lease')),
  property_type   VARCHAR(30),
  address         TEXT,
  city            VARCHAR(100),
  district        VARCHAR(100),
  lat             DOUBLE PRECISION,
  lng             DOUBLE PRECISION,
  google_maps_url TEXT,
  google_place_data JSONB,
  price           NUMERIC(14,2) NOT NULL,
  currency        VARCHAR(3) DEFAULT 'LKR',
  area_sqft       INT,
  bedrooms        INT,
  bathrooms       INT,
  floors          INT,
  year_built      SMALLINT,
  furnishing      VARCHAR(30),
  features        TEXT[] DEFAULT '{}',
  images          TEXT[] DEFAULT '{}',
  thumbnail_url   TEXT,
  agent_name      VARCHAR(255),
  agent_phone     VARCHAR(20),
  status          VARCHAR(20) DEFAULT 'pending'
                    CHECK (status IN ('pending','approved','rejected','sold','rented','archived','suspended')),
  search_vector   TSVECTOR GENERATED ALWAYS AS (
                    to_tsvector('english', coalesce(title,'') || ' ' ||
                      coalesce(city,'') || ' ' || coalesce(district,''))
                  ) STORED,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE property_deeds (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id         UUID NOT NULL REFERENCES properties(id),
  owner_id            UUID NOT NULL REFERENCES users(id),
  owner_full_name     VARCHAR(255) NOT NULL,
  owner_nic           VARCHAR(30),
  owner_company       VARCHAR(255),
  deed_file_url       TEXT NOT NULL,
  nic_front_url       TEXT,
  nic_back_url        TEXT,
  selfie_url          TEXT,
  verification_status VARCHAR(20) DEFAULT 'pending',
  admin_notes         TEXT,
  reviewed_by         UUID REFERENCES users(id),
  reviewed_at         TIMESTAMPTZ,
  uploaded_at         TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE broker_consents (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id         UUID NOT NULL REFERENCES properties(id),
  broker_id           UUID NOT NULL REFERENCES users(id),
  owner_name          VARCHAR(255) NOT NULL,
  owner_nic           VARCHAR(30),
  owner_mobile        VARCHAR(20),
  listing_purpose     VARCHAR(10),
  consent_file_url    TEXT NOT NULL,
  valid_until         TIMESTAMPTZ,
  verification_status VARCHAR(20) DEFAULT 'pending',
  admin_notes         TEXT,
  reviewed_by         UUID REFERENCES users(id),
  reviewed_at         TIMESTAMPTZ,
  uploaded_at         TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE property_sale_promos (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id   UUID NOT NULL REFERENCES properties(id),
  generated_by  UUID NOT NULL REFERENCES users(id),
  promo_code    VARCHAR(30) UNIQUE NOT NULL,
  status        VARCHAR(20) DEFAULT 'active',
  final_price   NUMERIC(14,2),
  used_by       UUID REFERENCES users(id),
  used_at       TIMESTAMPTZ,
  expires_at    TIMESTAMPTZ NOT NULL,
  created_at    TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE property_sales (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  property_id          UUID NOT NULL REFERENCES properties(id),
  promo_id             UUID NOT NULL REFERENCES property_sale_promos(id),
  seller_id            UUID NOT NULL REFERENCES users(id),
  buyer_id             UUID NOT NULL REFERENCES users(id),
  broker_id            UUID REFERENCES users(id),
  agreed_price         NUMERIC(14,2) NOT NULL,
  platform_commission  NUMERIC(12,2) NOT NULL,
  buyer_cashback_pts   INT NOT NULL,
  platform_net         NUMERIC(12,2) NOT NULL,
  commission_payment_ref VARCHAR(255),
  commission_status    VARCHAR(20) DEFAULT 'pending',
  pts_awarded          BOOLEAN DEFAULT false,
  status               VARCHAR(30) DEFAULT 'pending_seller_confirmation',
  seller_confirmed_at  TIMESTAMPTZ,
  completed_at         TIMESTAMPTZ,
  created_at           TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE social_posts (
  id             UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  user_id        UUID NOT NULL REFERENCES users(id),
  content        TEXT NOT NULL,
  images         TEXT[] DEFAULT '{}',
  video_url      TEXT,
  tags           TEXT[] DEFAULT '{}',
  location       VARCHAR(255),
  lat            DOUBLE PRECISION,
  lng            DOUBLE PRECISION,
  group_id       UUID,
  likes_count    INT DEFAULT 0,
  comments_count INT DEFAULT 0,
  is_premium_post BOOLEAN DEFAULT false,
  status         VARCHAR(20) DEFAULT 'active',
  created_at     TIMESTAMPTZ DEFAULT now(),
  updated_at     TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE social_post_likes (
  id       UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  post_id  UUID NOT NULL REFERENCES social_posts(id) ON DELETE CASCADE,
  user_id  UUID NOT NULL REFERENCES users(id),
  created_at TIMESTAMPTZ DEFAULT now(),
  UNIQUE(post_id, user_id)
);

CREATE TABLE social_comments (
  id        UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  post_id   UUID NOT NULL REFERENCES social_posts(id) ON DELETE CASCADE,
  user_id   UUID NOT NULL REFERENCES users(id),
  content   TEXT NOT NULL,
  parent_id UUID REFERENCES social_comments(id),
  created_at TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE conversations (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  context          VARCHAR(20) NOT NULL,
  participant_a    UUID NOT NULL REFERENCES users(id),
  participant_b    UUID NOT NULL REFERENCES users(id),
  booking_id       UUID REFERENCES bookings(id),
  taxi_ride_id     UUID REFERENCES taxi_rides(id),
  last_message_at  TIMESTAMPTZ,
  is_archived      BOOLEAN DEFAULT false,
  created_at       TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE chat_messages (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  conversation_id  UUID NOT NULL REFERENCES conversations(id) ON DELETE CASCADE,
  sender_id        UUID NOT NULL REFERENCES users(id),
  type             VARCHAR(20) DEFAULT 'text',
  content          TEXT,
  media_url        TEXT,
  duration_sec     INT,
  location_lat     DOUBLE PRECISION,
  location_lng     DOUBLE PRECISION,
  is_read          BOOLEAN DEFAULT false,
  read_at          TIMESTAMPTZ,
  is_deleted       BOOLEAN DEFAULT false,
  created_at       TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE chat_message_translations (
  id               UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  message_id       UUID NOT NULL REFERENCES chat_messages(id) ON DELETE CASCADE,
  from_locale      VARCHAR(5) NOT NULL,
  to_locale        VARCHAR(5) NOT NULL,
  translated_text  TEXT NOT NULL,
  created_at       TIMESTAMPTZ DEFAULT now(),
  UNIQUE(message_id, to_locale)
);

CREATE TABLE sme_profiles (
  id              UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  owner_id        UUID NOT NULL REFERENCES users(id),
  business_name   VARCHAR(255) NOT NULL,
  slug            VARCHAR(255) UNIQUE NOT NULL,
  description     TEXT,
  category        VARCHAR(50),
  address         TEXT,
  city            VARCHAR(100),
  lat             DOUBLE PRECISION,
  lng             DOUBLE PRECISION,
  google_maps_url TEXT,
  google_place_id VARCHAR(255),
  google_maps_embed TEXT,
  google_place_data JSONB,
  phone           VARCHAR(20),
  email           VARCHAR(255),
  website_url     TEXT,
  whatsapp        VARCHAR(20),
  logo_url        TEXT,
  images          TEXT[] DEFAULT '{}',
  opening_hours   JSONB DEFAULT '{}',
  social_links    JSONB DEFAULT '{}',
  is_premium      BOOLEAN DEFAULT false,
  is_verified     BOOLEAN DEFAULT false,
  accepts_appointments BOOLEAN DEFAULT false,
  avg_rating      NUMERIC(3,2) DEFAULT 0.00,
  total_reviews   INT DEFAULT 0,
  status          VARCHAR(20) DEFAULT 'pending',
  search_vector   TSVECTOR GENERATED ALWAYS AS (
                    to_tsvector('english', coalesce(business_name,'') || ' ' ||
                      coalesce(description,'') || ' ' || coalesce(category,''))
                  ) STORED,
  created_at      TIMESTAMPTZ DEFAULT now(),
  updated_at      TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE flash_deals (
  id                  UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id         UUID NOT NULL REFERENCES users(id),
  listing_id          UUID NOT NULL,
  listing_type        VARCHAR(20) NOT NULL,
  listing_snapshot    JSONB NOT NULL,
  title               VARCHAR(255) NOT NULL,
  description         TEXT,
  deal_type           VARCHAR(20),
  discount_value      NUMERIC(8,2) NOT NULL,
  original_price      NUMERIC(12,2) NOT NULL,
  deal_price          NUMERIC(12,2) NOT NULL,
  max_redemptions     INT NOT NULL,
  current_redemptions INT DEFAULT 0,
  duration_hours      INT NOT NULL,
  starts_at           TIMESTAMPTZ NOT NULL,
  expires_at          TIMESTAMPTZ NOT NULL,
  terms               TEXT,
  is_pearlhub_pick    BOOLEAN DEFAULT false,
  listing_payment_ref VARCHAR(255),
  status              VARCHAR(20) DEFAULT 'active',
  created_at          TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- SUBSCRIPTIONS
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE provider_subscriptions (
  id                   UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  provider_id          UUID NOT NULL REFERENCES users(id),
  vertical             VARCHAR(30) NOT NULL,
  monthly_amount       NUMERIC(10,2) NOT NULL,
  admin_override       BOOLEAN DEFAULT false,
  admin_override_note  TEXT,
  billing_day          INT NOT NULL,
  status               VARCHAR(20) DEFAULT 'active',
  current_period_start TIMESTAMPTZ,
  current_period_end   TIMESTAMPTZ,
  grace_period_ends_at TIMESTAMPTZ,
  next_billing_at      TIMESTAMPTZ,
  last_billed_at       TIMESTAMPTZ,
  cancelled_at         TIMESTAMPTZ,
  created_at           TIMESTAMPTZ DEFAULT now(),
  updated_at           TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- TAXI DRIVER MANAGEMENT
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE driver_scores (
  id                UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  driver_id         UUID NOT NULL REFERENCES users(id),
  period_start      DATE NOT NULL,
  period_end        DATE NOT NULL,
  total_rides       INT DEFAULT 0,
  completed_rides   INT DEFAULT 0,
  accepted_rides    INT DEFAULT 0,
  avg_rating        NUMERIC(3,2) DEFAULT 5.00,
  avg_response_sec  INT DEFAULT 0,
  online_minutes    INT DEFAULT 0,
  score             INT DEFAULT 0,
  tier              VARCHAR(20) DEFAULT 'bronze',
  bonus_earned      NUMERIC(10,2) DEFAULT 0,
  calculated_at     TIMESTAMPTZ DEFAULT now(),
  UNIQUE(driver_id, period_start)
);

CREATE TABLE driver_quests (
  id            UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  title         VARCHAR(255) NOT NULL,
  description   TEXT,
  quest_type    VARCHAR(30),
  target_metric VARCHAR(50),
  target_value  INT NOT NULL,
  reward_amount NUMERIC(10,2) NOT NULL,
  active        BOOLEAN DEFAULT true,
  valid_from    TIMESTAMPTZ,
  valid_until   TIMESTAMPTZ,
  created_at    TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- SUPPORT TICKETS
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE support_tickets (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  ticket_ref  VARCHAR(20) UNIQUE NOT NULL,
  user_id     UUID NOT NULL REFERENCES users(id),
  booking_id  UUID REFERENCES bookings(id),
  channel     VARCHAR(20) DEFAULT 'in_app',
  subject     TEXT,
  priority    VARCHAR(20) DEFAULT 'normal',
  status      VARCHAR(30) DEFAULT 'open',
  assigned_to UUID REFERENCES users(id),
  department  VARCHAR(50),
  resolved_at TIMESTAMPTZ,
  csat_score  SMALLINT,
  created_at  TIMESTAMPTZ DEFAULT now(),
  updated_at  TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- PLATFORM CONFIG & FEATURE FLAGS
-- ═══════════════════════════════════════════════════════════════
CREATE TABLE platform_config (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  category    VARCHAR(50) NOT NULL,
  key         VARCHAR(100) NOT NULL,
  value       TEXT NOT NULL,
  type        VARCHAR(20) DEFAULT 'string',
  label       VARCHAR(255),
  description TEXT,
  is_sensitive BOOLEAN DEFAULT false,
  updated_by  UUID REFERENCES users(id),
  updated_at  TIMESTAMPTZ DEFAULT now(),
  UNIQUE(category, key)
);

CREATE TABLE feature_flags (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  key         VARCHAR(100) UNIQUE NOT NULL,
  enabled     BOOLEAN DEFAULT false,
  description TEXT,
  updated_by  UUID REFERENCES users(id),
  updated_at  TIMESTAMPTZ DEFAULT now()
);

CREATE TABLE audit_log (
  id          UUID PRIMARY KEY DEFAULT gen_random_uuid(),
  admin_id    UUID NOT NULL REFERENCES users(id),
  action      VARCHAR(100) NOT NULL,
  entity_type VARCHAR(50),
  entity_id   UUID,
  before_val  JSONB,
  after_val   JSONB,
  ip_address  INET,
  created_at  TIMESTAMPTZ DEFAULT now()
);

-- ═══════════════════════════════════════════════════════════════
-- CRITICAL INDEXES
-- ═══════════════════════════════════════════════════════════════
CREATE INDEX stays_search_idx ON stays USING gin(search_vector);
CREATE INDEX vehicles_search_idx ON vehicles USING gin(search_vector);
CREATE INDEX events_search_idx ON events USING gin(search_vector);
CREATE INDEX experiences_search_idx ON experiences USING gin(search_vector);
CREATE INDEX properties_search_idx ON properties USING gin(search_vector);
CREATE INDEX sme_search_idx ON sme_profiles USING gin(search_vector);
CREATE INDEX bookings_customer_idx ON bookings(customer_id, created_at DESC);
CREATE INDEX bookings_provider_idx ON bookings(provider_id, created_at DESC);
CREATE INDEX bookings_payment_ref_idx ON bookings(payment_ref) WHERE payment_ref IS NOT NULL;
CREATE INDEX bookings_payout_hold_idx ON bookings(payout_hold_until) WHERE provider_credited_at IS NULL;
CREATE INDEX bookings_cash_pending_idx ON bookings(status) WHERE status = 'cash_pending';
CREATE INDEX bookings_bank_pending_idx ON bookings(payment_status, created_at) WHERE payment_status = 'awaiting_bank_transfer';
CREATE INDEX flash_deals_active_idx ON flash_deals(status, expires_at) WHERE status = 'active';
CREATE INDEX taxi_rides_driver_idx ON taxi_rides(driver_id, status);
CREATE INDEX cash_invoices_unpaid_idx ON cash_commission_invoices(provider_id, status) WHERE status IN ('pending','overdue');
CREATE INDEX payments_bank_pending_idx ON payments(status, bank_transfer_deadline) WHERE status = 'awaiting_bank_transfer';
CREATE INDEX profiles_driver_location ON profiles USING gist(ST_MakePoint(last_lng, last_lat)) WHERE is_online = true;

-- ═══════════════════════════════════════════════════════════════
-- pg_cron JOBS
-- ═══════════════════════════════════════════════════════════════
SELECT cron.schedule('clean-seat-holds',     '*/5 * * * *',     $$DELETE FROM seat_holds WHERE expires_at < now()$$);
SELECT cron.schedule('clean-otp-records',    '0 * * * *',        $$DELETE FROM otp_requests WHERE created_at < now() - INTERVAL '2 hours'$$);
SELECT cron.schedule('auto-cancel-bank',     '*/30 * * * *',     $$UPDATE bookings SET status='customer_cancelled' WHERE payment_status='awaiting_bank_transfer' AND created_at < now() - INTERVAL '48 hours'$$);
SELECT cron.schedule('auto-cancel-cash',     '*/30 * * * *',     $$UPDATE bookings SET status='customer_cancelled' WHERE status='awaiting_cash' AND created_at < now() - INTERVAL '24 hours'$$);
SELECT cron.schedule('expire-flash-deals',   '*/10 * * * *',     $$UPDATE flash_deals SET status='expired' WHERE expires_at < now() AND status='active'$$);
SELECT cron.schedule('auto-release-escrow',  '0 * * * *',        $$UPDATE escrow_holdings SET status='releasing' WHERE auto_release_at < now() AND status='held'$$);
```

---

# PART C — IMPLEMENTATION PLAN

## C17. DEVELOPMENT ENVIRONMENT SETUP

### Prerequisites
```bash
# Required on your machine:
Node.js 22.x (LTS)   → https://nodejs.org
PHP 8.3+             → https://php.net
Composer 2.x         → https://getcomposer.org
Flutter 3.22+        → https://flutter.dev
Dart 3.4+            → included with Flutter
Docker Desktop       → https://docker.com
Git                  → https://git-scm.com
VSCode               → https://code.visualstudio.com

# VSCode Extensions (install these first):
code --install-extension bmewburn.vscode-intelephense-client   # PHP
code --install-extension onecentlin.laravel-blade             # Blade
code --install-extension bradlc.vscode-tailwindcss            # Tailwind
code --install-extension Dart-Code.dart-code                  # Dart
code --install-extension Dart-Code.flutter                    # Flutter
code --install-extension ms-vscode.vscode-typescript-next     # TypeScript
code --install-extension dbaeumer.vscode-eslint               # ESLint
code --install-extension esbenp.prettier-vscode               # Prettier
code --install-extension cweijan.vscode-postgresql-client2    # PostgreSQL
code --install-extension redhat.vscode-yaml                   # YAML

# Claude Code (if using Claude Code terminal):
npm install -g @anthropic-ai/claude-code
claude  # to authenticate and start
```

---

## C18. PROJECT INITIALIZATION — COMMANDS

### Step 1: Create Monorepo
```bash
mkdir grabber && cd grabber
git init
git remote add origin https://github.com/YOUR_ORG/grabber.git

# Create root structure
mkdir -p {api,web,flutter,supabase,docs,.github/workflows}
touch .gitignore README.md

cat > .gitignore << 'EOF'
# Laravel
api/vendor/
api/.env
api/storage/logs/*
api/storage/framework/cache/*

# Next.js
web/.next/
web/node_modules/
web/.env.local

# Flutter
flutter/**/build/
flutter/**/.dart_tool/
flutter/**/android/local.properties
flutter/**/.flutter-plugins*

# Supabase
supabase/.env.local
supabase/functions/.env

# General
.DS_Store
Thumbs.db
EOF
```

### Step 2: Laravel API Setup
```bash
cd grabber
composer create-project laravel/laravel api
cd api

# Core packages
composer require \
  laravel/sanctum \
  laravel/reverb \
  laravel/horizon \
  laravel/octane \
  laravel/scout \
  spatie/laravel-permission \
  spatie/laravel-medialibrary \
  spatie/laravel-query-builder \
  spatie/laravel-activitylog \
  intervention/image-laravel \
  barryvdh/laravel-dompdf \
  openai-php/laravel \
  guzzlehttp/guzzle

# Dev packages  
composer require --dev \
  pestphp/pest \
  pestphp/pest-plugin-laravel \
  laravel/telescope \
  fakerphp/faker

# Publish config files
php artisan vendor:publish --provider="Laravel\Sanctum\SanctumServiceProvider"
php artisan vendor:publish --provider="Spatie\Permission\PermissionServiceProvider"
php artisan vendor:publish --provider="Laravel\Horizon\HorizonServiceProvider"
php artisan vendor:publish --tag=laravel-telescope

# Install Pest
php artisan pest:install

# Setup Octane
php artisan octane:install

# Create application key
php artisan key:generate
```

### Step 3: Next.js Web App Setup
```bash
cd ../web
npx create-next-app@latest . --typescript --tailwind --app --src-dir=false --import-alias="@/*"

# Dependencies
npm install \
  @supabase/supabase-js \
  @tanstack/react-query \
  zustand \
  react-hook-form \
  zod \
  @hookform/resolvers \
  next-intl \
  leaflet \
  react-leaflet \
  @types/leaflet \
  recharts \
  framer-motion \
  date-fns \
  pusher-js \
  lucide-react \
  clsx \
  tailwind-merge

# shadcn/ui
npx shadcn@latest init
npx shadcn@latest add button card input label badge dialog sheet tabs avatar

# Dev dependencies
npm install --save-dev \
  @playwright/test \
  @types/node \
  typescript
```

### Step 4: Supabase Setup
```bash
cd ../supabase
npm install -g supabase
supabase login
supabase init

# Create project at supabase.com then:
supabase link --project-ref YOUR_PROJECT_REF

# Create Edge Functions
supabase functions new webxpay-webhook
supabase functions new validate-ticket-qr
supabase functions new exchange-rate-sync
supabase functions new notify-driver
supabase functions new chat-relay
supabase functions new image-optimizer
supabase functions new content-moderator
supabase functions new generate-sitemap
supabase functions new otp-dispatcher
supabase functions new subscription-reminder
```

### Step 5: Flutter Monorepo Setup
```bash
cd ../flutter

# Create 3 apps + 1 shared package
flutter create --org com.grabberlk customer
flutter create --org com.grabberlk provider
flutter create --org com.grabberlk admin
flutter create --template=package shared

# Shared package pubspec.yaml
cat > shared/pubspec.yaml << 'EOF'
name: grabber_shared
version: 1.0.0
environment:
  sdk: ">=3.4.0 <4.0.0"
dependencies:
  flutter:
    sdk: flutter
  supabase_flutter: ^2.5.0
  dio: ^5.4.3
  flutter_secure_storage: ^9.2.2
  riverpod: ^2.5.1
  flutter_riverpod: ^2.5.1
  go_router: ^14.2.7
  freezed_annotation: ^2.4.4
  json_annotation: ^4.9.0
  hive_flutter: ^1.1.0
  connectivity_plus: ^6.0.3
  firebase_messaging: ^15.0.0
  flutter_local_notifications: ^17.0.0
  image_picker: ^1.1.2
  url_launcher: ^6.3.0
  share_plus: ^9.0.0
dev_dependencies:
  build_runner: ^2.4.11
  freezed: ^2.5.2
  json_serializable: ^6.8.0
EOF

# Add shared dependency to each app
for app in customer provider admin; do
  cd $app
  cat >> pubspec.yaml << 'EOF'

  grabber_shared:
    path: ../shared
  mobile_scanner: ^5.1.1
  flutter_map: ^7.0.2
  webview_flutter: ^4.7.0
  record: ^5.1.1
  just_audio: ^0.9.39
  geolocator: ^12.0.0
  qr_flutter: ^4.1.0
  lottie: ^3.1.2
  cached_network_image: ^3.3.1
  infinite_scroll_pagination: ^4.0.0
EOF
  flutter pub get
  cd ..
done
```

### Step 6: Docker Compose (Local Development)
```yaml
# grabber/docker-compose.yml
version: '3.9'
services:
  api:
    build:
      context: ./api
      dockerfile: Dockerfile.dev
    volumes:
      - ./api:/var/www/html
    ports:
      - "8000:8000"
    depends_on:
      - postgres
      - redis
    env_file:
      - ./api/.env
    command: php artisan serve --host=0.0.0.0 --port=8000

  horizon:
    build:
      context: ./api
      dockerfile: Dockerfile.dev
    volumes:
      - ./api:/var/www/html
    depends_on:
      - postgres
      - redis
    env_file:
      - ./api/.env
    command: php artisan horizon

  scheduler:
    build:
      context: ./api
      dockerfile: Dockerfile.dev
    volumes:
      - ./api:/var/www/html
    depends_on:
      - postgres
      - redis
    env_file:
      - ./api/.env
    command: php artisan schedule:work

  web:
    build:
      context: ./web
      dockerfile: Dockerfile.dev
    volumes:
      - ./web:/app
      - /app/node_modules
    ports:
      - "3000:3000"
    environment:
      - NEXT_PUBLIC_API_URL=http://localhost:8000

  postgres:
    image: postgis/postgis:16-3.4
    environment:
      POSTGRES_DB: grabber
      POSTGRES_USER: grabber
      POSTGRES_PASSWORD: grabber_secret
    volumes:
      - postgres_data:/var/lib/postgresql/data
    ports:
      - "5432:5432"

  redis:
    image: redis:7-alpine
    ports:
      - "6379:6379"

  meilisearch:
    image: getmeili/meilisearch:latest
    ports:
      - "7700:7700"
    environment:
      MEILI_MASTER_KEY: grabber_meili_key

volumes:
  postgres_data:
```

---

## C19. SPRINT-BY-SPRINT BUILD PLAN

### SPRINT 0 — Foundation (Week 1)
```
GOAL: Working skeleton of all 3 layers

API:
  □ Run all DB migrations (every table from B8)
  □ Seed: roles, platform_config, feature_flags, taxi_categories
  □ Seed: admin_roles (all 6 departments + super_admin)
  □ Laravel Horizon + Telescope working
  □ Basic health-check endpoint (GET /v1/health)
  □ CORS configured for grabber.lk
  □ CSP headers middleware
  □ Security headers middleware
  □ OTP rate limit middleware
  □ WebxPay webhook middleware (HMAC verification)

SUPABASE:
  □ Storage buckets: media (public), private-docs, invoices, reports
  □ RLS policies on all private buckets
  □ Enable extensions: postgis, vector, pg_cron, pgcrypto
  □ All 10 Edge Functions scaffolded and deployed
  □ pg_cron jobs running (seat holds, OTP cleanup, bank transfer cancellation)
  □ Realtime enabled on: taxi_rides, chat_messages, bookings

NEXT.JS:
  □ next-intl configured (8 locales, en default)
  □ Translation files: en.json, si.json, ta.json (skeleton all 8)
  □ Root layout with locale routing
  □ Metadata defaults (Grabber brand)
  □ Tailwind design tokens (Grabber Red #E63946 primary)
  □ shadcn/ui initialized

FLUTTER:
  □ Shared package: AppConfig (dart-define values)
  □ Shared package: ApiService (Dio + auth interceptor)
  □ Shared package: SupabaseService (realtime channels)
  □ All 3 apps: main.dart with Riverpod + GoRouter skeleton
  □ All 3 apps: splash screen with Grabber branding
```

### SPRINT 1 — Auth + Profiles + Admin Structure (Week 2)
```
GOAL: Any user can register, login, verify, and see their profile

API:
  □ AuthController: register, login, logout, me, refresh
  □ OtpController: send (with rate limiting), verify
  □ TwoFactorController: setup, confirm, verify (admin only)
  □ ProfileController: show, update, upload avatar
  □ KycController: upload docs → Supabase private bucket
  □ Admin roles seeded + permissions matrix
  □ AdminStaffController: CRUD staff accounts
  □ Audit log middleware (logs all admin actions)
  □ PearlPointsController: balance, history (no wallet endpoints)
  □ ProviderWalletController: show, transactions (provider only)

NEXT.JS:
  □ /auth/sign-in, /auth/sign-up, /auth/otp, /auth/forgot-password
  □ useAuth hook + AuthProvider (Zustand)
  □ Protected route wrapper
  □ Profile page (/profile)
  □ Admin layout (admin.grabber.lk or /admin route, behind 2FA)
  □ Admin: Staff Management tab (HR dept view)
  □ Admin: Feature Flags tab (all flags, ON/OFF)
  □ Admin: Settings tab (platform_config editor)
  □ Admin: Audit Log tab

FLUTTER:
  □ Grabber: SignInScreen, SignUpScreen, OtpVerifyScreen
  □ Grabber: ProfileScreen, EditProfileScreen, KycUploadScreen
  □ Grabber Provider: SignInScreen + 2FA setup (admin role)
  □ Grabber Admin: SignInScreen + mandatory 2FA verify
  □ Shared: secure token storage (FlutterSecureStorage)

TESTS (write as you go):
  □ Pest: register → login → OTP verify flow
  □ Pest: OTP rate limiting (3 per 15min)
  □ Pest: Admin 2FA required for admin routes
```

### SPRINT 2 — Stays + Tax + Vehicles + Fleet (Week 3-4)
```
GOAL: Stays and Vehicles bookable with all 3 payment methods

API:
  □ StayController: index (SSR), show, search (Meilisearch)
  □ ProviderStayController: CRUD + availability + tax config + transport packages
  □ TaxCalculationService: VAT + TDL + service charge + invoice PDF
  □ VehicleController: index, show, search
  □ ProviderVehicleController: CRUD + fleet management
  □ FleetController: create, bulk import (CSV), analytics
  □ SubscriptionBillingService: subscribe, renew, grace period, suspend
  □ SubscriptionController: subscribe/cancel/invoices
  □ BookingController: store (all 3 payment methods), cancel, modify
  □ CheckoutCalculationService: pts discount + card handling fee + deposit + tax
  □ WebxPayController: initiate + webhook handler
  □ BankTransferController: confirm receipt (accounting dept)
  □ CashPaymentController: mark cash received (provider), confirm at office (admin)
  □ EscrowService: hold, release, dispute
  □ PearlPointsService: award on booking complete

NEXT.JS:
  □ /stays SSR listing page + filters
  □ /stays/[slug] SSR detail page (JSON-LD: LodgingBusiness)
  □ Location section: Google Maps URL + OpenStreetMap Leaflet fallback
  □ AI Concierge widget (floating button, stays context)
  □ Booking widget with payment method selector:
      Card (+ handling fee display)
      Bank Transfer (show Grabber bank details)
      Cash (show agent finder or "pay provider" option)
  □ Pearl Points discount toggle at checkout
  □ /vehicles SSR listing + detail
  □ Provider stays dashboard (all 10 tabs)
  □ Provider vehicles dashboard (fleet management)
  □ Admin: Bank Transfer Queue (Accounting dept)
  □ Admin: Cash Payment Confirmations
  □ Admin: Payout Queue

FLUTTER:
  □ Grabber: StaysListScreen + StayDetailScreen + booking flow
  □ Grabber: VehiclesListScreen + VehicleDetailScreen + deposit info
  □ Grabber: PaymentMethodScreen (card → WebxPay webview, bank → details shown, cash → confirm)
  □ Grabber: PearlPointsScreen (balance, history, redeem toggle)
  □ Grabber Provider: StaysDashboard (all tabs)
  □ Grabber Provider: VehicleList, FleetDashboard, VehicleEditor
  □ Grabber Provider: MarkCashReceivedScreen (for cash stays)
  □ Grabber Admin: BankTransferQueueScreen, CashPaymentScreen

TESTS:
  □ Pest: card booking → webhook → commission → provider wallet
  □ Pest: bank transfer → accounting confirms → booking activated
  □ Pest: cash provider → mark received → commission invoiced
  □ Pest: Pearl Points applied at checkout → WebxPay charged correct amount
  □ Pest: deposit held in escrow → auto-release after 48h
```

### SPRINT 3 — Taxi Full Logic (Week 5-6)
```
GOAL: Complete taxi system with cash commission billing

API:
  □ TaxiRideController: request, show, cancel, rate, SOS, splitFare
  □ TaxiDriverController: setStatus, updateLocation (→ Supabase Realtime), accept, complete
  □ TaxiFareController: estimate (PostGIS distance), calculate surge
  □ TaxiSurgeService: auto-algorithm (demand/supply) + manual zone override
  □ TaxiDriverScoringService: weekly recalculation (Laravel Scheduler)
  □ TaxiQuestService: progress tracking, reward crediting to driver wallet
  □ TaxiCorporateController: company CRUD, employee management, invoice generation
  □ CashCommissionInvoiceService: weekly driver statement, monthly provider invoice
  □ AdminTaxiController: live rides, driver scores, surge zones, quests, fixed fares, corporate
  □ Supabase Edge Function: notify-driver (FCM < 500ms)

NEXT.JS:
  □ /taxi CSR page (Leaflet map, category cards, fare estimate)
  □ Ride tracking page (Supabase Realtime WebSocket via Pusher protocol)
  □ AI Concierge on taxi page (fare estimation context)
  □ Admin: Taxi tab (live map, driver queue, surge control)
  □ Admin: Driver Scores + Quest Management
  □ Admin: Cash Commission Invoices tab (Accounting dept)
  □ Admin: Corporate Accounts management

FLUTTER:
  □ Grabber: TaxiHomeScreen (flutter_map, live driver markers via Supabase Realtime)
  □ Grabber: RideTrackingScreen (real-time GPS, ETA, chat with driver)
  □ Grabber: RideCompleteScreen (rating + tip)
  □ Grabber: ScheduleRideScreen, FareSplitScreen
  □ Grabber Provider (Driver): DriverHomeScreen (online toggle, Geolocator)
  □ Grabber Provider (Driver): IncomingRideSheet (30s timer, accept/decline)
  □ Grabber Provider (Driver): ActiveRideScreen (navigate, arrive, start, complete, cash toggle)
  □ Grabber Provider (Driver): CashCommissionScreen (weekly statement, pay invoice)
  □ Grabber Provider (Driver): QuestsScreen + ScoringScreen
  □ Grabber Admin: GodsViewScreen (flutter_map, all markers, surge zones)

TESTS:
  □ Pest: ride request → driver matched → accepted → completed → commission
  □ Pest: cash ride → marked paid → weekly invoice generated
  □ Pest: surge algorithm (demand > supply → multiplier increases)
  □ Pest: driver score recalculation logic
```

### SPRINT 4 — Events + QR + Experiences (Week 7-8)
```
GOAL: Events bookable with QR system; Experiences live with SLTDA verification

API:
  □ EventController: index, show, search
  □ ProviderEventController: CRUD, ticket tiers, staff management
  □ EventTicketService: JWT QR generation, PDF ticket, void
  □ EventQrController: scan (calls Supabase Edge Function)
  □ SeatHoldController: create, release
  □ WaitingListController: join, leave, offer slot
  □ ExperienceController: index, show, search
  □ ProviderExperienceController: CRUD, schedules, packages
  □ Supabase Edge Function: validate-ticket-qr (offline-capable JWT verify)

NEXT.JS:
  □ /events SSR listing + detail (JSON-LD: Event)
  □ Event ticket purchase flow + seat map (if enabled)
  □ /experiences SSR listing + detail (JSON-LD: TouristTrip)
  □ Experience booking with schedule selection
  □ AI Concierge on events + experiences pages
  □ Provider: Event staff management + attendance dashboard
  □ Provider: Experience dashboard + schedule editor
  □ Admin: Events tab + Experience tab + SLTDA verification queue

FLUTTER:
  □ Grabber: EventDetailScreen + TicketPurchaseScreen
  □ Grabber: MyTicketsScreen + TicketQrScreen (show QR)
  □ Grabber: ExperienceDetailScreen + BookingScreen
  □ Grabber Provider: GateScannerScreen (offline JWT verify + sync)
  □ Grabber Provider: EventAttendanceDashboard (live counter)
  □ Grabber Provider: ExperienceEditor + ScheduleScreen
  □ Grabber Admin: ExperienceReviewScreen (SLTDA docs)

TESTS:
  □ Pest: event ticket purchase → QR generated → scan valid → scan again → rejected
  □ Pest: waiting list → slot opens → first in list notified → 4h window → booked
  □ Pest: experience weather cancellation → auto refund → no commission
```

### SPRINT 5 — Properties + Flash Deals + Bundles (Week 9-10)
```
GOAL: Properties with full deed/broker flow; Flash Deals live; Bundles

API:
  □ PropertyController: index, show, search (SSR-optimised)
  □ PropertyDeedController: upload → Supabase private bucket
  □ BrokerDocumentService: generate blank PDF + upload signed
  □ PropertyCommissionService: promo code, buyer confirm, seller confirm
  □ PropertySaleController: full sale promo flow
  □ PropertyInquiryController: enquiry form → chat conversation
  □ FlashDealController: list, claim, redeem
  □ ProviderFlashDealController: create (WebxPay LKR 1,500 fee), list, analytics
  □ BundleController: create, partner invite, booking

NEXT.JS:
  □ /properties SSR listing + detail (mortgage calculator, valuation widget)
  □ /properties/confirm-sale (promo code entry page)
  □ Neighbourhood insights (Google Places if enabled)
  □ Virtual tour embed (Matterport/YouTube)
  □ /flash-deals hub (countdown timers, Grabber Pick badges)
  □ /bundles listing + detail
  □ Provider: deed upload, broker consent, promo code generate
  □ Admin: Property deeds queue, broker consents queue (KYC dept)
  □ Admin: Property sales tracker, flash deals editorial

FLUTTER:
  □ Grabber: PropertyDetailScreen + ConfirmSaleScreen (buyer promo entry)
  □ Grabber: FlashDealDetailScreen + ClaimVoucherScreen
  □ Grabber: BundleDetailScreen
  □ Grabber Provider: PropertyDeedUploadScreen, BrokerConsentScreen, PromoCodeScreen
  □ Grabber Provider: FlashDealCreateScreen
  □ Grabber Admin: PropertyDeedReviewScreen (document viewer + checklist)

TESTS:
  □ Pest: property listing fee payment → deed verified → listing approved
  □ Pest: broker consent PDF generated → uploaded → approved
  □ Pest: sale promo code flow (end-to-end: generate → buyer confirms → seller confirms → pts awarded)
  □ Pest: flash deal claim → voucher generated → redeemed at booking → commission charged
```

### SPRINT 6 — Social Premium + SME Premium + Chat (Week 11)
```
GOAL: Social with premium chat/video; SME appointments; all chat types

API:
  □ ChatController: all conversation types (booking, enquiry, taxi, social, support)
  □ TranslationService: translate message (DeepL/OpenAI)
  □ VoiceTranscriptionService: Whisper API
  □ SocialVideoController: upload short video
  □ ContentModerationService: OpenAI Vision check on all uploads
  □ SocialGroupController: CRUD groups, join, post
  □ SmeAppointmentController: services, availability, book, queue
  □ SmeProductController: CRUD products, bulk import
  □ Supabase Edge Function: chat-relay (message insert + Realtime broadcast)
  □ Supabase Edge Function: content-moderator (AI scan on upload)

NEXT.JS:
  □ Social feed SSR + client polling
  □ Short video feed (Reels-like infinite scroll)
  □ Premium chat UI (web + realtime via Supabase)
  □ Chat translation UI (translate button → inline translated text)
  □ Voice note player (in-chat audio player)
  □ Social commerce: listing tag in posts → tappable card
  □ SME product catalogue page
  □ SME appointment booking page
  □ Provider: quick replies management
  □ Admin: Social moderation queue (AI-flagged content)

FLUTTER:
  □ Grabber: SocialFeedScreen, SocialVideoFeedScreen
  □ Grabber: ConversationListScreen, ConversationScreen (all message types + translation)
  □ Grabber: VoiceNoteRecorder (hold to record), AudioPlayer widget
  □ Grabber: SmeProductsScreen, SmeAppointmentScreen
  □ Grabber Provider: QuickRepliesScreen, ConversationQueueScreen
  □ Grabber Admin: SocialModerationScreen

TESTS:
  □ Pest: send message → Realtime broadcast → received → translate → stored
  □ Pest: voice note upload → transcription request → text stored
  □ Pest: social post listing tag → booking from tag → affiliate pts awarded
```

### SPRINT 7 — Admin Full Dashboard + All Departments (Week 12)
```
GOAL: Complete admin system with all 6 departments functional

NEXT.JS (ADMIN — all tabs):
  □ Overview: GMV charts, quick action cards, live stats
  □ God's View: Leaflet map (10 layers, all toggleable, Supabase Realtime)
  □ Users: Customer 360° + Provider 360° + ban/suspend/award pts
  □ Verifications: KYC + Deeds + Broker Consents + SLTDA (KYC dept)
  □ Listings: All verticals, approve/reject/suspend/feature
  □ Bookings: Full table with timeline, refund (any method), admin note
  □ Finance (Accounting dept):
      Bank Transfer Queue (confirm receipt → activate booking)
      Cash Commission Invoices (driver + provider — view, mark paid, suspend)
      Cash Agent Management (register agents, daily reconciliation)
      Grabber Bank Account Display (for customers to transfer to)
      Payout Queue (process withdrawals, enter bank ref)
      Escrow (release, dispute, auto-release schedule)
      Revenue charts (by vertical, by payment method)
      Pearl Points Liability dashboard
  □ Subscriptions: All subs, price override per provider, suspend/extend grace
  □ Taxi: Live rides, driver scores, quests, surge zones, corporate, fixed fares, cash commissions
  □ Flash Deals: All deals, Grabber Pick, pull, analytics
  □ Properties: Sales tracker, deed queue, broker consent queue
  □ Social: Moderation queue, premium stats
  □ Departments: HR view — staff list, role assignment, audit log
  □ Support Inbox (Customer Service dept): tickets, assign, reply, close
  □ Issues (Issue Resolution dept): disputes, fraud, full override tools
  □ Settings: All platform_config editable + feature flags
  □ Vertical ON/OFF: Master service toggle page (all 9 verticals + sub-features)
  □ Payment Methods: Toggle card/bank/cash per vertical + handling fee config

FLUTTER (Admin App — all screens):
  □ GodsViewScreen
  □ SupportInboxScreen + TicketDetailScreen
  □ DocumentReviewScreen (deed/KYC with document viewer + checklist)
  □ BankTransferQueueScreen
  □ CashInvoiceScreen
  □ SubscriptionManageScreen
  □ ServiceToggleScreen (ON/OFF all verticals)

TESTS:
  □ Pest: bank transfer confirm → booking activated → provider wallet scheduled
  □ Pest: cash invoice overdue → auto-suspend trigger
  □ Pest: subscription grace period → suspend → reactivate
  □ Pest: God's View data returns real-time drivers in radius
```

### SPRINT 8 — SEO + Performance + Notifications (Week 13)
```
GOAL: Platform is fast, indexed, and notifies users on all channels

SUPABASE EDGE FUNCTION: generate-sitemap
  All 9 verticals → all approved listing slugs → XML sitemap → public storage
  Run daily via pg_cron

NEXT.JS:
  □ JSON-LD structured data for all 9 verticals
  □ hreflang tags (all 8 languages) on all SSR pages
  □ Open Graph + Twitter Card meta per page
  □ Dynamic OG images (using @vercel/og)
  □ Multilingual listing display (next-intl, provider translations)
  □ Auto-translate button on listing descriptions (API call → DeepL)
  □ Review translation ("Translated from Sinhala") badge
  □ Google Maps embed on all applicable listings
  □ AI Concierge floating widget on all vertical listing pages

NOTIFICATIONS (all 60+ types):
  □ FCM push (via FCM server key, queued Laravel job)
  □ Email (Mailgun, HTML templates, multilingual)
  □ SMS (Twilio, key notifications only)
  □ WhatsApp (Twilio Business API, booking confirmations)
  □ In-app (Supabase Realtime push to user:{id} channel)

REDIS CACHING (all endpoints):
  □ Stays list: 5-minute cache
  □ Stay detail: 30-minute cache  
  □ Flash deals: 30-second cache (near real-time)
  □ Platform config: 1-hour cache
  □ Feature flags: 1-hour cache
  □ Exchange rates: 6-hour cache
  □ Admin God's View: 10-second cache

PERFORMANCE:
  □ Meilisearch: index all 9 verticals with live sync (Observer pattern)
  □ Image optimization: Supabase Edge Function (WebP conversion on upload)
  □ Next.js ISR: stays/vehicles/events/experiences pages revalidate: 1800
  □ Lighthouse: target > 90 on all SSR pages
```

### SPRINT 9 — Testing + Security + Polish (Week 14)
```
GOAL: Production-grade quality, security verified, ready for launch

PEST PHP TESTS (write full suite):
  □ AuthTest: register, login, OTP rate limit, 2FA admin, account lockout
  □ BookingTest: card, bank transfer, cash provider, cash agent
  □ PaymentTest: webhook idempotency, replay attack prevention
  □ CommissionTest: calculation per vertical, tax exclusion
  □ PearlPointsTest: earn rules, redeem max 30%, restore on cancel
  □ ProviderWalletTest: credit on booking, hold period, payout flow
  □ CashCommissionTest: invoice generation, suspension trigger
  □ EscrowTest: hold, auto-release, damage claim
  □ PropertySaleTest: promo code flow end-to-end
  □ SubscriptionTest: billing, grace, suspend, reactivate, price override
  □ TaxiTest: surge algorithm, driver scoring, quest rewards
  □ QrTicketTest: generate, scan valid, scan again rejected, void
  □ > 70% overall coverage

PLAYWRIGHT E2E:
  □ stays-booking.spec.ts: search → view → select dates → card checkout → confirm
  □ vehicle-deposit.spec.ts: book → deposit held → return → auto-release
  □ taxi-flow.spec.ts: request → driver accepts (mock) → complete → driver rated
  □ event-ticket.spec.ts: purchase → QR displayed → scan validates
  □ property-sale.spec.ts: promo generated → buyer confirms → seller confirms → pts
  □ flash-deal.spec.ts: post deal → customer claims → redeems → commission

SECURITY AUDIT:
  □ OWASP Top 10 checklist
  □ Payment webhook: replay attack test
  □ Document access: unsigned URL → should 403
  □ Admin routes: no 2FA → should redirect
  □ SQL injection: all custom queries reviewed
  □ XSS: all user content sanitized
  □ Rate limiting: all sensitive endpoints
  □ OTP abuse: 4th request in 15min → 429
  □ HMAC-SHA256 on all WebxPay webhooks

POLISH:
  □ Responsive audit: 320px, 375px, 768px, 1024px, 1440px
  □ RTL layout for Arabic locale
  □ WCAG 2.1 AA accessibility check
  □ Flutter: all screens match Grabber design system
  □ Error states: all API errors show user-friendly messages
  □ Empty states: all zero-result views have illustration + CTA
  □ Loading states: skeleton loaders on all listing grids
```

### SPRINT 10 — Launch (Week 15-16)
```
GOAL: Production deployed, apps submitted, Go Live

DEPLOYMENT:
  □ Railway: Laravel API deployed (web + worker + scheduler)
  □ Vercel: Next.js deployed (prod + preview)
  □ Supabase Pro: All Edge Functions deployed + secrets set
  □ Upstash: Redis production instance (TLS)
  □ Meilisearch Cloud: production instance + indexes
  □ DNS: grabber.lk → Vercel; api.grabber.lk → Railway; ws.grabber.lk → Reverb
  □ SSL: automatic via Railway + Vercel
  □ CDN: media.grabber.lk → Supabase Storage CDN

APPS:
  □ Grabber (customer): Play Store + App Store submitted
  □ Grabber Provider: Play Store + App Store submitted
  □ Grabber Admin: Firebase App Distribution (internal)
  □ All apps: Grabber icon, splash, store screenshots, privacy policy URL

ADMIN SETUP:
  □ Super admin account created
  □ All dept staff accounts created (2FA enforced)
  □ Grabber bank account details entered in platform_config
  □ WebxPay: sandbox fully tested → switch to production keys
  □ First cash agent registered and trained
  □ All platform_config seeded (from C24 below)
  □ All feature flags reviewed and set

MONITORING:
  □ Sentry: errors alerting for API + Web + Flutter
  □ BetterUptime: monitor api.grabber.lk + grabber.lk
  □ Railway: crash alerts → auto-restart enabled
  □ Horizon: queue size alerts (>500 jobs pending → alert)

SMOKE TESTS (on production):
  □ Book a stay with card → payment confirmed → wallet credited
  □ Book a stay with bank transfer → accounting confirms → booking active
  □ Request a taxi → driver accepts (use test driver) → complete → commission
  □ Post a flash deal → customer claims voucher → redeems
  □ Admin: toggle a feature flag → confirm reflected on website
  □ Admin: process a payout → provider notified
```

---

## C20. CLAUDE CODE PROMPTS — PER SPRINT

### How to Use With Claude Code

Open terminal in your project root, start Claude Code:
```bash
cd grabber
claude  # Starts Claude Code session
```

Use these prompts in Claude Code for each sprint:

### Sprint 0 Prompts
```
PROMPT 1 — Database Migrations:
"Create Laravel migrations for the Grabber platform in the api/ directory. 
Start with: 001_create_users_table.php, 002_create_profiles_table.php, 
003_create_provider_wallets_table.php, 004_create_pearl_points_balances_table.php, 
005_create_payments_table.php.
Use the schema from our spec: users table has role field with these values: 
customer, provider_stays, provider_vehicles, provider_events, provider_experiences, 
provider_properties, property_broker, provider_sme, driver, cash_agent, admin, super_admin.
Profiles table includes: bank details fields, accepts_cash boolean, 
cash_security_deposit_paid boolean, social_tier.
Payments table supports all 3 methods: card, bank_transfer, cash_agent, cash_provider.
Add all indexes specified including PostGIS index on driver location."

PROMPT 2 — Platform Config Seed:
"Create a Laravel seeder file api/database/seeders/PlatformConfigSeeder.php 
that seeds ALL platform_config values for the Grabber platform including:
branding (company name: Grabber Mobility Solutions Pvt Ltd, website: grabber.lk),
commissions (stays 12%, vehicles 10%, taxi 15%, events 8%, experiences 10%, 
properties_sale 2%, properties_rent 5%, flash_deals 12%, bundles 8%),
subscriptions (vehicle_monthly 9500, experiences_monthly 29500, sme_premium 6500, 
social_premium 3300, property_listing_fee 15000, flash_deal_fee 1500),
payments (card_handling_fee_enabled false, card_handling_fee_rate 0.03, 
card_enabled true, bank_transfer_enabled true, cash_enabled true,
cash_payment_window_hours 24, bank_transfer_window_hours 48,
withdrawal_fee_lkr 50, min_payout_lkr 5000, payout_hold_days 3),
pearl_points (earn_per_100_lkr 1, earn_review 50, earn_referral 500,
redeem_max_percent 30, redemption_face_value 1.00, redemption_cost 0.80, expiry_months 12),
taxi (min_fare 300, max_surge 3.0, auto_surge_enabled true, acceptance_timeout_sec 30),
and ALL other categories from the spec."
```

### Sprint 1 Prompts
```
PROMPT 3 — Auth System:
"Build the complete authentication system for Grabber in Laravel 11.
Create: app/Http/Controllers/Auth/AuthController.php with methods: 
register(creates user + profile + pearl_points_balance + provider_wallet if provider role),
login(returns Sanctum token; if admin role requires 2FA flag),
logout, me(returns user with profile + pts balance + wallet if provider).
Create: OtpController with send() method that:
  1. Checks otp_requests table for rate limits (3/15min per identifier, 10/hr per IP)
  2. Sends OTP via Supabase Edge Function (otp-dispatcher)
  3. Logs to otp_requests table
Create: TwoFactorController for admin TOTP setup and verify.
Create FormRequest validation classes for each action.
All responses must use JsonResource classes with consistent format:
  success: { data: {...}, message: 'string' }
  error: { message: 'string', errors: { field: ['error'] } }
Use Sanctum token abilities: admin:*, provider:*, customer:*, driver:*"

PROMPT 4 — Admin Role System:
"Set up the Grabber admin role and permission system using Spatie Laravel Permission.
Create a seeder that creates these admin roles with their permissions:
  super_admin: all permissions
  finance_admin: finance:read, finance:export, payouts:process, escrow:manage,
    bookings:read, bookings:refund, bank_transfer:confirm, cash:confirm,
    cash_invoices:manage, provider_wallet:adjust
  marketing_admin: coupons:manage, flash_deals:editorial, notifications:broadcast,
    featured:manage, analytics:read, translations:manage
  hr_admin: staff:manage, roles:read, audit:read_admin
  customer_service_agent: bookings:read, users:read_basic, bookings:refund_small(≤25000),
    support:manage, notifications:send_individual
  kyc_officer: kyc:review, deeds:review, broker_consents:review, 
    documents:view_signed_url, providers:read_basic
  issue_resolver: disputes:manage, bookings:override, users:read_full,
    chat:read_all, fraud:manage, bookings:refund_large
Create middleware: EnsureAdminRole, TwoFactorVerified, CheckPermission(permission_name)
Create AdminUserController with full CRUD for admin staff accounts."
```

### Sprint 2 Prompts
```
PROMPT 5 — Payment Multi-Method Service:
"Create app/Services/PaymentService.php for Grabber that handles all 3 payment methods.
The service must implement:

1. initiatePayment(booking, payment_method, pearl_points_used, coupon_code):
   - Calculate: subtotal, pts discount (1pt = LKR 1.00 face value, max 30% of subtotal),
     coupon discount, card handling fee (3% if card + feature flag enabled, on base only),
     deposit hold fee (1.5% of deposit if card/bank), grand total
   - If card: call WebxPayService::initiate() → return { payment_url, session_id }
   - If bank_transfer: set booking.status=awaiting_bank_transfer,
     create payment record(status=awaiting_bank_transfer, bank_transfer_deadline=+48h),
     return { grabber_bank_name, grabber_account_number, grabber_account_name,
              booking_ref, amount, deadline }
   - If cash_provider: set booking.status=cash_pending,
     provider must mark received in their app,
     create cash_commission_invoice linkage
   - If cash_agent: set booking.awaiting_cash, create payment with cash_deadline=+24h,
     return { booking_ref, amount, agent_list_url, deadline }

2. confirmBankTransfer(payment_id, bank_transaction_ref, confirmed_by_admin_id):
   - Verify payment exists and is awaiting_bank_transfer
   - Update payment: status=completed, bank_transfer_ref, confirmed_by, confirmed_at
   - Trigger same post-payment flow as card: commission, provider wallet, pts

3. confirmCashAtAgent(booking_ref, cash_receipt_number, agent_id):
   - Verify booking is awaiting_cash
   - Update payment: status=completed, cash_agent_id, cash_receipt_number
   - Trigger post-payment flow

4. markCashReceivedByProvider(booking_id, provider_id):
   - Provider marks cash received for their own booking
   - Updates booking.status=in_progress or confirmed (depending on vertical)
   - Adds commission to cash_commission_invoices for this provider

5. processPostPayment(booking_id) [called after any payment confirmed]:
   - Calculate commission (vertical-specific rate, any provider override)
   - Calculate provider_earns = subtotal - commission (excl taxes, excl deposit)
   - Create provider_wallet_transaction(type=booking_credit, status=on_hold)
   - Set booking.payout_hold_until = now() + provider.payout_hold_days
   - Deduct pearl_points_used from customer balance
   - Schedule pearl_points_earned award (on booking completion, not payment)
   - Fire BookingStatusChanged event (Supabase Realtime broadcast)

Use dependency injection. Write Pest tests for each method."

PROMPT 6 — Checkout Calculation API:
"Create POST /v1/checkout/calculate endpoint in Laravel.
Request body: { listing_id, vertical, check_in, check_out, quantity, 
payment_method, pearl_points_to_use, coupon_code, deposit_amount }
Response: {
  subtotal: number,
  deposit_amount: number,
  tax_breakdown: { service_charge, vat, tdl, total },
  pearl_points_discount: number,
  coupon_discount: number,
  card_handling_fee: number,
  card_handling_fee_rate: number,
  deposit_hold_fee: number,
  grand_total: number,
  payment_method: string,
  bank_transfer_info: { if bank: grabber bank details },
  available_pearl_points: number,
  max_pts_applicable: number,
  pearl_points_saved: number
}
Validate: pearl_points_to_use ≤ 30% of subtotal; pts available in customer balance;
coupon valid for this vertical; payment method enabled for this vertical (check feature_flags)."
```

### Sprint 3 Prompts
```
PROMPT 7 — Taxi System:
"Build the complete taxi ride system for Grabber in Laravel.

1. TaxiRideController::request():
   - Validate: pickup/dropoff coords, category_id, payment_method (card or cash only)
   - Get fare estimate using PostGIS: ST_Distance between points
   - Calculate surge: query online drivers in 10km zone, pending requests last 5min,
     compute demand_ratio, apply time bonuses (peak hours from platform_config),
     round to nearest 0.5, cap at max_surge (platform_config)
   - Create taxi_ride record (status=searching)
   - Query nearest 5 online drivers (PostGIS ST_DWithin 10km, not on active ride)
   - Dispatch NotifyDriverJob for nearest driver (calls Supabase Edge Function notify-driver)
   - Broadcast RideCreated to admin.gods-view channel

2. TaxiDriverController::accept(ride_id):
   - Set ride.driver_id, status=accepted, accepted_at
   - Broadcast TaxiStatusChanged to taxi-ride.{id} channel
   - Cancel pending notifications for other drivers

3. TaxiDriverController::complete(ride_id):
   - Set status=completing, completed_at
   - If cash: set cash_paid=true, calculate cash_commission_amount (fare × 15%)
     Add to provider cash_commission_invoices (running total for week)
   - If card: trigger payment processing
   - Award Pearl Points to customer (fare / 100 × 1 pts)
   - Broadcast TaxiStatusChanged

4. TaxiSurgeService::calculateSurge(lat, lng):
   All logic from spec with auto_surge_enabled flag check.
   Cache surge per zone in Redis (30-second TTL)

5. CashCommissionInvoiceService::generateWeeklyDriverStatement():
   - Runs every Monday via Laravel Scheduler
   - Group all cash rides by driver for the past week
   - Create cash_commission_invoices records
   - Send statement via email + push notification to each driver
   - If previous invoice still unpaid (>7 days): trigger driver account suspension"

PROMPT 8 — Driver Scoring + Quests:
"Build TaxiDriverScoringService in Laravel.
scoreDriver(driver_id, period_start, period_end):
  - Query: completed_rides, accepted_rides, received_requests, avg_rating, 
    avg_response_seconds, total_online_minutes for period
  - Calculate score: rating(40%) + acceptance_rate(20%) + completion_rate(20%) + 
    response_speed(10%) + hours(10%)
  - Determine tier: bronze/silver/gold/diamond
  - Calculate bonus_earned based on tier rate per km × total km in period
  - Upsert driver_scores record
  - Credit bonus to provider_wallet
  - Send notification if tier changed

Build TaxiQuestService:
  trackProgress(driver_id, quest_id): updates driver_quest_progress.current_value
  checkCompletion(driver_id): checks all active quests, marks completed, credits wallet
  Called after each ride completion.

Laravel Scheduler (app/Console/Kernel.php):
  $schedule->call([TaxiDriverScoringService::class, 'scoreAllDrivers'])->weeklyOn(1, '02:00');
  $schedule->call([TaxiQuestService::class, 'resetDailyQuests'])->daily()->at('00:00');
  $schedule->call([TaxiQuestService::class, 'resetWeeklyQuests'])->weeklyOn(1, '00:30');
  $schedule->call([CashCommissionInvoiceService::class, 'generateWeeklyStatements'])->weeklyOn(1, '08:00');
  $schedule->call([CashCommissionInvoiceService::class, 'suspendOverdue'])->daily()->at('09:00');
  $schedule->call([SubscriptionBillingService::class, 'processRenewals'])->daily()->at('09:30');
  $schedule->call([EscrowService::class, 'autoRelease'])->hourly();"
```

### Sprint 7 Prompts
```
PROMPT 9 — Admin Bank Transfer Queue:
"Build the bank transfer confirmation system for Grabber Accounting dept.

API: POST /v1/admin/finance/bank-transfers/{payment_id}/confirm
  Required permission: bank_transfer:confirm (finance_admin role)
  Body: { bank_transaction_ref: string }
  Actions:
    1. Verify payment status = awaiting_bank_transfer
    2. Verify bank_transfer_deadline not passed (if passed: return 422 'deadline expired')
    3. Call PaymentService::confirmBankTransfer()
    4. Log to audit_log: { admin_id, action: 'bank_transfer_confirmed', entity_id: payment_id, 
       before: {status: 'awaiting_bank_transfer'}, after: {status: 'completed', ref: bank_ref} }
    5. Return: updated booking + payment

GET /v1/admin/finance/bank-transfers
  Params: status (pending|confirmed|expired), date_from, date_to, vertical
  Returns: paginated list of bank transfer bookings with:
    booking_ref, customer_name, amount, vertical, created_at, deadline, time_remaining
  Sorted by: deadline ascending (most urgent first)

Build BankTransferQueueComponent in Next.js:
  Real-time refresh every 60 seconds
  Shows countdown timer for each pending transfer
  Bulk confirm option (select multiple → confirm all)
  Export CSV button"

PROMPT 10 — Admin Cash Commission Management:
"Build cash commission management for Grabber Accounting dept.

Tables to interact with: cash_commission_invoices, provider_wallets, bookings (is_cash_booking)

API endpoints:
GET /v1/admin/finance/cash-invoices
  Filter: status(pending/paid/overdue), provider_id, period
  Returns: all commission invoices with provider details, amount, due_date, days_overdue

POST /v1/admin/finance/cash-invoices/{id}/mark-paid
  Body: { payment_ref: string, payment_method: 'webxpay'|'bank_transfer' }
  Actions: mark invoice paid, trigger provider account reactivation if was suspended
  
POST /v1/admin/finance/cash-invoices/{id}/extend-deadline
  Requires: super_admin permission
  Body: { new_due_date: date, reason: string }
  Logs to audit_log

POST /v1/admin/finance/cash-invoices/{id}/waive
  Requires: super_admin permission
  Body: { reason: string }
  Marks invoice waived, logs audit entry

GET /v1/admin/finance/cash-agents
  List of authorised cash agents with: name, location, total_collected, last_active

POST /v1/admin/finance/cash-agents (create agent)
POST /v1/admin/finance/cash-agents/{id}/confirm-receipt
  Body: { booking_ref, amount, receipt_number }
  Activates the booking (calls PaymentService::confirmCashAtAgent)

Also create the CashSecurityDepositController:
  GET /v1/admin/finance/cash-deposits — list all provider cash deposits
  POST /v1/provider/cash-deposit/pay — provider pays cash security deposit to enable cash acceptance
  Only after deposit confirmed: profile.cash_security_deposit_paid = true"
```

---

## C21. TESTING PLAN

### Pest PHP Test Structure
```
api/tests/
├── Unit/
│   ├── Services/
│   │   ├── CheckoutCalculationTest.php   — pts discount, handling fee, tax, deposit
│   │   ├── TaxCalculationTest.php        — VAT 18%, TDL 1%, service charge 10%
│   │   ├── PropertyCommissionTest.php    — 2% commission, 0.5% pts cashback
│   │   ├── TaxiSurgeTest.php             — surge algorithm, peak hours, max cap
│   │   ├── TaxiDriverScoringTest.php     — score formula, tier assignment
│   │   ├── SubscriptionBillingTest.php   — renew, grace, suspend, override price
│   │   ├── CashCommissionTest.php        — invoice generation, suspension trigger
│   │   └── EscrowTest.php                — hold, auto-release, dispute
│   └── Models/
│       └── PearlPointsTest.php           — earn, redeem max 30%, expiry FIFO
│
├── Feature/
│   ├── Auth/
│   │   ├── RegistrationTest.php
│   │   ├── LoginTest.php
│   │   ├── OtpRateLimitTest.php          — 3/15min limit enforced
│   │   └── AdminTwoFactorTest.php        — admin blocked without 2FA
│   ├── Payments/
│   │   ├── CardPaymentTest.php           — initiate → webhook → commission → wallet
│   │   ├── BankTransferTest.php          — confirm → booking active → wallet
│   │   ├── CashProviderTest.php          — mark received → invoice
│   │   ├── CashAgentTest.php             — agent confirms → booking active
│   │   ├── WebhookIdempotencyTest.php    — duplicate webhook → skipped
│   │   └── RefundTest.php                — pts restored, provider wallet debited
│   ├── Bookings/
│   │   ├── StayBookingTest.php
│   │   ├── VehicleDepositTest.php
│   │   ├── TaxiRideTest.php
│   │   └── EventTicketTest.php
│   ├── Properties/
│   │   └── PropertySalePromoTest.php     — end-to-end promo flow
│   ├── Taxi/
│   │   └── CashCommissionInvoiceTest.php
│   └── Admin/
│       ├── BankTransferConfirmTest.php
│       └── CashInvoiceManagementTest.php
```

### Playwright E2E Tests
```
web/tests/e2e/
├── auth.spec.ts
├── stays-booking-card.spec.ts
├── stays-booking-bank-transfer.spec.ts
├── vehicle-deposit.spec.ts
├── taxi-booking.spec.ts
├── event-ticket-qr.spec.ts
├── property-sale-promo.spec.ts
├── flash-deal-claim.spec.ts
├── pearl-points-at-checkout.spec.ts
├── admin-bank-transfer-queue.spec.ts
└── admin-feature-flag-toggle.spec.ts
```

---

## C22. DEPLOYMENT PIPELINE

### GitHub Actions (Complete)
```yaml
# .github/workflows/api-test.yml
name: API Tests
on: [push, pull_request]
jobs:
  test:
    runs-on: ubuntu-latest
    services:
      postgres:
        image: postgis/postgis:16-3.4
        env:
          POSTGRES_DB: grabber_test
          POSTGRES_USER: grabber
          POSTGRES_PASSWORD: secret
        options: >-
          --health-cmd pg_isready
          --health-interval 10s
        ports: ['5432:5432']
      redis:
        image: redis:7-alpine
        ports: ['6379:6379']
    steps:
      - uses: actions/checkout@v4
      - uses: shivammathur/setup-php@v2
        with:
          php-version: '8.3'
          extensions: pdo_pgsql, redis, gd
          coverage: xdebug
      - run: cd api && composer install --prefer-dist --no-dev --no-interaction
      - run: cd api && cp .env.testing .env
      - run: cd api && php artisan key:generate
      - run: cd api && php artisan migrate --force
      - run: cd api && php artisan db:seed --class=TestSeeder
      - run: cd api && php artisan test --parallel --min-coverage=70
        env:
          DB_CONNECTION: pgsql
          DB_HOST: localhost
          DB_DATABASE: grabber_test
          DB_USERNAME: grabber
          DB_PASSWORD: secret

# .github/workflows/api-deploy.yml
name: Deploy API
on:
  push:
    branches: [main]
    paths: ['api/**']
jobs:
  test:
    uses: ./.github/workflows/api-test.yml
  deploy:
    needs: test
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - name: Deploy to Railway
        uses: bervProject/railway-deploy@v1
        with:
          railway_token: ${{ secrets.RAILWAY_TOKEN }}
          service: grabber-api

# .github/workflows/web-deploy.yml
name: Deploy Web
on:
  push:
    branches: [main]
    paths: ['web/**']
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: actions/setup-node@v4
        with: { node-version: '22' }
      - run: cd web && npm ci
      - run: cd web && npm run build
      - uses: amondnet/vercel-action@v25
        with:
          vercel-token: ${{ secrets.VERCEL_TOKEN }}
          vercel-org-id: ${{ secrets.VERCEL_ORG_ID }}
          vercel-project-id: ${{ secrets.VERCEL_PROJECT_ID }}
          vercel-args: '--prod'

# .github/workflows/flutter-build.yml
name: Flutter Build
on:
  push:
    branches: [main]
    paths: ['flutter/**']
  workflow_dispatch:
    inputs:
      app:
        type: choice
        options: [customer, provider, admin, all]
jobs:
  build:
    runs-on: ubuntu-latest
    strategy:
      matrix:
        app: [customer, provider, admin]
    steps:
      - uses: actions/checkout@v4
      - uses: subosito/flutter-action@v2
        with:
          flutter-version: '3.22.0'
          channel: stable
      - run: cd flutter/shared && flutter pub get
      - run: cd flutter/${{ matrix.app }} && flutter pub get
      - run: |
          cd flutter/${{ matrix.app }}
          flutter build apk --release \
            --dart-define=APP_NAME="${{ matrix.app == 'customer' && 'Grabber' || matrix.app == 'provider' && 'Grabber Provider' || 'Grabber Admin' }}" \
            --dart-define=API_BASE_URL=${{ secrets.API_BASE_URL }} \
            --dart-define=SUPABASE_URL=${{ secrets.SUPABASE_URL }} \
            --dart-define=SUPABASE_ANON_KEY=${{ secrets.SUPABASE_ANON_KEY }}
      - uses: actions/upload-artifact@v4
        with:
          name: grabber-${{ matrix.app }}-apk
          path: flutter/${{ matrix.app }}/build/app/outputs/flutter-apk/app-release.apk

# .github/workflows/supabase-deploy.yml
name: Deploy Edge Functions
on:
  push:
    branches: [main]
    paths: ['supabase/**']
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v4
      - uses: supabase/setup-cli@v1
      - run: supabase functions deploy --project-ref ${{ secrets.SUPABASE_PROJECT_REF }}
        env:
          SUPABASE_ACCESS_TOKEN: ${{ secrets.SUPABASE_ACCESS_TOKEN }}
```

---

## C23. ENVIRONMENT VARIABLES — COMPLETE FINAL

### Laravel `api/.env`
```dotenv
# ══════════════════════════════════════════════
# GRABBER — LARAVEL API PRODUCTION ENV
# ══════════════════════════════════════════════
APP_NAME="Grabber"
APP_COMPANY="Grabber Mobility Solutions Pvt Ltd"
APP_ENV=production
APP_DEBUG=false
APP_KEY=base64:GENERATE_WITH_php_artisan_key_generate
APP_URL=https://api.grabber.lk
FRONTEND_URL=https://grabber.lk

# ── Database (Supabase Pooler) ──────────────────
DB_CONNECTION=pgsql
DB_HOST=aws-0-ap-south-1.pooler.supabase.com
DB_PORT=6543
DB_DATABASE=postgres
DB_USERNAME=postgres.YOUR_PROJECT_REF
DB_PASSWORD=YOUR_SUPABASE_DB_PASSWORD

# ── Redis (Upstash TLS) ─────────────────────────
REDIS_URL=rediss://default:YOUR_TOKEN@YOUR_HOST.upstash.io:6379
REDIS_SCHEME=tls

# ── Supabase ────────────────────────────────────
SUPABASE_URL=https://YOUR_PROJECT_REF.supabase.co
SUPABASE_SERVICE_ROLE_KEY=YOUR_SERVICE_ROLE_KEY
SUPABASE_ANON_KEY=YOUR_ANON_KEY

# ── Broadcasting ────────────────────────────────
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=grabber
REVERB_APP_KEY=YOUR_REVERB_KEY
REVERB_APP_SECRET=YOUR_REVERB_SECRET
REVERB_HOST=ws.grabber.lk
REVERB_PORT=443
REVERB_SCHEME=https

# ── Queues ──────────────────────────────────────
QUEUE_CONNECTION=redis
HORIZON_PREFIX=grabber

# ── Cache ───────────────────────────────────────
CACHE_DRIVER=redis
SESSION_DRIVER=redis

# ── WebxPay ─────────────────────────────────────
WEBXPAY_MERCHANT_ID=YOUR_MERCHANT_ID
WEBXPAY_SECRET_KEY=YOUR_SECRET_KEY
WEBXPAY_API_URL=https://api.webxpay.com/api/v1
WEBXPAY_SANDBOX=false
WEBXPAY_RETURN_URL=https://grabber.lk/payment/success
WEBXPAY_CANCEL_URL=https://grabber.lk/payment/cancel

# ── Grabber Bank Account (for bank transfer payments) ──
GRABBER_BANK_NAME=Commercial Bank of Ceylon
GRABBER_BANK_ACCOUNT_NUMBER=1234567890
GRABBER_BANK_ACCOUNT_NAME="Grabber Mobility Solutions Pvt Ltd"
GRABBER_BANK_BRANCH=Colombo 03
GRABBER_BANK_SWIFT=CCEYLKLX

# ── Email (Mailgun) ─────────────────────────────
MAIL_MAILER=mailgun
MAILGUN_DOMAIN=grabber.lk
MAILGUN_SECRET=YOUR_MAILGUN_KEY
MAIL_FROM_ADDRESS=noreply@grabber.lk
MAIL_FROM_NAME="Grabber"

# ── SMS + WhatsApp (Twilio) ──────────────────────
TWILIO_SID=YOUR_SID
TWILIO_TOKEN=YOUR_TOKEN
TWILIO_FROM=+94XXXXXXXXX
WHATSAPP_FROM=whatsapp:+94XXXXXXXXX

# ── Firebase FCM ────────────────────────────────
FCM_SERVER_KEY=YOUR_SERVER_KEY

# ── OpenAI ──────────────────────────────────────
OPENAI_API_KEY=YOUR_KEY
OPENAI_MODEL=gpt-4o-mini
OPENAI_EMBEDDING_MODEL=text-embedding-3-small
WHISPER_MODEL=whisper-1

# ── DeepL (translation) ──────────────────────────
DEEPL_API_KEY=YOUR_DEEPL_KEY

# ── Google Maps + Places ─────────────────────────
GOOGLE_MAPS_API_KEY=YOUR_PLACES_API_KEY
GOOGLE_MAPS_EMBED_KEY=YOUR_EMBED_KEY

# ── Storage (S3/Supabase) ───────────────────────
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=YOUR_KEY
AWS_SECRET_ACCESS_KEY=YOUR_SECRET
AWS_DEFAULT_REGION=ap-south-1
AWS_BUCKET=grabber-media
AWS_URL=https://media.grabber.lk

# ── Meilisearch ──────────────────────────────────
SCOUT_DRIVER=meilisearch
MEILISEARCH_HOST=https://YOUR_HOST.meilisearch.io
MEILISEARCH_KEY=YOUR_MASTER_KEY

# ── Event QR ─────────────────────────────────────
EVENT_QR_SECRET=64_CHAR_RANDOM_NEVER_ROTATE_AFTER_GOLIVE

# ── Sentry ───────────────────────────────────────
SENTRY_LARAVEL_DSN=https://KEY@sentry.io/PROJECT
```

### Next.js `web/.env.local`
```dotenv
NEXT_PUBLIC_API_URL=https://api.grabber.lk
NEXT_PUBLIC_APP_NAME=Grabber
NEXT_PUBLIC_COMPANY=Grabber Mobility Solutions Pvt Ltd
NEXT_PUBLIC_SUPABASE_URL=https://YOUR_REF.supabase.co
NEXT_PUBLIC_SUPABASE_ANON_KEY=YOUR_ANON_KEY
NEXT_PUBLIC_GOOGLE_MAPS_EMBED_KEY=YOUR_EMBED_KEY
NEXT_PUBLIC_GA4_ID=G-XXXXXXXXXX
NEXT_PUBLIC_SENTRY_DSN=https://KEY@sentry.io/PROJECT
```

### Flutter Dart Defines (build command)
```bash
# Customer App
flutter build apk --release \
  --dart-define=APP_NAME="Grabber" \
  --dart-define=API_BASE_URL=https://api.grabber.lk \
  --dart-define=SUPABASE_URL=https://YOUR_REF.supabase.co \
  --dart-define=SUPABASE_ANON_KEY=YOUR_ANON_KEY \
  --dart-define=GOOGLE_MAPS_KEY=YOUR_ANDROID_KEY \
  --dart-define=ENVIRONMENT=production

# Provider App  
flutter build apk --release \
  --dart-define=APP_NAME="Grabber Provider" \
  --dart-define=API_BASE_URL=https://api.grabber.lk \
  --dart-define=SUPABASE_URL=https://YOUR_REF.supabase.co \
  --dart-define=SUPABASE_ANON_KEY=YOUR_ANON_KEY \
  --dart-define=ENVIRONMENT=production

# Admin App
flutter build apk --release \
  --dart-define=APP_NAME="Grabber Admin" \
  --dart-define=API_BASE_URL=https://api.grabber.lk \
  --dart-define=SUPABASE_URL=https://YOUR_REF.supabase.co \
  --dart-define=SUPABASE_ANON_KEY=YOUR_ANON_KEY \
  --dart-define=ENVIRONMENT=production
```

---

## C24. ADMIN CONFIG REFERENCE — COMPLETE FINAL

```
ALL platform_config keys (Category → Key → Default Value):

BRANDING:
  app_name                     = Grabber
  company_name                 = Grabber Mobility Solutions Pvt Ltd
  website                      = https://grabber.lk
  support_email                = support@grabber.lk
  finance_email                = finance@grabber.lk
  kyc_email                    = kyc@grabber.lk
  support_phone                = +94112345678
  whatsapp_support             = +94771234567
  grabber_bank_name            = Commercial Bank of Ceylon
  grabber_bank_account         = [TO BE SET]
  grabber_bank_account_name    = Grabber Mobility Solutions Pvt Ltd
  grabber_bank_branch          = [TO BE SET]

COMMISSIONS:
  commission_stays             = 0.12
  commission_vehicles          = 0.10
  commission_taxi              = 0.15
  commission_events            = 0.08
  commission_experiences       = 0.10
  commission_properties_sale   = 0.02
  commission_properties_rent   = 0.05
  commission_flash_deals       = 0.12
  commission_bundles           = 0.08
  commission_sme_appointments  = 0.10
  property_buyer_cashback_pts  = 0.005

SUBSCRIPTIONS:
  vehicle_monthly              = 9500
  experiences_monthly          = 29500
  sme_premium_monthly          = 6500
  social_premium_monthly       = 3300
  property_listing_fee         = 15000
  flash_deal_fee               = 1500
  grace_period_days            = 7
  cash_security_deposit_weeks  = 2     (weeks of avg commission as deposit)
  cash_commission_due_days     = 14    (days to pay monthly invoice)

PAYMENTS:
  card_enabled                 = true
  bank_transfer_enabled        = true
  cash_enabled                 = true
  card_handling_fee_enabled    = false
  card_handling_fee_rate       = 0.03
  bank_transfer_window_hours   = 48
  cash_agent_window_hours      = 24
  vehicle_deposit_hold_fee     = 0.015
  withdrawal_fee_lkr           = 50
  min_payout_lkr               = 5000
  payout_hold_days             = 3
  escrow_auto_release_hours    = 48
  refund_window_hours          = 48

PEARL_POINTS:
  earn_per_100_lkr             = 1
  earn_review                  = 50
  earn_referral                = 500
  earn_social_post             = 5
  earn_social_like             = 2
  earn_waiting_list            = 10
  earn_daily_social_max        = 80
  earn_per_booking_max         = 5000
  redeem_max_percent           = 30
  redemption_face_value        = 1.00
  redemption_cost              = 0.80
  expiry_months                = 12
  tier_verified                = 500
  tier_pro                     = 2000
  tier_elite                   = 10000

TAXI:
  min_fare                     = 300
  max_surge_multiplier         = 3.0
  auto_surge_enabled           = true
  surge_demand_threshold       = 1.5
  peak_hours_morning           = 07:00-09:00
  peak_hours_evening           = 16:00-20:00
  peak_morning_bonus           = 0.2
  peak_evening_bonus           = 0.3
  acceptance_timeout_sec       = 30
  free_wait_minutes            = 5
  wait_per_minute_lkr          = 15
  search_radius_km             = 10
  expansion_radius_km          = 20
  cash_commission_statement_day = monday
  cash_commission_due_days     = 7

EVENTS:
  qr_scanner_fee               = 2500
  seat_hold_minutes            = 15
  waiting_list_offer_hours     = 4
  waiting_list_pts             = 10

PROPERTIES:
  promo_expiry_days            = 30
  dispute_window_hours         = 48
  seller_confirm_hours         = 48
  broker_consent_months        = 6

SOCIAL:
  max_post_length              = 2000
  max_photos_per_post          = 10
  max_video_sec                = 60
  report_auto_hide             = 5
  affiliate_pct                = 0.02

FLASH_DEALS:
  min_discount_pct             = 10
  max_simultaneous             = 3
  max_redemptions_ceiling      = 500
  voucher_validity_hours       = 48

AI:
  concierge_model              = gpt-4o-mini
  translation_provider         = deepl
  voice_transcription_model    = whisper-1
  moderation_nudity_threshold  = 0.80
  pricing_suggestion_enabled   = true

MAPS:
  google_maps_enabled          = true
  google_places_enabled        = true
  default_map_lat              = 7.8731
  default_map_lng              = 80.7718
  default_map_zoom             = 8

SUPPORT:
  critical_response_hours      = 1
  high_response_hours          = 2
  normal_response_hours        = 24
  dispute_resolution_days      = 7

ALL feature_flags (key → default):
  service.stays.enabled                    → true
  service.vehicles.enabled                 → true
  service.taxi.enabled                     → true
  service.events.enabled                   → true
  service.experiences.enabled              → true
  service.properties.enabled               → true
  service.social.enabled                   → true
  service.sme.enabled                      → true
  service.flash_deals.enabled              → true
  payment.card.enabled                     → true
  payment.bank_transfer.enabled            → true
  payment.cash.enabled                     → true
  payment.card_handling_fee.enabled        → false
  payment.pearl_points_at_checkout.enabled → true
  feature.google_maps.enabled              → true
  feature.google_places.enabled            → true
  feature.ai_concierge.enabled             → true
  feature.chat.translation.enabled         → true
  feature.chat.voice.enabled               → true
  feature.chat.booking_chat.enabled        → true
  feature.chat.social_chat.enabled         → true
  feature.social.short_video.enabled       → true
  feature.social.stories.enabled           → true
  feature.social.premium.enabled           → true
  feature.sme.appointments.enabled         → true
  feature.sme.loyalty_stamps.enabled       → true
  feature.events.qr_checkin.enabled        → true
  feature.events.waiting_list.enabled      → true
  feature.taxi.scheduled.enabled           → true
  feature.taxi.fare_split.enabled          → true
  feature.taxi.quests.enabled              → true
  feature.taxi.auto_surge.enabled          → true
  feature.taxi.cash_commission.enabled     → true
  feature.vehicles.deposit.enabled         → true
  feature.vehicles.fleet.enabled           → true
  feature.property.broker.enabled          → true
  feature.property.sale_promo.enabled      → true
  feature.bundles.enabled                  → true
  feature.flash_deals.enabled              → true
  feature.waiting_list.enabled             → true
  feature.booking_modification.enabled     → true
  feature.smart_pricing.enabled            → true
  feature.social_commerce.enabled          → true
  feature.multilingual_listings.enabled    → true
  feature.ai.content_moderation.enabled    → true
  feature.cash_agents.enabled              → true
  feature.corporate_taxi.enabled           → true
  feature.push_notifications.enabled       → true
  feature.email_notifications.enabled      → true
  feature.sms_notifications.enabled        → true
  feature.whatsapp_notifications.enabled   → true
```

---

## C25. GO-LIVE CHECKLIST

```
INFRASTRUCTURE:
  □ Railway API deployed + auto-restart on crash
  □ Vercel Web deployed + preview deployments enabled
  □ Supabase Pro plan (not free tier)
  □ All 10 Edge Functions deployed + secrets set
  □ Upstash Redis production (TLS)
  □ Meilisearch Cloud (all 9 verticals indexed)
  □ DNS: grabber.lk → Vercel, api.grabber.lk → Railway, ws.grabber.lk → Reverb
  □ SSL: all subdomains (automatic via platform)
  □ CDN: media.grabber.lk → Supabase Storage CDN

DATABASE:
  □ All migrations run (0 pending)
  □ All pg_cron jobs active (verify with SELECT cron.job)
  □ PostGIS, vector, pgcrypto, pg_cron extensions enabled
  □ All indexes created (verify with pg_indexes)
  □ platform_config fully seeded
  □ feature_flags fully seeded
  □ Taxi categories seeded (Economy, Comfort, SUV, Tuk-tuk, etc.)
  □ Admin roles + permissions seeded

ADMIN SETUP:
  □ Super admin account created (2FA set up and verified)
  □ All department staff accounts: finance_admin, marketing_admin, hr_admin,
    customer_service_agent (×2), kyc_officer (×2), issue_resolver
  □ All dept accounts: 2FA enrolled and verified
  □ Grabber bank account details entered in platform_config
  □ WebxPay: sandbox tested → production merchant ID + secret set
  □ First cash agent registered with training completed
  □ WebxPay payment methods tested: card ✓, bank transfer ✓
  □ Cash at agent flow tested ✓

APPS:
  □ Grabber: Google Play submission + App Store submission
  □ Grabber Provider: both stores
  □ Grabber Admin: Firebase App Distribution (internal only)
  □ All apps: correct icons, splash, app names, store listings
  □ privacy_policy URL live at grabber.lk/privacy
  □ terms URL live at grabber.lk/terms

LEGAL / COMPLIANCE:
  □ Terms of Service drafted and live
  □ Privacy Policy drafted and live
  □ Refund Policy live
  □ Broker agreement template reviewed
  □ IRDSL consultation completed (VAT registration if required)
  □ CBSL legal advice obtained (provider wallet → e-money regulations)

MONITORING:
  □ Sentry DSN set for API + Web + Flutter
  □ BetterUptime monitors: api.grabber.lk, grabber.lk, Supabase health
  □ Alert: >10 errors/min → Slack/email notification
  □ Alert: queue > 500 jobs (Horizon alert)
  □ Alert: cash invoice overdue → accounting team

PRODUCTION SMOKE TESTS (run on live production):
  □ Register customer account → receive OTP → verify → profile created
  □ Book a stay with CARD → WebxPay payment → webhook → booking confirmed → provider notified
  □ Book a stay with BANK TRANSFER → bank details shown → accounting confirms → booking active
  □ Book a stay with CASH AGENT → voucher shown → agent enters in admin → booking active
  □ Request taxi CARD → driver receives push (test driver) → accepts → completes → wallet credited
  □ Request taxi CASH → driver marks cash paid → weekly statement generated
  □ Buy event ticket → QR downloaded → gate scanner validates → second scan rejected
  □ Property sale: promo generated → buyer confirms → seller confirms → pts awarded to buyer
  □ Post flash deal (pay LKR 1,500) → customer claims → uses at checkout
  □ Admin: toggle a feature flag → reflected immediately on website
  □ Admin: process a provider payout → provider receives notification
  □ Admin: confirm bank transfer → booking activated
  □ AI Concierge on stays listing → asks question → gets contextual answer
  □ Chat: customer messages provider → provider translates to Sinhala → voice note sent → transcribed
  □ Pearl Points: earn from booking → apply at next checkout → WebxPay charged correct amount
```

---

## QUICK REFERENCE CARD

```
GRABBER PLATFORM — AT A GLANCE

Company:  Grabber Mobility Solutions Pvt Ltd
Website:  grabber.lk | API: api.grabber.lk
Apps:     Grabber · Grabber Provider · Grabber Admin

PAYMENT FLOW:
  Card    → WebxPay → Grabber → Commission → Provider Wallet
  Bank    → Customer's bank → GRABBER ACCOUNT ONLY → Accounting confirms → Provider Wallet
  Cash    → Provider direct (taxi/stays) → Monthly commission invoice to Grabber
            OR Cash agent → Grabber daily reconciliation → Provider Wallet

PROVIDER WALLET:
  Receives:  Booking earnings (after commission, after hold period)
  Can withdraw: Bank transfer to provider's own bank (LKR 50 fee, min LKR 5,000)
  Cannot: Transfer to other users · Top up customer balance · Receive cash payments

CUSTOMER WALLET: DOES NOT EXIST
  Customers pay WebxPay every single time
  Pearl Points = checkout discount only (not transferable, not cash)

PEARL POINTS:
  Earn: 1pt per LKR 100 spent · 50pts/review · 500pts/referral
  Use:  Reduce WebxPay charge by up to 30% of booking (1pt = LKR 1 discount)
  NOT: Transferred, withdrawn, gifted, or converted to cash

COMMISSIONS: Stays 12% | Vehicles 10% | Taxi 15% | Events 8% | 
             Experiences 10% | Properties (sale) 2% | Social/SME: subscription only

SUBSCRIPTIONS: Vehicles LKR 9,500/vehicle/month | Experiences LKR 29,500/month
               SME Premium LKR 6,500/month | Social Premium LKR 3,300/month
               All via WebxPay recurring · 7-day grace · admin can override price

VERTICALS (9): Stays | Vehicles | Taxi | Events | Experiences | Properties | Social | SME | Flash Deals

DEPARTMENTS (6): Accounting | Marketing | HR | Customer Service | KYC | Issue Resolution
```

---

*Grabber — Final Complete Development Document*  
*Grabber Mobility Solutions Pvt Ltd · grabber.lk · April 2026*  
*Laravel 11 · PostgreSQL 16 · Supabase · Next.js 15 · Flutter 3.x · WebxPay*  
*This is the single source of truth for the entire platform development.*
