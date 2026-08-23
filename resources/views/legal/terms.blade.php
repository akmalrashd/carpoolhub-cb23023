<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Terms of Service & Privacy Policy | CarpoolHub</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Poppins:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="preconnect" href="https://cdnjs.cloudflare.com" crossorigin>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css" integrity="sha512-DTOQO9RWCH3ppGqcWaEA1BIZOC6xxalwEsw9c2QQeAIftl+Vegovlnee1c9QX4TctnWMn13TZye+giMm8e2LwA==" crossorigin="anonymous" referrerpolicy="no-referrer">
    <link rel="icon" type="image/png" href="{{ asset('assets/branding/icon.png') }}">
    <style>
        :root {
            --ch-yellow: #FACC15; --ch-yellow-deep: #E6B800; --ch-yellow-ink: #2A1E04;
            --ink: #0B1220; --ink-2: #1F2937; --ink-3: #475569;
            --muted: #64748B; --muted-2: #94A3B8;
            --hairline: #ECE7DA; --hairline-strong: #DAD2BE;
            --surface: #FFFFFF; --surface-2: #FAF7EE; --canvas: #F4EFE2;
            --font-display: "Poppins", sans-serif; --font-ui: "Inter", sans-serif;
        }
        * { box-sizing: border-box; }
        body {
            margin: 0;
            font-family: var(--font-ui);
            background: var(--canvas);
            color: var(--ink-2);
            line-height: 1.65;
        }
        .legal-header {
            background: var(--ink);
            padding: 28px 20px;
        }
        .legal-header-inner {
            max-width: 820px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 16px;
        }
        .legal-logo {
            display: inline-flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
        }
        .legal-logo img { width: 32px; height: 32px; }
        .legal-logo span {
            font-family: var(--font-display);
            font-size: 20px;
            font-weight: 800;
            color: #fff;
        }
        .legal-logo span b { color: var(--ch-yellow); font-weight: 800; }
        .legal-back {
            color: var(--muted-2);
            font-size: 13px;
            font-weight: 600;
            text-decoration: none;
        }
        .legal-back:hover { color: #fff; }
        .legal-wrap {
            max-width: 820px;
            margin: 0 auto;
            padding: 36px 20px 80px;
        }
        .legal-title {
            font-family: var(--font-display);
            font-size: clamp(26px, 4vw, 34px);
            font-weight: 800;
            color: var(--ink);
            margin: 0 0 6px;
        }
        .legal-meta {
            font-size: 13px;
            color: var(--muted);
            margin-bottom: 28px;
        }
        .legal-summary {
            background: var(--surface);
            border: 1px solid var(--ch-yellow-deep);
            border-radius: 14px;
            padding: 18px 20px;
            margin-bottom: 28px;
            font-size: 14.5px;
        }
        .legal-summary strong { color: var(--ink); }
        .legal-toc {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            padding: 18px 22px;
            margin-bottom: 32px;
        }
        .legal-toc-title {
            font-size: 12px;
            font-weight: 800;
            letter-spacing: 0.06em;
            text-transform: uppercase;
            color: var(--muted);
            margin: 0 0 10px;
        }
        .legal-toc ol {
            list-style: none;
            columns: 2;
            column-gap: 24px;
            margin: 0;
            padding-left: 0;
            font-size: 13.5px;
        }
        .legal-toc li { margin-bottom: 5px; break-inside: avoid; }
        .legal-toc a { color: var(--ink-2); text-decoration: none; }
        .legal-toc a:hover { color: var(--ch-yellow-deep); text-decoration: underline; }
        section.legal-section {
            background: var(--surface);
            border: 1px solid var(--hairline);
            border-radius: 14px;
            padding: 22px 24px;
            margin-bottom: 16px;
            scroll-margin-top: 20px;
        }
        .legal-section h2 {
            font-family: var(--font-display);
            font-size: 18px;
            font-weight: 700;
            color: var(--ink);
            margin: 0 0 10px;
        }
        .legal-section h3 {
            font-size: 14.5px;
            font-weight: 700;
            color: var(--ink);
            margin: 16px 0 6px;
        }
        .legal-section p { margin: 0 0 10px; font-size: 14.5px; color: var(--ink-3); }
        .legal-section ul, .legal-section ol.legal-list {
            margin: 0 0 10px;
            padding-left: 20px;
            font-size: 14.5px;
            color: var(--ink-3);
        }
        .legal-section li { margin-bottom: 6px; }
        .legal-section a { color: var(--ch-yellow-deep); font-weight: 600; }
        .legal-doc-title {
            font-family: var(--font-display);
            font-size: 24px;
            font-weight: 800;
            color: var(--ink);
            margin: 48px 0 6px;
            scroll-margin-top: 20px;
        }
        .legal-enforce-card {
            border: 1px solid var(--hairline-strong);
            border-radius: 10px;
            padding: 12px 16px;
            margin-bottom: 10px;
            background: var(--surface-2);
        }
        .legal-enforce-card h4 {
            margin: 0 0 4px;
            font-size: 13.5px;
            color: var(--ink);
        }
        .legal-enforce-card p { margin: 0; font-size: 13.5px; }
        .legal-footer {
            text-align: center;
            font-size: 13px;
            color: var(--muted);
            padding: 20px 0 0;
        }
        @media (max-width: 600px) {
            .legal-toc ol { columns: 1; }
        }
    </style>
</head>
<body>

    <div class="legal-header">
        <div class="legal-header-inner">
            <a href="{{ route('home') }}" class="legal-logo">
                <img src="{{ asset('assets/branding/logo-small-b.png') }}" alt="">
                <span>Carpool<b>Hub</b></span>
            </a>
            <a href="{{ url()->previous() === url()->current() ? route('register') : url()->previous() }}" class="legal-back">
                <i class="fa-solid fa-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <div class="legal-wrap">

        <h1 class="legal-title">Terms of Service</h1>
        <p class="legal-meta">
            Effective: 23 August 2026 &nbsp;·&nbsp; Version 1.0 &nbsp;·&nbsp;
            CarpoolHub, operated by Prsdnt Worldwide
        </p>

        <div class="legal-summary">
            <strong>Plain-English summary:</strong> CarpoolHub is a carpool-matching app built for
            Malaysian commuters and campus communities. Use it honestly, treat other
            drivers and passengers with respect, and don't post harmful or illegal
            content. Fares are agreed and paid <strong>directly between driver and
            passenger</strong> (usually by DuitNow or Touch 'n Go bank transfer) — CarpoolHub
            never processes, holds, or guarantees any payment, and is not a party to
            the arrangement between you and the other user. Drivers go through a
            one-time license and identity check before they can post trips. We remove
            content and suspend or ban accounts that break these rules — especially
            for fraud, harassment, fake trips, or unsafe conduct. We don't sell your
            data, and you can delete your account at any time. This is a summary
            only; the full Terms below govern your use.
        </div>

        <div class="legal-toc">
            <p class="legal-toc-title">Contents</p>
            <ol>
                <li><a href="#s1">1. About CarpoolHub</a></li>
                <li><a href="#s2">2. Acceptance & Changes</a></li>
                <li><a href="#s3">3. Eligibility</a></li>
                <li><a href="#s4">4. Your Account</a></li>
                <li><a href="#s5">5. Features</a></li>
                <li><a href="#s6">6. Driver Verification</a></li>
                <li><a href="#s7">7. Public Profile Information</a></li>
                <li><a href="#s8">8. Conduct Rules</a></li>
                <li><a href="#s9">9. Trips, Fares & Ride Arrangements</a></li>
                <li><a href="#s10">10. Passenger Risk Insights</a></li>
                <li><a href="#s11">11. Enforcement & Account Suspension</a></li>
                <li><a href="#s12">12. Acceptable Use</a></li>
                <li><a href="#s13">13. Fares & Payments</a></li>
                <li><a href="#s14">14. Your Content & Licence</a></li>
                <li><a href="#s15">15. Intellectual Property</a></li>
                <li><a href="#s16">16. Disclaimers</a></li>
                <li><a href="#s17">17. Limitation of Liability</a></li>
                <li><a href="#s18">18. Indemnification</a></li>
                <li><a href="#s19">19. Termination</a></li>
                <li><a href="#s20">20. General</a></li>
                <li><a href="#s21">21. Governing Law</a></li>
                <li><a href="#privacy">Privacy Policy</a></li>
            </ol>
        </div>

        <section class="legal-section" id="s1">
            <h2>1. About CarpoolHub</h2>
            <p>
                CarpoolHub ("the Service", "we", "us", "our") is a ride-matching and
                fare-splitting platform for Malaysian commuters, built primarily
                around campus and workplace communities, and operated by
                <strong>Prsdnt Worldwide</strong> (contact: legal@prsdntworldwide.com).
                These Terms of Service, together with the Privacy Policy below, form
                a binding agreement between you and Prsdnt Worldwide governing your
                access to and use of the Service.
            </p>
        </section>

        <section class="legal-section" id="s2">
            <h2>2. Acceptance & Changes to the Terms</h2>
            <p>
                By creating an account or using any part of the Service — including
                posting or joining trips, the Explore search, the AI chat assistant,
                payments tracking, Connections, or notifications — you confirm that
                you have read, understood, and agree to be bound by these Terms. If
                you do not agree, do not use the Service.
            </p>
            <p>
                We may update these Terms from time to time. Where changes are
                material, we will give notice by email or an in-app notification. The
                effective date above reflects the latest revision, and your continued
                use of the Service after changes take effect constitutes acceptance
                of the updated Terms.
            </p>
        </section>

        <section class="legal-section" id="s3">
            <h2>3. Eligibility</h2>
            <p>
                You must be at least 18 years old, or at least 13 years old with the
                consent and awareness of a parent or guardian, to use CarpoolHub —
                driving requires a valid Malaysian driving licence regardless of age.
                By registering, you confirm that you meet this requirement. Posting
                trips as a driver additionally requires a one-time verification of
                your driving licence and identity (see section 6); riding as a
                passenger does not.
            </p>
        </section>

        <section class="legal-section" id="s4">
            <h2>4. Your Account</h2>
            <ul>
                <li>You are responsible for keeping your login credentials confidential and for all activity under your account.</li>
                <li>You agree to provide accurate, current information when registering — including your real name, a working contact method, and, if you register as a driver, your actual vehicle and licence details.</li>
                <li>You must not share your account, or use another person's account, without their explicit permission.</li>
                <li>You must not create multiple accounts to evade a suspension or ban; doing so will result in all related accounts being permanently banned.</li>
                <li>We may suspend or terminate accounts that violate these Terms, at our reasonable discretion.</li>
            </ul>
        </section>

        <section class="legal-section" id="s5">
            <h2>5. Features</h2>
            <p>CarpoolHub currently provides the following features. Features may be added, changed, or removed over time.</p>
            <ul>
                <li><strong>Post & Join Trips</strong> — drivers post one-way or two-way trips with a route, schedule, seat limit, and fare; passengers request to join public trips or are added directly to private ones.</li>
                <li><strong>Explore & Matching</strong> — search public trips by route, date, and seat availability, with trips ranked using a matching score based on route fit, timing, seats, fare, your Connections, and your trip history.</li>
                <li><strong>Fare Splitting</strong> — the trip fare is split among confirmed passengers automatically, with an additional fee if a passenger requests a custom pickup or drop-off point that detours from the driver's route.</li>
                <li><strong>Payments Tracking</strong> — a record-keeping tool for marking a fare as paid and letting the driver confirm or query it. CarpoolHub does not process the payment itself (see section 13).</li>
                <li><strong>Attendance & Absence Records</strong> — drivers can mark a confirmed passenger absent (from shortly before departure onward) or remove them with a stated reason, building a reliability history for that passenger.</li>
                <li><strong>Driver Verification</strong> — a one-time licence and identity check before a new driver's account is activated (see section 6).</li>
                <li><strong>Passenger Risk Insights</strong> — an internal reliability indicator shown only to the trip's driver, computed from that passenger's own payment, cancellation, and attendance history (see section 10).</li>
                <li><strong>AI Chat Assistant</strong> — a conversational assistant that can help draft a new trip or saved route, suggest a fare based on distance, and answer general questions about using the app.</li>
                <li><strong>Connections</strong> — add other users as trusted contacts to see their trips more easily in Explore.</li>
                <li><strong>Saved Routes</strong> — save a frequently-driven route for quicker trip creation.</li>
                <li><strong>Notifications</strong> — in-app and optional push notifications about activity relevant to you (join requests, payment updates, trip changes).</li>
            </ul>
        </section>

        <section class="legal-section" id="s6">
            <h2>6. Driver Verification</h2>
            <p>
                To post trips as a driver, you must submit a photo of your driving
                licence and a selfie holding that licence. Your account remains
                inactive — unable to post or join any trip — until an administrator
                has reviewed and approved this submission.
            </p>
            <ul>
                <li>You must submit your own, genuine, current driving licence. Submitting someone else's licence, or a fraudulent or altered document, will result in a permanent ban.</li>
                <li>You may not attempt to bypass, automate, or manipulate the verification review.</li>
                <li>Your licence and selfie photos are used only for this one-time review and to resolve safety disputes; they are never shown to passengers or displayed publicly (see section 7).</li>
                <li>Verification is a trust signal only, confirming you hold a valid licence and control the vehicle details on file — it is not a background check and does not guarantee a driver's conduct, and we may revoke it at any time if misuse is suspected or confirmed.</li>
            </ul>
        </section>

        <section class="legal-section" id="s7">
            <h2>7. Public Profile Information & What Others Can See</h2>
            <p>
                A passenger considering a public trip, or a driver reviewing a join
                request, can see your name, profile photo, and (if you choose to make
                them visible) your email and phone number. A driver you have an
                approved seat with can additionally see your trip and payment status
                for that trip. Your driving licence photo, selfie, and full payment
                account details are never shown to other users — a driver's DuitNow /
                Touch 'n Go QR code is the only payment detail passengers see, and
                only for trips they've joined. You can control what's visible to
                others from your account settings at any time.
            </p>
        </section>

        <section class="legal-section" id="s8">
            <h2>8. Conduct Rules</h2>
            <p>You are solely responsible for the trip details, notes, and messages you post or send through the Service. You must not:</p>
            <ul>
                <li>Post a trip you do not genuinely intend to drive or take, or repeatedly no-show on confirmed trips.</li>
                <li>Harass, threaten, or discriminate against another user based on race, religion, gender, disability, or any other protected characteristic.</li>
                <li>Share another user's personal information without their consent, or contact them outside the app after being asked to stop.</li>
                <li>Impersonate another person, a CarpoolHub administrator, or any organisation.</li>
                <li>Post deliberately false trip details (route, fare, seats, or schedule) intended to mislead a passenger or driver.</li>
                <li>Use trip notes, chat, or the AI assistant to send spam, phishing links, or unsolicited advertising.</li>
                <li>Attempt to arrange or solicit anything illegal under Malaysian law through the Service.</li>
            </ul>
        </section>

        <section class="legal-section" id="s9">
            <h2>9. Trips, Fares & Ride Arrangements</h2>
            <p>
                CarpoolHub connects drivers and passengers; it is not a party to any
                trip, and does not itself provide transportation. We do not guarantee
                a driver's punctuality, driving standard, or vehicle condition, nor a
                passenger's attendance or payment. All arrangements — including the
                agreed fare, pickup point, and schedule — are solely between the
                driver and passenger.
            </p>
            <p>
                We may remove any trip listing at any time without notice, for
                example if it appears fraudulent or violates these Terms. For your
                own safety, we recommend confirming pickup details in advance and
                meeting at a clearly agreed, public location.
            </p>
        </section>

        <section class="legal-section" id="s10">
            <h2>10. Passenger Risk Insights</h2>
            <p>
                To help drivers make an informed decision when reviewing a join
                request, CarpoolHub computes an internal reliability indicator for
                each passenger from their own history on the Service — payment
                reliability, cancelled requests, and marked absences. This indicator:
            </p>
            <ul>
                <li>Is shown only to the driver of the specific trip being requested, never publicly or to the passenger themselves.</li>
                <li>Is a decision-support signal, not a determination of fault or an accusation — a low score does not mean a rule was broken, only that the underlying history suggests caution.</li>
                <li>Carries no monetary value, is not a credit score, and is not shared with any party outside CarpoolHub.</li>
                <li>Is recalculated as your history changes, and is not retained after your account is deleted.</li>
            </ul>
        </section>

        <section class="legal-section" id="s11">
            <h2>11. Enforcement & Account Suspension</h2>
            <p>
                Violations — whether reported by users or identified by us — may lead
                to the following actions, applied at our reasonable discretion based
                on the severity and frequency of the violation:
            </p>
            <div class="legal-enforce-card">
                <h4>Content or Trip Removal</h4>
                <p>The offending trip listing, note, or message is removed. May be applied with or without further action.</p>
            </div>
            <div class="legal-enforce-card">
                <h4>Warning</h4>
                <p>A formal warning for minor or first-time violations, noted on your account.</p>
            </div>
            <div class="legal-enforce-card">
                <h4>Temporary Suspension</h4>
                <p>Your account is suspended for a set period, during which you cannot post, join, or manage any trip. Applied for repeated no-shows, payment disputes, or minor conduct violations.</p>
            </div>
            <div class="legal-enforce-card">
                <h4>Permanent Ban</h4>
                <p>Your account is permanently disabled. Applied immediately, without prior warning, for fraudulent driver documents, scamming another user, credible threats, doxxing, evading a previous ban, or any activity constituting a criminal offence under Malaysian law.</p>
            </div>
            <p>
                If you believe an action was taken in error, email
                legal@prsdntworldwide.com with the subject "Account Appeal". We
                review appeals fairly but cannot guarantee reinstatement.
            </p>
        </section>

        <section class="legal-section" id="s12">
            <h2>12. Acceptable Use</h2>
            <p>In addition to the conduct rules above, you agree not to:</p>
            <ul>
                <li>Use the Service for any unlawful purpose or in violation of applicable Malaysian or international law.</li>
                <li>Attempt to gain unauthorised access to any part of the Service, its servers, databases, or systems.</li>
                <li>Upload or transmit viruses, malware, or other harmful code.</li>
                <li>Scrape, copy, or redistribute trip data, user profiles, or Service functionality without our prior written consent.</li>
                <li>Use bots, scripts, or other automated means to post trips or join requests without our prior written consent.</li>
                <li>Reverse-engineer, decompile, or attempt to extract source code from any part of the Service.</li>
            </ul>
        </section>

        <section class="legal-section" id="s13">
            <h2>13. Fares & Payments</h2>
            <p>
                CarpoolHub is free to use — there is no subscription or fee to create
                an account, post a trip, or join one.
            </p>
            <ul>
                <li>Fares are set by the driver and split among passengers by the app; the agreed amount is payable <strong>directly to the driver</strong>, typically by DuitNow or Touch 'n Go e-wallet bank transfer, using the payment details the driver has added to their own profile.</li>
                <li>CarpoolHub does not process, transmit, or hold any funds, is not a payment institution, and is not a party to the payment. We only let a passenger mark a fare as paid and a driver confirm or query it, for both parties' record-keeping.</li>
                <li>Because we never touch the funds, we cannot issue a refund. A payment dispute — non-payment, a wrong amount, or a disagreement over a paid fare — is between the driver and passenger; CarpoolHub may assist by sharing the in-app record of what was marked, but cannot compel or reverse a bank transfer.</li>
                <li>Suspension or a ban does not entitle you to anything from CarpoolHub in connection with a fare, since no fare is ever paid to us.</li>
            </ul>
        </section>

        <section class="legal-section" id="s14">
            <h2>14. Your Content & Licence</h2>
            <p>
                You retain ownership of the content you post (trip details, notes,
                messages, your profile photo). By posting, you grant CarpoolHub a
                non-exclusive, royalty-free, worldwide licence to host, store,
                reproduce, and display that content solely to operate, secure, and
                improve the Service. This licence ends when you delete your content
                or account, subject to reasonable technical delays and to backups or
                records we retain for safety, dispute-resolution, or legal purposes.
            </p>
            <p>
                You confirm you have the right to post any content you submit and
                that it does not infringe any third party's rights.
            </p>
        </section>

        <section class="legal-section" id="s15">
            <h2>15. Intellectual Property & Copyright Complaints</h2>
            <p>
                All content, branding, design, and code within CarpoolHub (excluding
                user-generated trip content) are the property of Prsdnt Worldwide.
                You may not reproduce, distribute, or create derivative works without
                our prior written consent.
            </p>
            <p>
                If you believe content on CarpoolHub infringes your intellectual
                property, email legal@prsdntworldwide.com with the subject "IP
                Complaint", including a description of the work, a link to the
                allegedly infringing content, your contact details, and a good-faith
                statement that you are the rights holder or authorised to act.
            </p>
        </section>

        <section class="legal-section" id="s16">
            <h2>16. Disclaimer of Warranties</h2>
            <p>
                The Service is provided "as is" and "as available", without
                warranties of any kind, express or implied, to the fullest extent
                permitted by law. Fare estimates, AI-suggested fares, trip matches,
                and passenger risk insights are for reference only and do not
                guarantee accuracy, a driver's or passenger's conduct, or a safe or
                punctual ride. Trip content is provided by users and does not
                represent the views of CarpoolHub or Prsdnt Worldwide.
            </p>
        </section>

        <section class="legal-section" id="s17">
            <h2>17. Limitation of Liability</h2>
            <p>
                To the maximum extent permitted by Malaysian law, Prsdnt Worldwide
                shall not be liable for any indirect, incidental, special, or
                consequential damages arising from your use of, or inability to use,
                the Service — including losses arising from a ride, a payment
                dispute between users, a driver's or passenger's conduct, or an
                accident. Nothing in these Terms excludes or limits liability that
                cannot be excluded or limited under applicable law.
            </p>
        </section>

        <section class="legal-section" id="s18">
            <h2>18. Indemnification</h2>
            <p>
                You agree to indemnify and hold harmless Prsdnt Worldwide from and
                against any claims, damages, losses, liabilities, and reasonable
                costs (including legal fees) arising out of or connected with your
                content, your use of the Service, your trips or payment
                arrangements with another user, or your breach of these Terms or of
                any applicable law or third-party right.
            </p>
        </section>

        <section class="legal-section" id="s19">
            <h2>19. Termination</h2>
            <p>
                You may stop using the Service and delete your account at any time
                from your settings or by contacting us. We may suspend or terminate
                your access as described in section 11, or if we discontinue the
                Service. On termination, sections that by their nature should survive
                (including content licence to the extent needed, intellectual
                property, disclaimers, limitation of liability, indemnification, and
                governing law) will continue to apply.
            </p>
        </section>

        <section class="legal-section" id="s20">
            <h2>20. General</h2>
            <ul>
                <li><strong>Entire agreement.</strong> These Terms and the Privacy Policy are the entire agreement between you and Prsdnt Worldwide regarding the Service.</li>
                <li><strong>Severability.</strong> If any provision is found unenforceable, the remaining provisions stay in full force.</li>
                <li><strong>No waiver.</strong> Our failure to enforce any provision is not a waiver of our right to do so later.</li>
                <li><strong>Assignment.</strong> You may not assign your rights under these Terms. We may assign or transfer ours, for example in connection with a merger or acquisition.</li>
                <li><strong>Force majeure.</strong> We are not liable for delays or failures caused by events beyond our reasonable control.</li>
            </ul>
        </section>

        <section class="legal-section" id="s21">
            <h2>21. Governing Law & Disputes</h2>
            <p>
                These Terms are governed by the laws of Malaysia, including the
                Communications and Multimedia Act 1998, the Personal Data Protection
                Act 2010, the Computer Crimes Act 1997, and the Consumer Protection
                Act 1999 where applicable. We encourage you to contact us first so we
                can try to resolve any concern informally. Any dispute that cannot be
                resolved is subject to the exclusive jurisdiction of the courts of
                Malaysia.
            </p>
        </section>

        <h1 class="legal-doc-title" id="privacy">Privacy Policy</h1>
        <p class="legal-meta">
            Effective: 23 August 2026 &nbsp;·&nbsp; Version 1.0 &nbsp;·&nbsp;
            CarpoolHub by Prsdnt Worldwide
        </p>

        <section class="legal-section">
            <p>
                This policy explains how CarpoolHub collects, uses, discloses, and
                protects your personal data, in line with the Personal Data
                Protection Act 2010 (PDPA) of Malaysia. We collect only what we need
                to run the Service, and we do not sell your personal data. Prsdnt
                Worldwide is the data controller responsible for your personal data.
            </p>

            <h3>What We Collect</h3>
            <ul>
                <li><strong>Account data</strong> — your name, email, phone number, and profile photo, used to create and manage your account.</li>
                <li><strong>Trip data</strong> — pickup and destination points, schedule, seat count, fare, and trip notes you create or join.</li>
                <li><strong>Driver verification data</strong> — your vehicle model and plate number, and (for drivers) a photo of your driving licence and a selfie holding it, submitted for the one-time review in section 6 of the Terms. These photos are only visible to administrators reviewing your application, and are never shown to passengers or displayed publicly.</li>
                <li><strong>Payment record data</strong> — the fare amount, payment status (unpaid, marked paid, confirmed), and, for drivers, the bank account name/number and DuitNow/Touch 'n Go QR image you choose to add so passengers can pay you directly. We do not process any payment or hold any funds — see section 13 of the Terms.</li>
                <li><strong>Connections data</strong> — the contacts you add and their response, used to operate the Connections feature.</li>
                <li><strong>AI assistant data</strong> — messages you send to the in-app AI assistant, used to draft trips, suggest fares, and answer your questions. These messages are processed by our AI provider (see Third-Party Services below) solely to generate a response.</li>
                <li><strong>Notification data</strong> — if you enable push notifications, your browser/device push subscription token, removed when you disable notifications or delete your account.</li>
                <li><strong>Usage & device data</strong> — pages visited, features used, session timestamps, and technical information such as IP address and browser type from server logs, used to run the Service securely and diagnose issues.</li>
                <li><strong>Support & reports</strong> — the content of reports, appeals, and support requests you send, so we can respond and keep records.</li>
            </ul>
            <p>We do not collect identity card (IC/NRIC) numbers, passport details, or banking passwords/credentials.</p>

            <h3>How We Use Your Data</h3>
            <ul>
                <li>To provide and personalise your experience — trip matching, fare suggestions, and the AI assistant.</li>
                <li>To operate driver verification and keep the community safe.</li>
                <li>To send account-related messages — join-request updates, payment status changes, and important service notices.</li>
                <li>To analyse usage, measure feature adoption, and fix issues.</li>
                <li>To detect, prevent, and investigate fraud, fake trips, and abuse.</li>
                <li>To comply with legal obligations and respond to lawful requests.</li>
            </ul>
            <p>
                We rely on your consent, the performance of our agreement with you,
                and our legitimate interest in operating a safe Service as the bases
                for processing. We do not sell your personal data.
            </p>

            <h3>How We Share Data</h3>
            <p>
                We share personal data only as needed to run the Service: with the
                third-party processor listed below; where required by law, court
                order, or a lawful request from authorities; and to protect the
                rights, safety, and security of users or CarpoolHub. We do not
                otherwise disclose your personal data to third parties for their own
                marketing.
            </p>

            <h3>Third-Party Services</h3>
            <ul>
                <li><strong>Anthropic (Claude)</strong> — powers the in-app AI chat assistant. Messages you send it are processed to generate a reply; see Anthropic's own privacy policy for how they handle API data.</li>
                <li><strong>Hostinger</strong> — our hosting provider, storing the Service's database and files.</li>
            </ul>

            <h3>Data Retention</h3>
            <p>
                We keep your personal data while your account is active and for as
                long as needed to provide the Service. When you delete your account,
                we delete or anonymise your personal data, subject to short technical
                delays and to backups. We may retain certain records longer where
                necessary for safety (for example, records relating to a ban or a
                payment dispute) or to comply with legal obligations.
            </p>

            <h3>Data Storage & Security</h3>
            <p>
                Communication between your device and our servers is encrypted using
                HTTPS (TLS), and passwords are hashed — we never store them in plain
                text. We apply reasonable security measures, including access
                controls on driver verification documents. No system can guarantee
                absolute security; please use a strong, unique password and contact
                us immediately if you suspect any unauthorised activity on your
                account.
            </p>

            <h3>Your Rights & Account Deletion (PDPA 2010)</h3>
            <p>Under Malaysia's Personal Data Protection Act 2010, you have the right to:</p>
            <ul>
                <li><strong>Access</strong> — request a copy of the personal data we hold about you.</li>
                <li><strong>Correction</strong> — ask us to update inaccurate or outdated information.</li>
                <li><strong>Withdraw consent</strong> — stop us from processing your data (this may limit your ability to use certain features).</li>
                <li><strong>Account & data deletion</strong> — request deletion of your account and associated personal data.</li>
            </ul>
            <p>
                To exercise any of these rights, email legal@prsdntworldwide.com with
                the subject "Data Request". We will acknowledge promptly and respond
                within 21 days.
            </p>

            <h3>Children's Privacy</h3>
            <p>
                CarpoolHub is intended for users aged 13 and above and is not
                directed at children under 13. We do not knowingly collect personal
                data from children under 13. If you believe a child under 13 has
                provided us personal data, contact us and we will delete it.
            </p>

            <h3>Cookies & Tracking</h3>
            <p>
                We use cookies and similar technologies to keep you signed in and
                remember preferences. You can manage cookies in your browser
                settings, though disabling them may affect sign-in and core
                functionality.
            </p>

            <h3>Changes to This Policy</h3>
            <p>
                We may revise this Privacy Policy from time to time. Material changes
                will be communicated by email or an in-app notice, and the effective
                date above reflects the latest revision. Continued use after an
                update takes effect constitutes acceptance of the revised policy.
            </p>

            <h3>Contact Us</h3>
            <p>
                For any questions, data requests, appeals, or concerns about these
                Terms or this policy:<br>
                <strong>Prsdnt Worldwide</strong> · General & legal: legal@prsdntworldwide.com<br>
                We aim to respond within 3 business days (data requests within 21 days).
            </p>
        </section>

        <p class="legal-footer">&copy; 2026 Prsdnt Worldwide. CarpoolHub is a trademark of Prsdnt Worldwide.</p>

    </div>

</body>
</html>
